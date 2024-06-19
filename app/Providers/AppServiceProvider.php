<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\SettingsActivityLogListener;
use App\Listeners\SupportBubbleSubmittedToSentryFeedbackListener;
use App\Providers\Macros\BluePrintMixin;
use Domain\Access\Admin\Models\Admin;
use Domain\Shop\Branch\Models\Branch;
use Domain\Shop\Brand\Models\Brand;
use Domain\Shop\Cart\Models\Cart;
use Domain\Shop\Category\Models\Category;
use Domain\Shop\Customer\Models\Address;
use Domain\Shop\Customer\Models\Customer;
use Domain\Shop\Order\Models\Order;
use Domain\Shop\Order\Models\OrderInvoice;
use Domain\Shop\Order\Models\OrderItem;
use Domain\Shop\Product\Models\Attribute;
use Domain\Shop\Product\Models\AttributeOption;
use Domain\Shop\Product\Models\Product;
use Domain\Shop\Product\Models\Sku;
use Domain\Shop\Stock\Models\SkuStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use ReflectionException;
use Sentry\Laravel\Integration;
use Spatie\LaravelSettings\Events\SavingSettings;
use Spatie\SupportBubble\Events\SupportBubbleSubmittedEvent;
use TiMacDonald\JsonApi\JsonApiResource;

class AppServiceProvider extends ServiceProvider
{
    /** @throws ReflectionException */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::handleLazyLoadingViolationUsing(Integration::lazyLoadingViolationReporter());

        Relation::enforceMorphMap([
            Admin::class,
            Product::class,
            Customer::class,
            Order::class,
            OrderItem::class,
            OrderInvoice::class,
            Sku::class,
            Attribute::class,
            AttributeOption::class,
            Branch::class,
            Brand::class,
            Address::class,
            Category::class,
            SkuStock::class,
            Cart::class,
            config('permission.models.role'),
            config('permission.models.permission'),
        ]);

        Password::defaults(
            fn () => $this->app->environment('local', 'testing')
                ? Password::min(4)
                : Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
        );

        $this->macros();
        self::registerEventsAndListeners();

        if (class_exists($class = '\Laravel\Telescope\TelescopeServiceProvider')) {
            $this->app->register($class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        JsonApiResource::resolveIdUsing(fn (Model $model): string => (string) $model->getRouteKey());

        // https://laravel.com/docs/10.x/localization#handling-missing-translation-strings
        // Lang::handleMissingKeysUsing(fn (string $key) => Log::info('Missing translation key .'.$key));

        Builder::$defaultMorphKeyType = 'uuid';
    }

    /** @throws ReflectionException */
    private function macros(): void
    {
        if ($this->app->runningInConsole()) {
            Blueprint::mixin(new BluePrintMixin());
        }

        Rule::macro(
            'email',
            fn (): string => app()->environment('local', 'testing')
                ? 'email'
                : 'email:rfc,dns'
        );
    }

    public static function registerEventsAndListeners(): void
    {
        Event::listen(
            SavingSettings::class,
            SettingsActivityLogListener::class
        );

        Event::listen(
            SupportBubbleSubmittedEvent::class,
            SupportBubbleSubmittedToSentryFeedbackListener::class
        );
    }
}
