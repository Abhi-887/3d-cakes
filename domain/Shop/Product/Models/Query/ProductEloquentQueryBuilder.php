<?php

declare(strict_types=1);

namespace Domain\Shop\Product\Models\Query;

use Domain\Shop\Stock\Models\Query\SkuStockEloquentQueryBuilder;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends \Illuminate\Database\Eloquent\Builder<\Domain\Shop\Product\Models\Product>
 */
class ProductEloquentQueryBuilder extends Builder
{
    public function whereBaseOnStocksIsWarning(): self
    {
        return $this->whereRelation(
            'skus.skuStocks',
            fn (SkuStockEloquentQueryBuilder $query) => $query->whereBaseOnStocksIsWarning()
        );
    }

    public function whereBaseOnStocksNotZero(): self
    {
        return $this->whereRelation(
            'skus.skuStocks',
            fn (SkuStockEloquentQueryBuilder $query) => $query->whereBaseOnStocksNotZero()
        );
    }
}
