<?php

namespace TerpDev\CubeWikiPackage;

use Filament\Contracts\Plugin;
use Filament\Panel;
use TerpDev\CubeWikiPackage\Pages\KnowledgeBase;

class CubeWikiPackagePlugin implements Plugin
{
    public function getId(): string
    {
        return 'cubewikipackage';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            KnowledgeBase::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}

