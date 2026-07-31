<?php

namespace App\Filament\Pages;

use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class SystemPreference extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static string $settings = GeneralSettings::class;

    protected static ?string $title = 'System Preferences';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(\App\Enums\Permission::ViewSettings->value) ?? false;
    }

    public ?array $data = [];

    protected string $view = 'filament.pages.system-preference';

    public function mount(): void
    {
        $this->form->fill($this->mutateFormDataBeforeFill([]));
    }

    public function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->schema([
                Section::make('General')
                    ->schema([
                        Select::make('general.display_timezone')
                            ->label('Timezone')
                            ->options(array_combine(\DateTimeZone::listIdentifiers(), \DateTimeZone::listIdentifiers()))
                            ->searchable()
                            ->required()
                            ->rules(['timezone']),
                        Select::make('general.locale')
                            ->label('Language')
                            ->options([
                                'id' => 'Bahasa Indonesia',
                                'en' => 'English',
                            ])
                            ->required(),
                        TextInput::make('general.date_format')
                            ->label('Date Format')
                            ->required()
                            ->default('d M Y'),
                    ])->columns(2),

                Section::make('Company Profile')
                    ->schema([
                        TextInput::make('company.company_name')
                            ->label('Company Name')
                            ->required(),
                        TextInput::make('company.phone')
                            ->label('Phone Number')
                            ->tel(),
                        TextInput::make('company.email')
                            ->label('Email Address')
                            ->email(),
                        TextInput::make('company.address')
                            ->label('Address')
                            ->columnSpanFull(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $generalSettings = app(GeneralSettings::class);
        $companySettings = app(CompanySettings::class);
        
        $data['general'] = [
            'display_timezone' => $generalSettings->display_timezone,
            'locale' => $generalSettings->locale,
            'date_format' => $generalSettings->date_format,
        ];
        
        $data['company'] = [
            'company_name' => $companySettings->company_name,
            'address' => $companySettings->address,
            'phone' => $companySettings->phone,
            'email' => $companySettings->email,
        ];
        return $data;
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can(\App\Enums\Permission::ManageSettings->value), 403);
        
        $data = $this->form->getState();
        
        // Activity log before save
        $generalSettings = app(GeneralSettings::class);
        $companySettings = app(CompanySettings::class);
        
        $oldValues = [
            'general' => $generalSettings->toArray(),
            'company' => $companySettings->toArray(),
        ];
        
        activity()
            ->causedBy(auth()->user())
            ->event('updated')
            ->withProperties([
                'old' => $oldValues,
                'attributes' => $data,
            ])
            ->log('System settings updated');

        $generalSettings->display_timezone = $data['general']['display_timezone'];
        $generalSettings->locale = $data['general']['locale'];
        $generalSettings->date_format = $data['general']['date_format'];
        $generalSettings->save();

        $companySettings->company_name = $data['company']['company_name'];
        $companySettings->address = $data['company']['address'] ?? '';
        $companySettings->phone = $data['company']['phone'] ?? '';
        $companySettings->email = $data['company']['email'] ?? '';
        $companySettings->save();

        \Illuminate\Support\Facades\Cache::flush();

        \Filament\Notifications\Notification::make()
            ->title('Settings saved successfully.')
            ->success()
            ->send();
    }
}
