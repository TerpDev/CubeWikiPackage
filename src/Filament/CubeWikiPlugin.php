<?php

namespace TerpDev\CubeWikiPackage\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use TerpDev\CubeWikiPackage\Filament\Pages\KnowledgeBase;

class CubeWikiPlugin implements Plugin
{
    protected bool $hasNavigationGroup = true;
    protected string $navigationGroup = 'Knowledge Base';
    protected ?int $navigationSort = 99;
    protected bool $isEnabled = true;

    public function getId(): string
    {
        return 'cubewiki';
    }

    public function register(Panel $panel): void
    {
        if (!$this->isEnabled) {
            return;
        }

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
        /** @var static */
        return filament(app(static::class)->getId());
    }

    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;
        return $this;
    }

    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;
        return $this;
    }

    public function withoutNavigationGroup(): static
    {
        $this->hasNavigationGroup = false;
        return $this;
    }

    public function enabled(bool $enabled = true): static
    {
        $this->isEnabled = $enabled;
        return $this;
    }

    public function disabled(): static
    {
        return $this->enabled(false);
    }

    public function getNavigationGroup(): ?string
    {
        return $this->hasNavigationGroup ? $this->navigationGroup : null;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }
}

