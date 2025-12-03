<?php

namespace TerpDev\CubeWikiPackage\Filament;

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class CubeWikiPlugin implements Plugin
{
    public static string $cubeWikiPanelPath = 'cubewiki';

    public static array $importantPages = [];

    public static bool $documentationDisabled = false;

    public function getId(): string
    {
        return 'cubewiki-plugin';
    }

    public function importantPages(array $pages): static
    {
        self::$importantPages = $pages;

        return $this;
    }

    public function disableDocumentation(bool $disabled = true): static
    {
        self::$documentationDisabled = $disabled;

        return $this;
    }

    public static function documentationEnabled(): bool
    {
        return ! self::$documentationDisabled;
    }

    public static function getImportantPages(): array
    {
        return self::$importantPages;
    }

    public function register(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            $panel->hasTopbar() ? PanelsRenderHook::GLOBAL_SEARCH_AFTER : PanelsRenderHook::SIDEBAR_LOGO_AFTER,
            function (): string {
                $currentPanel = Filament::getCurrentPanel();
                if ($currentPanel?->getId() === self::$cubeWikiPanelPath) {
                    return '';
                }

                return Blade::render('<livewire:cubewikipackage-helpaction />');
            }
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            function (): string {
                $currentPanel = Filament::getCurrentPanel();
                if ($currentPanel?->getId() === self::$cubeWikiPanelPath) {
                    return '';
                }

                return Blade::render('<livewire:cubewikipackage-helpaction host-only="true" />');
            }
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): self
    {
        return new self;
    }
}
