<?php

namespace TerpDev\CubeWikiPackage\Pages;

use Filament\Pages\Page;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

class KnowledgeBase extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-book-open';

    protected string $view = 'cubewikipackage::pages.knowledge-base';

    protected static ?string $navigationLabel = 'Knowledge Base';

    protected static ?string $title = 'Knowledge Base';

    public string $apiToken = '';

    public ?array $knowledgeBaseData = null;

    public ?string $errorMessage = null;

    public bool $isLoading = false;

    public function mount(): void
    {
        // Load token from session if exists
        $this->apiToken = session('wikicube_token', '');

        if ($this->apiToken) {
            $this->loadKnowledgeBase();
        }
    }

    public function loadKnowledgeBase(): void
    {
        $this->validate([
            'apiToken' => 'required|string|min:10',
        ]);

        $this->isLoading = true;
        $this->errorMessage = null;
        $this->knowledgeBaseData = null;

        try {
            $service = app(WikiCubeApiService::class);
            $this->knowledgeBaseData = $service->fetchKnowledgeBase($this->apiToken);

            // Save token to session
            session(['wikicube_token' => $this->apiToken]);

            $this->dispatch('knowledge-base-loaded');
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function clearToken(): void
    {
        $this->apiToken = '';
        $this->knowledgeBaseData = null;
        $this->errorMessage = null;
        session()->forget('wikicube_token');

        if ($this->apiToken) {
            $service = app(WikiCubeApiService::class);
            $service->clearCache($this->apiToken);
        }
    }

    public function refreshData(): void
    {
        if ($this->apiToken) {
            $service = app(WikiCubeApiService::class);
            $service->clearCache($this->apiToken);
            $this->loadKnowledgeBase();
        }
    }
}

