<?php

namespace TerpDev\CubeWikiPackage\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class CubeWikiPlugin implements Plugin
{
    public static string $cubeWikiPanelPath = 'cubewiki';
    public static string $buttonLabel = 'Documentation';
    public static string $buttonIcon = 'heroicon-o-book-open';

    public function getId(): string
    {
        return 'cubewiki';
    }

    public function register(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => Blade::render('<livewire:cubewikipackage-documentation-button />')
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return new static();
    }

    public function cubeWikiPanelPath(string $path): static
    {
        static::$cubeWikiPanelPath = $path;
        return $this;
    }

    public function buttonLabel(string $label): static
    {
        static::$buttonLabel = $label;
        return $this;
    }

    public function buttonIcon(string $icon): static
    {
        static::$buttonIcon = $icon;
        return $this;
    }
}

