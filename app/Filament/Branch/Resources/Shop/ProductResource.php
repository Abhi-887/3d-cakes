<?php

declare(strict_types=1);

namespace App\Filament\Branch\Resources\Shop;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends \App\Filament\Admin\Resources\Shop\ProductResource
{
    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ProductResource\Pages\ListProducts::route('/'),
        ];
    }

    /**
     * disable tenant scoping
     */
    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        /** @var \Illuminate\Database\Eloquent\Builder<\Domain\Shop\Product\Models\Product> $query */
        $query = self::getModel()::query();

        return $query
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
