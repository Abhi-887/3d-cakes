<?php

declare(strict_types=1);

use App\Settings\OrderSettings;
use Carbon\Carbon;
use Domain\Shop\Branch\Database\Factories\BranchFactory;
use Domain\Shop\Order\Actions\GenerateReceiptNumberAction;
use Domain\Shop\Order\Database\Factories\OrderFactory;

use function Pest\Laravel\travelTo;

beforeEach(function () {
    $this->branch = BranchFactory::new()->createOne(['code' => 'BRANCH']);
    $this->prefix = 'SETTING';

    OrderSettings::fake([
        'prefix' => $this->prefix,
    ]);

    $this->generator = app(GenerateReceiptNumberAction::class);
    travelTo(now()->parse('2021-01-01'));
});

it('generate purchase number for the fist time _', function () {

    expect($this->generator->execute($this->branch))
        ->toBe(generatePrefix($this->prefix, now()).'0001');
});

it('generate purchase number for the fist time in the next day', function () {

    $format = generatePrefix($this->prefix, now());

    OrderFactory::new()
        ->for(BranchFactory::new()->enabled()->createOne())
        ->sequence(
            ['receipt_number' => $format.'0001', 'created_at' => now()->subDays(4)],
            ['receipt_number' => $format.'0002', 'created_at' => now()->subDays(2)]
        )
        ->count(2)
        ->create();

    travelTo(now()->addDay());

    expect($this->generator->execute($this->branch))
        ->toBe(generatePrefix($this->prefix, now()).'0001');
});

it('generate purchase 3rd time _', function () {

    $format = generatePrefix($this->prefix, now());

    OrderFactory::new()
        ->for($this->branch)
        ->sequence(
            ['receipt_number' => $format.'0001', 'created_at' => now()->subDays(4)],
            ['receipt_number' => $format.'0002', 'created_at' => now()->subDays(2)]
        )
        ->count(2)
        ->create();

    expect($this->generator->execute($this->branch))
        ->toBe($format.'0003');
});

it('generate purchase 3rd time in the next day', function () {

    $format = generatePrefix($this->prefix, now());

    OrderFactory::new()
        ->for($this->branch)
        ->sequence(
            ['receipt_number' => $format.'0001', 'created_at' => now()->subDays(4)],
            ['receipt_number' => $format.'0002', 'created_at' => now()->subDays(2)]
        )
        ->count(2)
        ->create();

    travelTo(now()->addDay());

    $format = generatePrefix($this->prefix, now());

    OrderFactory::new()
        ->for($this->branch)
        ->sequence(
            ['receipt_number' => $format.'0001', 'created_at' => now()->subDays(4)],
            ['receipt_number' => $format.'0002', 'created_at' => now()->subDays(2)]
        )
        ->count(2)
        ->create();

    expect($this->generator->execute($this->branch))
        ->toBe($format.'0003');
});

function generatePrefix(string $prefix, Carbon $now): string
{
    $y = $now->format('y');
    $m = $now->format('m');
    $d = $now->format('d');

    return sprintf('%s%s%s%s%s', $prefix, 'BRANCH', $y, $m, $d);
}
