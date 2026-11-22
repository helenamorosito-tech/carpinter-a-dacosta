<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Widgets\LatestFurnitureProjectsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
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
            ->brandName('Carpintería DaCosta')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                AccountWidget::class,
                LatestFurnitureProjectsWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => Blade::render(<<<'BLADE'
                    <style>
                        /* Sidebar marrón (carpintería) */
                        .fi-sidebar,
                        .fi-sidebar-header,
                        .fi-sidebar-header-ctn,
                        .fi-sidebar-nav,
                        .fi-sidebar-footer {
                            background-color: #5c4033 !important;
                        }

                        .fi-sidebar {
                            border-inline-end-color: #4a3228 !important;
                        }

                        .fi-sidebar .fi-logo,
                        .fi-sidebar .fi-sidebar-group-label,
                        .fi-sidebar .fi-sidebar-item-label,
                        .fi-sidebar .fi-sidebar-item-btn,
                        .fi-sidebar .fi-icon-btn,
                        .fi-sidebar .fi-sidebar-group-btn {
                            color: #f5ebe0 !important;
                        }

                        .fi-sidebar .fi-sidebar-item-btn:hover,
                        .fi-sidebar .fi-sidebar-group-btn:hover {
                            background-color: rgba(245, 235, 224, 0.12) !important;
                        }

                        .fi-sidebar .fi-sidebar-item-btn.fi-active,
                        .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
                            background-color: rgba(245, 235, 224, 0.18) !important;
                            color: #fff8f0 !important;
                        }

                        .fi-sidebar .fi-sidebar-group-label {
                            opacity: 0.75;
                            font-weight: 600;
                            letter-spacing: 0.02em;
                        }

                        .fi-sidebar svg {
                            color: #f5ebe0 !important;
                            stroke: currentColor;
                        }

                        /* Wizard de presets: los 4 pasos visibles, sin scroll horizontal en encabezados */
                        .fi-preset-wizard .fi-sc-wizard-header {
                            display: grid !important;
                            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                            grid-auto-flow: unset !important;
                            overflow: visible !important;
                            overflow-x: visible !important;
                        }

                        .fi-preset-wizard .fi-sc-wizard-header-step {
                            display: flex !important;
                            min-width: 0 !important;
                        }

                        .fi-preset-wizard .fi-sc-wizard-header-step-btn {
                            flex-direction: column;
                            align-items: center;
                            justify-content: flex-start;
                            gap: 0.35rem;
                            padding: 0.65rem 0.35rem !important;
                            text-align: center;
                            width: 100%;
                        }

                        .fi-preset-wizard .fi-sc-wizard-header-step-text {
                            width: 100% !important;
                            max-width: 100% !important;
                            justify-items: center !important;
                        }

                        .fi-preset-wizard .fi-sc-wizard-header-step-label {
                            font-size: 0.7rem !important;
                            line-height: 1.15 !important;
                            text-align: center;
                            text-wrap: balance;
                        }

                        .fi-preset-wizard .fi-sc-wizard-header-step-separator {
                            display: none !important;
                        }

                        @media (max-width: 767px) {
                            .fi-preset-wizard .fi-sc-wizard-header {
                                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                            }
                        }

                        /* Mecanizados: azul claro en modo claro, azul oscuro en modo oscuro */
                        .fi-section-machining > .fi-section:not(.fi-aside) {
                            background-color: #e3f2fb !important;
                            --tw-ring-color: rgb(125 176 210 / 0.65) !important;
                        }

                        .fi-section-machining > .fi-section.fi-section-has-header:not(.fi-collapsed) > .fi-section-content-ctn {
                            border-top-color: #b7d6ec !important;
                        }

                        .dark .fi-section-machining > .fi-section:not(.fi-aside) {
                            background-color: #17384f !important;
                            --tw-ring-color: rgb(125 186 220 / 0.38) !important;
                        }

                        .dark .fi-section-machining > .fi-section.fi-section-has-header:not(.fi-collapsed) > .fi-section-content-ctn {
                            border-top-color: rgb(147 197 224 / 0.28) !important;
                        }

                        .dark .fi-section-machining .fi-section-header-heading {
                            color: #f3f8fc !important;
                        }

                        .dark .fi-section-machining .fi-section-header-description {
                            color: #c5d7e6 !important;
                        }
                    </style>
                BLADE),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render(<<<'BLADE'
                    <script>
                        (() => {
                            const scrollToTop = () => {
                                window.scrollTo(0, 0)
                                document.documentElement.scrollTop = 0
                                document.body.scrollTop = 0
                                for (const selector of ['.fi-main-ctn', '.fi-main', '.fi-body', '.fi-layout', '.fi-page']) {
                                    const el = document.querySelector(selector)
                                    if (! el) continue
                                    el.scrollTop = 0
                                    if (typeof el.scrollTo === 'function') el.scrollTo(0, 0)
                                }
                            }

                            const schedule = () => {
                                scrollToTop()
                                queueMicrotask(scrollToTop)
                                requestAnimationFrame(scrollToTop)
                                setTimeout(scrollToTop, 0)
                                setTimeout(scrollToTop, 50)
                                setTimeout(scrollToTop, 150)
                            }

                            window.addEventListener('next-wizard-step', schedule)
                            window.addEventListener('go-to-wizard-step', schedule)
                        })()
                    </script>
                BLADE),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
