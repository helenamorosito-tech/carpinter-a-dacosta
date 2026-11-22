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
                'orange' => Color::Orange,
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
                        .fi-edit-heading {
                            display: flex;
                            flex-direction: column;
                            align-items: flex-start;
                            gap: 0.4rem;
                        }

                        /* El título largo no se aplasta a una palabra por línea junto a los botones */
                        @media (min-width: 640px) {
                            .fi-header > div:first-child {
                                flex: 1 1 22rem !important;
                                min-width: min(100%, 22rem) !important;
                            }
                        }

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

                        /* Tipo de mueble: amarillo claro, y ámbar oscuro en modo oscuro */
                        .fi-section-furniture-type > .fi-section:not(.fi-aside) {
                            background-color: #fff4cc !important;
                            --tw-ring-color: rgb(202 160 48 / 0.55) !important;
                        }

                        .fi-section-furniture-type > .fi-section.fi-section-has-header:not(.fi-collapsed) > .fi-section-content-ctn {
                            border-top-color: #ead48a !important;
                        }

                        .fi-section-furniture-type .fi-furniture-type-selected {
                            margin-inline-start: 0.85rem;
                            font-weight: 600;
                            color: #9a6700;
                        }

                        .dark .fi-section-furniture-type > .fi-section:not(.fi-aside) {
                            background-color: #3f3414 !important;
                            --tw-ring-color: rgb(214 176 70 / 0.4) !important;
                        }

                        .dark .fi-section-furniture-type > .fi-section.fi-section-has-header:not(.fi-collapsed) > .fi-section-content-ctn {
                            border-top-color: rgb(214 176 70 / 0.35) !important;
                        }

                        .dark .fi-section-furniture-type .fi-section-header-heading {
                            color: #fff8e8 !important;
                        }

                        .dark .fi-section-furniture-type .fi-section-header-heading .fi-furniture-type-selected {
                            color: #ffe08a !important;
                        }

                        /* Balda maletero: gris claro, y gris más claro que el fondo en modo oscuro */
                        .fi-section-trunk > .fi-section:not(.fi-aside) {
                            background-color: #e8eaed !important;
                            --tw-ring-color: rgb(156 163 175 / 0.7) !important;
                        }

                        .fi-section-trunk > .fi-section.fi-section-has-header:not(.fi-collapsed) > .fi-section-content-ctn {
                            border-top-color: #d1d5db !important;
                        }

                        .dark .fi-section-trunk > .fi-section:not(.fi-aside) {
                            background-color: #3a4150 !important;
                            --tw-ring-color: rgb(186 196 210 / 0.35) !important;
                        }

                        .dark .fi-section-trunk > .fi-section.fi-section-has-header:not(.fi-collapsed) > .fi-section-content-ctn {
                            border-top-color: rgb(186 196 210 / 0.28) !important;
                        }

                        .dark .fi-section-trunk .fi-section-header-heading {
                            color: #f4f6f8 !important;
                        }

                        .dark .fi-section-trunk .fi-section-header-description {
                            color: #d5dbe3 !important;
                        }

                        /* Baldas: verde claro, y verde oscuro en modo oscuro */
                        .fi-section-shelves > .fi-section:not(.fi-aside) {
                            background-color: #e5f6e8 !important;
                            --tw-ring-color: rgb(110 176 122 / 0.55) !important;
                        }

                        .fi-section-shelves > .fi-section.fi-section-has-header:not(.fi-collapsed) > .fi-section-content-ctn {
                            border-top-color: #b7dfc0 !important;
                        }

                        .dark .fi-section-shelves > .fi-section:not(.fi-aside) {
                            background-color: #1a3a28 !important;
                            --tw-ring-color: rgb(134 196 150 / 0.38) !important;
                        }

                        .dark .fi-section-shelves > .fi-section.fi-section-has-header:not(.fi-collapsed) > .fi-section-content-ctn {
                            border-top-color: rgb(134 196 150 / 0.28) !important;
                        }

                        .dark .fi-section-shelves .fi-section-header-heading {
                            color: #f3faf5 !important;
                        }

                        .dark .fi-section-shelves .fi-section-header-description {
                            color: #c9e6d1 !important;
                        }

                        .fi-shelf-count {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-width: 1.45rem;
                            height: 1.45rem;
                            margin-inline-start: 0.55rem;
                            padding: 0 0.4rem;
                            border-radius: 999px;
                            font-size: 0.78rem;
                            font-weight: 700;
                            line-height: 1;
                            vertical-align: 0.05rem;
                        }

                        .fi-shelf-count-trunk {
                            background-color: #5f6978;
                            color: #fff;
                        }

                        .dark .fi-shelf-count-trunk {
                            background-color: #d5dbe3;
                            color: #1c2430;
                        }

                        .fi-shelf-count-shelves {
                            background-color: #2f7a46;
                            color: #fff;
                        }

                        .dark .fi-shelf-count-shelves {
                            background-color: #c6efd2;
                            color: #12321c;
                        }

                        /* Cuerpo: gris claro, y gris más claro que el fondo en modo oscuro */
                        .fi-section-body > .fi-section:not(.fi-aside) {
                            background-color: #e8eaed !important;
                            --tw-ring-color: rgb(156 163 175 / 0.7) !important;
                        }

                        .fi-section-body > .fi-section.fi-section-has-header:not(.fi-collapsed) > .fi-section-content-ctn {
                            border-top-color: #d1d5db !important;
                        }

                        .dark .fi-section-body > .fi-section:not(.fi-aside) {
                            background-color: #3a4150 !important;
                            --tw-ring-color: rgb(186 196 210 / 0.35) !important;
                        }

                        .dark .fi-section-body > .fi-section.fi-section-has-header:not(.fi-collapsed) > .fi-section-content-ctn {
                            border-top-color: rgb(186 196 210 / 0.28) !important;
                        }

                        .dark .fi-section-body .fi-section-header-heading {
                            color: #f4f6f8 !important;
                        }

                        .dark .fi-section-body .fi-section-header-description {
                            color: #d5dbe3 !important;
                        }

                        /* Paredes: morado claro, y morado oscuro en modo oscuro */
                        .fi-section-walls > .fi-section:not(.fi-aside) {
                            background-color: #f0e7fb !important;
                            --tw-ring-color: rgb(167 139 202 / 0.55) !important;
                        }

                        .fi-section-walls > .fi-section.fi-section-has-header:not(.fi-collapsed) > .fi-section-content-ctn {
                            border-top-color: #d8c4ef !important;
                        }

                        .dark .fi-section-walls > .fi-section:not(.fi-aside) {
                            background-color: #342848 !important;
                            --tw-ring-color: rgb(196 170 224 / 0.38) !important;
                        }

                        .dark .fi-section-walls > .fi-section.fi-section-has-header:not(.fi-collapsed) > .fi-section-content-ctn {
                            border-top-color: rgb(196 170 224 / 0.28) !important;
                        }

                        .dark .fi-section-walls .fi-section-header-heading {
                            color: #f7f3fc !important;
                        }

                        .dark .fi-section-walls .fi-section-header-description {
                            color: #e4d4f5 !important;
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
