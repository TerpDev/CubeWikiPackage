<?php

namespace TerpDev\CubeWikiPackage\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

class KnowledgeBase extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'WikiCube';

    protected static \UnitEnum|string|null $navigationGroup = 'Knowledge Base';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'WikiCube Knowledge Base';

    protected string $view = 'cubewikipackage::filament.pages.knowledge-base';

    public ?string $tokenApiToken = null;
    public ?array $knowledgeBaseData = null;
    public ?string $apiToken = null;
    public ?int $selectedApplicationId = null;

    public function mount(): void
    {
        // Check if token and application_id are in session (from DocumentationButton)
        $sessionToken = session('cubewiki_token');
        $sessionAppId = session('cubewiki_application_id');

        if ($sessionToken) {
            $this->tokenApiToken = $sessionToken;
            $this->apiToken = $sessionToken;

            try {
                $service = app(WikiCubeApiService::class);
                $this->knowledgeBaseData = $service->fetchKnowledgeBase($sessionToken);

                // Set the selected application if provided
                if ($sessionAppId) {
                    $this->selectedApplicationId = (int) $sessionAppId;
                }

                // Clear session data after loading
                session()->forget(['cubewiki_token', 'cubewiki_application_id']);
            } catch (\Throwable $e) {
                Notification::make()
                    ->danger()
                    ->title('Failed to load Knowledge Base')
                    ->body($e->getMessage())
                    ->persistent()
                    ->send();
            }
        }
    }

    public function loadKnowledgeBase(): void
    {
        $this->validate([
            'tokenApiToken' => ['required', 'string'],
        ]);

        try {
            $service = app(WikiCubeApiService::class);

            $this->knowledgeBaseData = $service->fetchKnowledgeBase($this->tokenApiToken);
            $this->apiToken = $this->tokenApiToken;

            // Don't auto-select - let user choose from dropdown
            $this->selectedApplicationId = null;

            Notification::make()
                ->success()
                ->title('Knowledge Base loaded successfully')
                ->body('Connected to tenant: ' . ($this->knowledgeBaseData['tenant']['name'] ?? 'Unknown'))
                ->send();
        } catch (\Throwable $e) {
            $this->knowledgeBaseData = null;

            Notification::make()
                ->danger()
                ->title('Failed to load Knowledge Base')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    public function updatedSelectedApplicationId(): void
    {
        // Optionally reload data for specific application
        if ($this->apiToken && $this->selectedApplicationId) {
            try {
                $service = app(WikiCubeApiService::class);
                $this->knowledgeBaseData = $service->fetchKnowledgeBase($this->apiToken, $this->selectedApplicationId);
            } catch (\Throwable $e) {
                Notification::make()
                    ->danger()
                    ->title('Failed to load application data')
                    ->body($e->getMessage())
                    ->send();
            }
        }
    }

    public function getApplicationOptions(): array
    {
        if (!$this->knowledgeBaseData) {
            return [];
        }

        $applications = $this->knowledgeBaseData['applications'] ?? [];
        $options = [];

        foreach ($applications as $app) {
            $options[$app['id']] = $app['name'];
        }

        return $options;
    }

    public function getFilteredApplications(): array
    {
        if (!$this->knowledgeBaseData) {
            return [];
        }

        $applications = $this->knowledgeBaseData['applications'] ?? [];

        // If no application is selected, show nothing (user must select)
        if (!$this->selectedApplicationId) {
            return [];
        }

        // Filter to show only selected application
        return array_filter($applications, fn($app) => $app['id'] === $this->selectedApplicationId);
    }

    protected function getHeaderActions(): array
    {
        if (!$this->knowledgeBaseData) {
            return [];
        }

        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    if ($this->apiToken) {
                        $service = app(WikiCubeApiService::class);
                        $service->clearCache($this->apiToken, $this->selectedApplicationId);

                        try {
                            $this->knowledgeBaseData = $service->fetchKnowledgeBase(
                                $this->apiToken,
                                $this->selectedApplicationId
                            );

                            Notification::make()
                                ->success()
                                ->title('Knowledge Base refreshed')
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Failed to refresh')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }
                }),
            Action::make('clearToken')
                ->label('Change Token')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Change API Token')
                ->modalDescription('Are you sure you want to disconnect and enter a different token?')
                ->action(function () {
                    $this->apiToken = null;
                    $this->knowledgeBaseData = null;
                    $this->tokenApiToken = null;
                    $this->selectedApplicationId = null;

                    Notification::make()
                        ->info()
                        ->title('Token cleared')
                        ->body('Please enter a new API token.')
                        ->send();
                }),
        ];
    }
}

