<?php

namespace TerpDev\CubeWikiPackage\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;
use Illuminate\Support\Str;


class KnowledgeBase extends Page implements HasForms
{
    use InteractsWithForms;

    public ?string $selectedPageContentHtml = null;

    protected $listeners = [
        'wikicube.selectApp' => 'selectApplication',
        'wikicube.selectCat' => 'selectCategory',
        'wikicube.openPage' => 'openPage',
    ];
    protected string $view = 'cubewikipackage::filament.pages.knowledge-base';
    public ?array $knowledgeBaseData = null;
    public ?string $apiToken = null;
    protected static bool $hasPageHeader = false;

    public ?int $selectedApplicationId = null;
    public ?int $selectedCategoryId = null;
    public ?int $selectedPageId = null;
    public ?string $selectedPageTitle = null;


    public function mount(): void
    {
        $sessionToken = session('cubewiki_token');
        $sessionAppId = session('cubewiki_application_id');

        if (!$sessionToken) {
            Notification::make()
                ->info()
                ->title('Geen API-token gevonden')
                ->body('Open de Documentatie-wizard om een token en applicatie te kiezen.')
                ->send();
            return;
        }

        $this->apiToken = $sessionToken;

        $service = app(WikiCubeApiService::class);
        $this->knowledgeBaseData = $service->fetchKnowledgeBase($sessionToken, $sessionAppId);
        $this->selectedApplicationId = $sessionAppId ? (int)$sessionAppId : null;

        $qApp = (int)request()->query('app', 0);
        $qCat = (int)request()->query('cat', 0);
        $qPage = (int)request()->query('page', 0);

        if ($qApp) {
            $this->selectApplication($qApp);
        }
        if ($qCat) {
            $this->selectCategory($qCat);
        }
        if ($qPage) {
            $this->openPage($qPage);
        }
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        if (!$this->knowledgeBaseData) {
            return $schema->schema([
                Placeholder::make('no_data')
                    ->hiddenlabel()
                    ->content(fn() => new HtmlString('
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <p>Open de Documentation wizard om een API-token en applicatie te selecteren.</p>
                        </div>
                    ')),
            ])->statePath('formData');
        }

        if ($this->selectedPageContentHtml) {
            return $schema->schema([
                Placeholder::make('page_content')
                    ->hiddenLabel()
                    ->content(fn() => new HtmlString('<div class="wk-doc">' . $this->selectedPageContentHtml . '</div>'))
            ])->statePath('formData');
        }

        return $schema->schema([
            Placeholder::make('welcome')
                ->hiddenLabel()
                ->content(fn() => new HtmlString('
                    <div class="text-center py-12">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Choose a page to read</h3>
                        <p class="text-gray-500 dark:text-gray-400">Use the sidebar to browse.</p>
                    </div>
                ')),
        ])->statePath('formData');
    }


    public function selectApplication(?int $appId): void
    {
        $this->selectedApplicationId = $appId ?: null;
        $this->selectedCategoryId = null;
        $this->selectedPageId = null;
        $this->selectedPageTitle = null;
        $this->selectedPageContentHtml = null;

        if ($this->apiToken && $this->selectedApplicationId) {

            $service = app(WikiCubeApiService::class);
            $this->knowledgeBaseData = $service->fetchKnowledgeBase($this->apiToken, $this->selectedApplicationId);
        }
    }
    public
    function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId ?: null;
        $this->selectedPageId = null;
        $this->selectedPageTitle = null;
        $this->selectedPageContentHtml = null;
    }
    public
    function openPage(int $pageId): void
    {
        $this->selectedPageId = $pageId;

        $page = $this->findPageById($pageId);
        if (!$page) {
            Notification::make()->danger()->title('Pagina niet gevonden')->send();
            return;
        }

        $this->selectedPageTitle = $page['title'] ?? 'Untitled';
        $rawHtml = (string)($page['content_html'] ?? '');
        $this->selectedPageContentHtml = trim($rawHtml) !== '' ? $rawHtml : '<p class="text-gray-500">No content available.</p>';
    }
    public
    function getSelectedApplication(): ?array
    {
        return collect($this->knowledgeBaseData['applications'] ?? [])
            ->firstWhere('id', $this->selectedApplicationId);
    }
    public
    function getCategoriesForSelectedApp(): array
    {
        return $this->getSelectedApplication()['categories'] ?? [];
    }
    public function getSelectedCategory(): ?array
    {
        return collect($this->getCategoriesForSelectedApp())
            ->firstWhere('id', $this->selectedCategoryId);
    }

    protected
    function findPageById(int $pageId): ?array
    {
        foreach ($this->getCategoriesForSelectedApp() as $cat) {
            foreach ($cat['pages'] ?? [] as $page) {
                if (($page['id'] ?? null) === $pageId) {
                    return $page;
                }
            }
        }
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
    public function getLocalBreadcrumbs(): array
    {

        // Applicatie
        if ($this->selectedApplicationId && ($app = $this->getSelectedApplication())) {
            $breadcrumbs[static::getUrl([
                'app' => $app['id'],
            ])] = $app['name'] ?? 'Applicatie';
        }

        // Categorie
        if ($this->selectedCategoryId && ($category = $this->getSelectedCategory())) {
            $breadcrumbs[static::getUrl([
                'app' => $this->selectedApplicationId,
                'cat' => $category['id'],
            ])] = $category['name'] ?? 'Categorie';
        }

        if ($this->selectedPageId && $this->selectedPageTitle) {
            $breadcrumbs['#'] = $this->selectedPageTitle;
        }

        return $breadcrumbs;
    }

    public
    function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return null;
    }


}
