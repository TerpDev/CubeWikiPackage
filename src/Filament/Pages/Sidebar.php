<?php

namespace TerpDev\CubeWikiPackage\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

class Sidebar extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $allData = null;

    public ?string $appName = null;

    public ?int $appId = null;

    public ?array $formData = [];

    protected ?string $token = null;

    public function mount(): void
    {
        $this->token = session('cubewiki_token')
            ?? config('cubewikipackage.api_token');

        if (! $this->token) {
            return;
        }
        session(['cubewiki_token' => $this->token]);

        $service = app(WikiCubeApiService::class);
        $this->allData = $service->fetchKnowledgeBase($this->token, null);

        $appParam = request()->query('app');
        $sessionAppName = session('cubewiki_application_name');

        $resolvedAppName = null;

        if (! empty($appParam) && $this->appNameExists($appParam)) {
            $resolvedAppName = $this->normalizeAppName($appParam);
        } elseif (! empty($sessionAppName) && $this->appNameExists($sessionAppName)) {
            $resolvedAppName = $this->normalizeAppName($sessionAppName);
        } else {
            $resolvedAppName = null;
        }

        $this->appName = $resolvedAppName;
        $this->appId = $this->appName ? $this->getAppIdByName($this->appName) : null;

        session([
            'cubewiki_application_name' => $this->appName,
            'cubewiki_application_id' => $this->appId,
        ]);

        if ($this->appName) {
            $this->formData = ['appId' => $this->appName];
        }
    }

    protected function appNameExists(string $name): bool
    {
        foreach ($this->allData['applications'] ?? [] as $app) {
            if (isset($app['name']) && strcasecmp($app['name'], $name) === 0) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeAppName(string $name): ?string
    {
        foreach ($this->allData['applications'] ?? [] as $app) {
            if (isset($app['name']) && strcasecmp($app['name'], $name) === 0) {
                return $app['name'];
            }
        }

        return null;
    }

    protected function getAppIdByName(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        foreach ($this->allData['applications'] ?? [] as $app) {
            if (isset($app['name']) && strcasecmp($app['name'], $name) === 0) {
                return isset($app['id']) ? (int) $app['id'] : null;
            }
        }

        return null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('appId')
                ->label('Application')
                ->options($this->getAppOptions())
                ->placeholder('Select application')
                ->live()
                ->searchable()
                ->afterStateUpdated(function ($state): void {
                    $this->appName = $state ?: null;
                    $this->appId = $this->appName ? $this->getAppIdByName($this->appName) : null;

                    session([
                        'cubewiki_application_name' => $this->appName,
                        'cubewiki_application_id' => $this->appId,
                    ]);

                    if ($this->appName) {
                        $this->redirect(url('/cubewiki/knowledge-base?app='.urlencode($this->appName)));

                        return;
                    }
                }),
        ])->statePath('formData');
    }

    public function getAppOptions(): array
    {
        $opts = [];

        foreach ($this->allData['applications'] ?? [] as $app) {
            if (isset($app['name'])) {
                $opts[$app['name']] = $app['name'];
            }
        }

        return $opts;
    }

    public function render()
    {
        return view('cubewikipackage::filament.pages.sidebar');
    }
}
