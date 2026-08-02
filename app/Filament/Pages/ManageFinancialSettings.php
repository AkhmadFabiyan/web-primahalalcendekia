<?php

namespace App\Filament\Pages;

use App\Modules\Settings\Services\FinancialSettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;

class ManageFinancialSettings extends Page implements HasForms
{
    use InteractsWithForms;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    public static function getNavigationLabel(): string
    {
        return 'Financial Settings';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Financial Settings';
    }

    protected string $view = 'filament.pages.manage-financial-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Gate::allows('financial-settings.view');
    }

    public function mount(FinancialSettingsService $settingsService): void
    {
        $this->form->fill([
            'invoice_due_days' => $settingsService->get('invoice_due_days', 14),
            'company_name' => $settingsService->get('company_name', 'Prima Halal Cendekia'),
            'company_address' => $settingsService->get('company_address', ''),
            'company_phone' => $settingsService->get('company_phone', ''),
            'company_email' => $settingsService->get('company_email', ''),
            'bank_name' => $settingsService->get('bank_name', ''),
            'bank_account_number' => $settingsService->get('bank_account_number', ''),
            'bank_account_holder' => $settingsService->get('bank_account_holder', ''),
            'invoice_footer' => $settingsService->get('invoice_footer', ''),
            'receipt_footer' => $settingsService->get('receipt_footer', ''),
            'tax_enabled' => $settingsService->get('tax_enabled', false),
            'tax_rate' => $settingsService->get('tax_rate', 11),
            'invoice_template_version' => $settingsService->get('invoice_template_version', '1'),
            'receipt_template_version' => $settingsService->get('receipt_template_version', '1'),
            'invoice_reminder_days' => $settingsService->get('invoice_reminder_days', 3),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')->required(),
                        Forms\Components\Textarea::make('company_address')->required(),
                        Forms\Components\TextInput::make('company_phone')->tel()->required(),
                        Forms\Components\TextInput::make('company_email')->email()->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Bank Account (Protected)')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name'),
                        Forms\Components\TextInput::make('bank_account_number'),
                        Forms\Components\TextInput::make('bank_account_holder'),
                    ])->columns(3),
                Forms\Components\Section::make('Invoice Rules')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_due_days')->numeric()->required()->label('Default Invoice Due Days'),
                        Forms\Components\Toggle::make('tax_enabled')->label('Enable Tax (PPN)'),
                        Forms\Components\TextInput::make('tax_rate')->numeric()->suffix('%')->label('Tax Rate'),
                        Forms\Components\TextInput::make('invoice_reminder_days')->numeric()->label('Invoice Reminder Before (Days)'),
                    ])->columns(2),
                Forms\Components\Section::make('Templates')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_template_version')->default('1')->disabled(),
                        Forms\Components\TextInput::make('receipt_template_version')->default('1')->disabled(),
                        Forms\Components\Textarea::make('invoice_footer')->label('Invoice Footer Note'),
                        Forms\Components\Textarea::make('receipt_footer')->label('Receipt Footer Note'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(FinancialSettingsService $settingsService): void
    {
        abort_unless(Gate::allows('financial-settings.update'), 403);

        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            $settingsService->set($key, $value);
        }

        activity()
            ->causedBy(auth()->user())
            ->event('updated')
            ->log('FINANCIAL_SETTINGS_UPDATED');

        Notification::make()
            ->title('Saved successfully')
            ->success()
            ->send();
    }
}
