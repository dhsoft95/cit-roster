<?php

namespace App\Providers\Filament;

use CWSPS154\AppSettings\AppSettingsPlugin;
use CWSPS154\UsersRolesPermissions\UsersRolesPermissionsPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()

            ->colors([
                'primary' => [
                    50 => '#f9f5e2',
                    100 => '#f3ebc4',
                    200 => '#e9dea8',
                    300 => '#e1d28c',
                    400 => '#dcc770',
                    500 => '#d7bc61',
                    600 => '#c9ab48',
                    700 => '#b0943d',
                    800 => '#8a7332',
                    900 => '#645225',
                    950 => '#3a3015',
                ],
                'secondary' => [
                    50 => '#e6ebf5',
                    100 => '#ccd7eb',
                    200 => '#99afd7',
                    300 => '#6687c3',
                    400 => '#335faf',
                    500 => '#00379b',
                    600 => '#032c76',
                    700 => '#032362',
                    800 => '#031a54', // Navy blue color as requested
                    900 => '#02103f',
                    950 => '#010a2a',
                ],
                'danger' => Color::Rose,
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->brandName('Admin Dashboard')
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/favicon.ico'))
            ->darkMode(true)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins(plugins: [
                AppSettingsPlugin::make()
                    ->canAccess(function () {
                        return true;
                    })
                    ->canAccessAppSectionTab(function () {
                        return true;
                    })
                    ->appAdditionalField([]),
//                UsersRolesPermissionsPlugin::make()
                \MixCode\FilamentMulti2fa\FilamentMulti2faPlugin::make()
                    ->forceSetup2fa(),
            ])
            ->navigationGroups([
                'Content',
                'Settings',
                'User Management',
            ])
            ->databaseNotifications()
            ->databaseTransactions();
    }
}
