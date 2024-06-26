<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Auth;

use Database\Seeders\Auth\AdminSeeder;
use Filament\Pages\Auth\Login as BasePage;

/**
 * @todo remove this when in real production site
 */
class Login extends BasePage
{
    #[\Override]
    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'email' => AdminSeeder::demoEmail(),
            'password' => AdminSeeder::demoPassword(),
            'remember' => true,
        ]);
    }
}
