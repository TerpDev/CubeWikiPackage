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

    public function loadKnowledgeBase(): void
    {
        $this->validate([
            'tokenApiToken' => ['required', 'string'],
        ]);

        try {
            $service = app(WikiCubeApiService::class);

            $this->knowledgeBaseData = $service->fetchKnowledgeBase($this->tokenApiToken);
            $this->apiToken = $this->tokenApiToken;

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
                        $service->clearCache($this->apiToken);
                        $this->loadKnowledgeBase();
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

                    Notification::make()
                        ->info()
                        ->title('Token cleared')
                        ->body('Please enter a new API token.')
                        ->send();
                }),
        ];
    }
}


