<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Shop\OrderResource;

use Domain\Shop\Order\Actions\CalculateOrderTotalPriceAction;
use Domain\Shop\Order\DataTransferObjects\ItemWithMinMaxData;

class Support
{
    private function __construct()
    {
    }

    public static function callCalculatorForTotalPrice(array $orderItems): float
    {
        return app(CalculateOrderTotalPriceAction::class)
            ->execute(
                collect($orderItems)
                    ->reject(fn ($data): bool => blank($data['sku_uuid']))
                    ->map(
                        fn (array $data): ItemWithMinMaxData => new ItemWithMinMaxData(
                            price: money($data['price'] * 100),
                            quantity: (float) $data['quantity'],
                            minimum: $data['minimum'],
                            maximum: $data['maximum']
                        )
                    )
                    ->toArray()
            )->getValue();
    }
}
