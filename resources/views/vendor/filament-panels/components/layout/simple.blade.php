@php
    use Filament\Support\Enums\Width;

    $livewire ??= null;

    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= (filament()->getSimplePageMaxContentWidth() ?? Width::Large);

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    @props([
        'after' => null,
        'heading' => null,
        'subheading' => null,
    ])

    <!-- Inject Public Site CSS & JS for Header/Footer styles -->
    @vite(['resources/css/public-site.css', 'resources/js/public-site.js'])

    <div class="public-site flex flex-col min-h-screen bg-phc-surface">
        <x-public.header />

        <!-- Filament Simple Layout Wrapper -->
        <div class="fi-simple-layout flex-1 flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            @if (($hasTopbar ?? true) && filament()->auth()->check())
                <a href="#fi-main-content" class="fi-skip-link fi-sr-only">
                    {{ __('filament-panels::layout.skip_to_content.label') }}
                </a>
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_START, scopes: $renderHookScopes) }}

            @if (($hasTopbar ?? true) && filament()->auth()->check())
                <div class="fi-simple-layout-header">
                    @if (filament()->hasDatabaseNotifications())
                        @livewire(filament()->getDatabaseNotificationsLivewireComponent(), [
                            'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                            'position' => \Filament\Enums\DatabaseNotificationsPosition::Topbar,
                        ])
                    @endif

                    @if (filament()->hasUserMenu())
                        @livewire(Filament\Livewire\SimpleUserMenu::class)
                    @endif
                </div>
            @endif

            <div class="fi-simple-main-ctn w-full flex justify-center">
                <main
                    id="fi-main-content"
                    tabindex="-1"
                    @class([
                        'fi-simple-main w-full max-w-md',
                        ($maxContentWidth instanceof Width) ? "fi-width-{$maxContentWidth->value}" : $maxContentWidth,
                    ])
                >
                    {{ $slot }}
                </main>
            </div>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_END, scopes: $renderHookScopes) }}
        </div>

        <x-public.footer />
    </div>
</x-filament-panels::layout.base>
