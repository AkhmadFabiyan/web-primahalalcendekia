<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;

class EditProfile extends \Filament\Auth\Pages\EditProfile
{
    /**
     * @return array<int | string, string | \Filament\Forms\Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getPhoneFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(! static::isSimple()),
            ),
        ];
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label(__('Phone'))
            ->tel()
            ->maxLength(255);
    }
}
