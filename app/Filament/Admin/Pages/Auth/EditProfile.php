<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Auth;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Lloricode\Timezone\Timezone;

class EditProfile extends BaseEditProfile
{
    #[\Override]
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent()
                    ->disabled(fn () => Filament::auth()->user()?->isZeroDayAdmin() ?? true),
                $this->getEmailFormComponent()
                    ->disabled(),

                Forms\Components\Select::make('timezone')
                    ->translateLabel()
                    ->options(Timezone::generateList())
                    ->required()
                    ->rule('timezone')
                    ->searchable()
                    ->default(config('default.timezone')),
            ]);
    }
}
