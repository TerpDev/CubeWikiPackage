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

    public ?array $allData = null; // all applications
    public ?int $appId = null;
    protected ?string $token = null;
    public ?array $formData = [];

    public function mount(): void
    {
        $this->token = session('cubewiki_token');
        // Prefer query param first, then session value
        $requestedAppId = (int) request()->integer('app');
        $sessionAppId = (int) (session('cubewiki_application_id') ?? 0);
        $initialAppId = $requestedAppId ?: $sessionAppId;

        if (! $this->token) return;

        $service = app(WikiCubeApiService::class);
        $this->allData = $service->fetchKnowledgeBase($this->token, null);
        $this->appId = $initialAppId ?: ($this->getFirstAppId() ?? null);
        $this->form->fill(['appId' => $this->appId]);
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
                ->afterStateUpdated(function ($state) {
                    $this->appId = $state ? (int) $state : null;
                    session(['cubewiki_application_id' => $this->appId]);

                    // Redirect so navigation rebuilds for selected app
                    if ($this->appId) {
                        $this->redirect(url('/cubewiki/knowledge-base?app=' . $this->appId));
                        return;
                    }
                    $this->redirect(url('/cubewiki/knowledge-base'));
                }),
        ])->statePath('formData');
    }

    protected function getFirstAppId(): ?int
    {
        foreach ($this->allData['applications'] ?? [] as $app) {
            return (int) ($app['id'] ?? 0) ?: null;
        }
        return null;
    }

    public function getAppOptions(): array
    {
        $opts = [];
        foreach ($this->allData['applications'] ?? [] as $app) {
            $opts[(int) $app['id']] = $app['name'];
        }
        return $opts;
    }

    public function render()
    {
        return view('cubewikipackage::filament.pages.sidebar');
    }
}
