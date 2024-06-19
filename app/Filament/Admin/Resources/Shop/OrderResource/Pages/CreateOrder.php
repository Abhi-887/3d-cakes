<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Shop\OrderResource\Pages;

use App\Filament\Admin\Resources\Shop\CustomerResource\Schema\CustomerSchema;
use App\Filament\Admin\Resources\Shop\OrderResource;
use App\Filament\Admin\Resources\Shop\OrderResource\Support;
use App\Filament\Admin\Support\TenantHelper;
use Domain\Shop\Branch\Enums\Status as BranchStatus;
use Domain\Shop\Branch\Models\Branch;
use Domain\Shop\Customer\Models\Customer;
use Domain\Shop\Order\Actions\OrderCreatedPipelineAction;
use Domain\Shop\Order\Enums\PaymentMethod;
use Domain\Shop\Order\Enums\PaymentStatus;
use Domain\Shop\Order\Enums\Status as OrderStatus;
use Domain\Shop\Order\Models\OrderItem;
use Domain\Shop\Product\Models\Sku;
use Domain\Shop\Stock\Rules\CheckQuantitySkuStockRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Number;
use Throwable;

/**
 * @property-read \Domain\Shop\Order\Models\Order $record
 */
class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    #[\Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['delivery_price'] = money(0); // TODO: delivery price from selected customer address

        return $data;
    }

    #[\Override]
    public function form(Form $form): Form
    {
        $tenantBranch = TenantHelper::getBranch();

        return $form->schema([
            Forms\Components\Hidden::make('total_price')
                ->dehydrateStateUsing(
                    fn (Forms\Get $get): float => Support::callCalculatorForTotalPrice($get('orderItems'))
                ),

            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Wizard::make([
                        Forms\Components\Wizard\Step::make(trans('Order Details'))
                            ->schema([
                                Forms\Components\Select::make('customer_uuid')
                                    ->translateLabel()
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->optionsLimit(20)
                                    ->getOptionLabelFromRecordUsing(
                                        fn (Customer $record) => $record->full_name
                                    )
                                    ->relationship(
                                        'customer',
                                        'full_name',
                                        fn (Builder $query) => $query->latest()
                                    )
                                    ->createOptionForm([
                                        Forms\Components\Section::make(
                                            CustomerSchema::schema(),
                                        )->columns(['sm' => 2]),
                                    ])
                                    ->default(function () {
                                        $customerRouteKey = Request::get('customer');

                                        if (null === $customerRouteKey) {
                                            return null;
                                        }

                                        return Customer::where((new Customer())->getRouteKeyName(), $customerRouteKey)
                                            ->value((new Customer())->getKeyName());
                                    }),

                                Forms\Components\Select::make('branch_uuid')
                                    ->translateLabel()
                                    ->relationship(
                                        'branch',
                                        'name',
                                        fn (Builder $query) => $query
                                            ->where('status', BranchStatus::enabled)
                                            ->when($tenantBranch, fn (Builder $query, Branch $branch) => $query->whereKey($branch))
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->disabled(null !== $tenantBranch)
                                    ->default($tenantBranch?->getKey()),

                                Forms\Components\Select::make('payment_status')
                                    ->translateLabel()
                                    ->options(PaymentStatus::class)
                                    ->enum(PaymentStatus::class)
                                    ->required()
                                    ->default(PaymentStatus::pending),

                                Forms\Components\Select::make('status')
                                    ->translateLabel()
                                    ->options(OrderStatus::class)
                                    ->enum(OrderStatus::class)
                                    ->required()
                                    ->default(OrderStatus::pending),

                                Forms\Components\Select::make('payment_method')
                                    ->translateLabel()
                                    ->options(PaymentMethod::class)
                                    ->enum(PaymentMethod::class)
                                    ->nullable(),

                                Forms\Components\MarkdownEditor::make('notes')
                                    ->translateLabel()
                                    ->nullable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Forms\Components\Wizard\Step::make(trans('Order Items'))
                            ->schema([
                                Forms\Components\Repeater::make('orderItems')
                                    ->translateLabel()
                                    ->required()
                                    ->relationship('orderItems')
                                    ->schema(fn () => [
                                        Forms\Components\Select::make('sku_uuid')
                                            ->translateLabel()
                                            ->relationship(
                                                'sku',
                                                'code',
                                                fn (Forms\Get $get, Builder $query): Builder => $query
                                                    ->whereRelation(
                                                        'skuStocks.branch',
                                                        'uuid',
                                                        $tenantBranch?->getKey() ?? $get('../../branch_uuid')
                                                    )
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->optionsLimit(10)
                                            ->required()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->afterStateHydrated(
                                                function (Forms\Set $set, ?int $state, ?OrderItem $record): void {

                                                    if (null !== $record) {
                                                        return;
                                                    }

                                                    $price = Sku::whereKey($state)->value('price');
                                                    $set('price', number_format($price / 100, 2));
                                                }
                                            )
                                            ->afterStateUpdated(
                                                function (Forms\Set $set, $state, ?OrderItem $record): void {

                                                    if (null !== $record) {
                                                        return;
                                                    }

                                                    $sku = Sku::whereKey($state)->first();

                                                    if (null === $sku) {
                                                        return;
                                                    }

                                                    $set('price', $sku->price->getValue());
                                                    $set('minimum', $sku->minimum);
                                                    $set('maximum', $sku->maximum);
                                                }
                                            )
                                            ->reactive(),

                                        Forms\Components\TextInput::make('price')
                                            ->translateLabel()
                                            ->numeric()
                                            ->money()
                                            ->disabled()
                                            ->dehydrated(false),

                                        Forms\Components\TextInput::make('minimum')
                                            ->translateLabel()
                                            ->disabled()
                                            ->dehydrated(false),

                                        Forms\Components\TextInput::make('quantity')
                                            ->translateLabel()
                                            ->default(1)
                                            ->required()
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(9_999)
                                            ->rule(
                                                fn (Forms\Get $get) => new CheckQuantitySkuStockRule(
                                                    /** @phpstan-ignore-next-line  */
                                                    branch: $tenantBranch ?? Branch::whereKey($get('../../branch_uuid'))->first(),
                                                    sku: $get('sku_uuid'),
                                                ),
                                                // prevent the rule from running when the sku_uuid is null
                                                fn (Forms\Get $get) => null !== $get('sku_uuid')
                                            )
                                            ->reactive(),

                                        //            Forms\Components\TextInput::make('total')
                                        //                ->translateLabel()
                                        //                ->visibleOn('view')
                                        //                ->formatStateUsing(fn (?OrderItem $record) => $record === null
                                        //                    ? null
                                        //                    : number_format($record->total_price / 100, 2)),
                                    ])
                                    ->columns(4),
                            ]),
                    ])
                        ->submitAction($this->getSubmitFormAction())
                        ->cancelAction($this->getCancelFormAction()),
                ])
                ->columnSpan(['lg' => 3]),

            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Placeholder::make('total_price_placeholder')
                        ->label('Total price')
                        ->translateLabel()
                        ->content(
                            fn (Forms\Get $get) => Number::currency(
                                Support::callCalculatorForTotalPrice($get('orderItems')),
                                Table::$defaultCurrency
                            )
                        ),
                ])
                ->columnSpan(['lg' => 1]),

        ])
            ->columns(4);
    }

    /** @throws Throwable */
    protected function afterCreate(): void
    {
        app(OrderCreatedPipelineAction::class)
            ->execute($this->record);
    }

    #[\Override]
    public function getFormActions(): array
    {
        return [];
    }
}
