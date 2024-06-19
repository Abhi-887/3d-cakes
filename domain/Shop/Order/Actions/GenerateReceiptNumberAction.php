<?php

declare(strict_types=1);

namespace Domain\Shop\Order\Actions;

use App\Settings\OrderSettings;
use Domain\Shop\Branch\Models\Branch;
use Domain\Shop\Order\Models\Order;
use Illuminate\Support\Str;

readonly class GenerateReceiptNumberAction
{
    public function __construct(
        private OrderSettings $orderSettings,
    ) {
    }

    public function execute(Branch $branch): string
    {
        $prefix = $this->orderSettings->prefix.$branch->code;

        $dateTime = now();

        $y = $dateTime->format('y');
        $m = $dateTime->format('m');
        $d = $dateTime->format('d');

        $format = sprintf('%s%s%s%s', $prefix, $y, $m, $d);

        /** @var Order $latestModel */
        $latestModel = Order::withTrashed()
            ->where(
                'receipt_number',
                'like',
                $format.'%'
            )
            ->latest()
            ->first();

        if (blank($latestModel)) {

            $output = $format.'0001';

        } else {
            $dateLength = Str::length($y) + Str::length($m) + Str::length($d);

            $subStr = (string) Str::of($latestModel->receipt_number)
                ->substr(Str::length($prefix) + $dateLength);

            $number = ((int) $subStr) + 1;

            $output = $format.Str::of((string) $number)->padLeft(4, '0');
        }

        return $output;
    }
}
