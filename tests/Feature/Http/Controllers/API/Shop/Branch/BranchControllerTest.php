<?php

declare(strict_types=1);

use Domain\Shop\Branch\Database\Factories\BranchFactory;
use Domain\Shop\Branch\Models\Branch;

use function Pest\Laravel\assertDatabaseEmpty;
use function Pest\Laravel\getJson;

it('list', function () {

    assertDatabaseEmpty(Branch::class);

    BranchFactory::new()
        ->hasSpecificMedia()
        ->enabled()
        ->count(3)
        ->sequence(
            [
                'name' => 'Branch 1',
                'address' => 'Address 1',
                'phone' => 'Phone 1',
                'email' => 'Email 1',
                'website' => 'Website 1',
            ],
            [
                'name' => 'Branch 2',
                'address' => 'Address 2',
                'phone' => 'Phone 2',
                'email' => 'Email 2',
                'website' => 'Website 2',
            ],
            [
                'name' => 'Branch 3',
                'address' => 'Address 3',
                'phone' => 'Phone 3',
                'email' => 'Email 3',
                'website' => 'Website 3',
            ],
        )
        ->create();

    $response = getJson('api/branches?include=media')
        ->assertOk();

    expect($response)->toMatchSnapshot();
});
