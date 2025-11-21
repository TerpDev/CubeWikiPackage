<?php

namespace TerpDev\CubeWikiPackage\Filament\Pages;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

class KnowledgeBase extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'cubewikipackage::filament.pages.knowledge-base';

    public ?string $selectedPageContentHtml = null;

    public ?array $knowledgeBaseData = null;

    public ?string $apiToken = null;

    public ?int $selectedApplicationId = null;

    public ?int $selectedCategoryId = null;

    public ?int $selectedPageId = null;

    public ?string $selectedPageTitle = null;

    public array $pageHeadings = [];

    public function mount(): void
    {
        $sessionToken = session('cubewiki_token');
        $sessionAppId = session('cubewiki_application_id');
        $sessionAppName = session('cubewiki_application_name');

        if (! $sessionToken) {
            Notification::make()
                ->info()
                ->title('Geen API-token gevonden')
                ->body('Open eerst de documentatie button.')
                ->send();

            return;
        }

        $this->apiToken = $sessionToken;

        $service = app(WikiCubeApiService::class);
        $this->knowledgeBaseData = $service->fetchKnowledgeBase($sessionToken, null);

        // URL-parameters
        $appParam = request()->query('app');           // naam of id
        $qCat = (int) request()->query('cat', 0);  // categorie-id
        $qPage = (int) request()->query('page', 0); // page-id

        $resolvedAppId = null;

        if (! empty($appParam)) {
            if (is_numeric($appParam)) {
                $resolvedAppId = (int) $appParam;
            } else {
                $resolvedAppId = $this->getAppIdByName($appParam);
            }
        } elseif ($sessionAppId && $this->appExistsById((int) $sessionAppId)) {
            $resolvedAppId = (int) $sessionAppId;
        } elseif ($sessionAppName && ($id = $this->getAppIdByName($sessionAppName))) {
            $resolvedAppId = $id;
        }

        if ($resolvedAppId) {
            $this->selectApplication($resolvedAppId);
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
        if ($this->selectedPageContentHtml) {
            return $schema
                ->schema([
                    // Breadcrumbs - alleen tonen als er een pagina actief is
                    Placeholder::make('breadcrumbs')
                        ->hiddenLabel()
                        ->columnSpan(['md' => 4, 'lg' => 4])
                        ->visible(fn () => (bool) $this->selectedPageId)
                        ->content(fn () => view('filament::components.breadcrumbs', [
                            'breadcrumbs' => $this->getLocalBreadcrumbs(),
                        ])),

                    // Linkerkolom: content
                    Placeholder::make('page_content')
                        ->hiddenLabel()
                        ->columnSpan(['md' => 3, 'lg' => 3])
                        ->content(fn () => new HtmlString(
                            '<div class="prose dark:prose-invert pt-4">'
                            .$this->selectedPageContentHtml.
                            '</div>'
                        )),

                    // Rechterkolom: TOC
                    Placeholder::make('toc')
                        ->hiddenLabel()
                        ->columnSpan(['md' => 1, 'lg' => 1])
                        ->visible(fn () => count($this->pageHeadings) > 0)
                        ->content(fn () => view('cubewikipackage::components.toc', [
                            'tocHtml' => $this->renderTocHtml(),
                        ])),
                ])
                ->columns([
                    'md' => 4,
                    'lg' => 4,
                ])
                ->statePath('formData');
        }

        // Geen pagina gekozen
        return $schema
            ->schema([
                Placeholder::make('welcome')
                    ->hiddenLabel()
                    ->content(fn () => new HtmlString('
                        <div class="text-center py-12">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                Choose a page to read
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400">
                                Use the sidebar to browse.
                            </p>
                        </div>
                    ')),
            ])
            ->statePath('formData');
    }

    public function selectApplication(?int $appId): void
    {
        $this->selectedApplicationId = $appId ?: null;
        $this->selectedCategoryId = null;
        $this->selectedPageId = null;
        $this->selectedPageTitle = null;
        $this->selectedPageContentHtml = null;
        $this->pageHeadings = [];

        // Sessie ook bijwerken (handig i.c.m. Sidebar / opnieuw openen)
        if ($this->selectedApplicationId) {
            $app = $this->getApplicationById($this->selectedApplicationId);

            session([
                'cubewiki_application_id' => $this->selectedApplicationId,
                'cubewiki_application_name' => $app['name'] ?? null,
            ]);
        } else {
            session([
                'cubewiki_application_id' => null,
                'cubewiki_application_name' => null,
            ]);
        }
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId ?: null;
        $this->selectedPageId = null;
        $this->selectedPageTitle = null;
        $this->selectedPageContentHtml = null;
        $this->pageHeadings = [];
    }

    public function openPage(int $pageId): void
    {
        $this->selectedPageId = $pageId;

        $page = $this->findPageById($pageId);

        if (! $page) {
            return;
        }

        $this->selectedPageTitle = $page['title'] ?? 'Page';
        $rawHtml = (string) ($page['content_html'] ?? '');
        $this->selectedPageContentHtml = trim($rawHtml) !== ''
            ? $rawHtml
            : '<p class="text-gray-500">No content available.</p>';

        $this->processHeadings();

        Log::debug('cubewiki.headings', $this->pageHeadings);
    }

    protected function processHeadings(): void
    {
        $this->pageHeadings = [];

        if (! $this->selectedPageContentHtml) {
            return;
        }

        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$this->selectedPageContentHtml);

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');

        foreach ($nodes as $node) {
            $text = trim($node->textContent);

            if ($text === '') {
                continue;
            }

            $level = (int) substr($node->nodeName, 1);

            // Work with DOMElement only for attribute operations
            if ($node instanceof \DOMElement) {
                $id = $node->getAttribute('id') ?: Str::slug($text);

                if (! $node->hasAttribute('id')) {
                    $node->setAttribute('id', $id);
                }

                $existingClass = $node->getAttribute('class');
                $newClass = trim($existingClass.' scroll-mt-24');
                $node->setAttribute('class', $newClass);
            } else {
                $id = Str::slug($text);
            }

            $this->pageHeadings[] = [
                'text' => $text,
                'level' => $level,
                'id' => $id,
            ];
        }

        $body = $dom->getElementsByTagName('body')->item(0);

        if ($body) {
            $innerHtml = '';
            foreach ($body->childNodes as $child) {
                $innerHtml .= $dom->saveHTML($child);
            }

            $this->selectedPageContentHtml = $innerHtml;
        }
    }

    protected function renderTocHtml(): string
    {
        if (! count($this->pageHeadings)) {
            return '';
        }

        $html = '<div class="py-2 text-sm space-y-2">';
        $html .= '  <div class="flex flex-col gap-1.5">';

        foreach ($this->pageHeadings as $heading) {
            $html .= sprintf(
                '<a href="#%s" class="block no-underline dark:text-gray-200 text-gray-700 hover:bg-white/5 p-2 rounded-lg duration-300 text-md font-medium">%s</a>',
                e($heading['id']),
                e($heading['text'])
            );
        }

        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }

    public function getSelectedApplication(): ?array
    {
        if (! $this->selectedApplicationId) {
            return null;
        }

        return collect($this->knowledgeBaseData['applications'] ?? [])
            ->firstWhere('id', $this->selectedApplicationId);
    }

    public function getCategoriesForSelectedApp(): array
    {
        $app = $this->getSelectedApplication();

        if (! $app) {
            return [];
        }

        return $app['categories'] ?? [];
    }

    public function getSelectedCategory(): ?array
    {
        if (! $this->selectedCategoryId) {
            return null;
        }

        return collect($this->getCategoriesForSelectedApp())
            ->firstWhere('id', $this->selectedCategoryId);
    }

    protected function findPageById(int $pageId): ?array
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

    // ---- Helpers om op naam/id te kunnen resolven ----

    protected function getAppIdByName(string $name): ?int
    {
        foreach ($this->knowledgeBaseData['applications'] ?? [] as $app) {
            if (isset($app['name']) && strcasecmp($app['name'], $name) === 0) {
                return isset($app['id']) ? (int) $app['id'] : null;
            }
        }

        return null;
    }

    protected function appExistsById(int $id): bool
    {
        foreach ($this->knowledgeBaseData['applications'] ?? [] as $app) {
            if (isset($app['id']) && (int) $app['id'] === $id) {
                return true;
            }
        }

        return false;
    }

    protected function getApplicationById(int $id): ?array
    {
        foreach ($this->knowledgeBaseData['applications'] ?? [] as $app) {
            if (isset($app['id']) && (int) $app['id'] === $id) {
                return $app;
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
        $breadcrumbs = [];

        if ($this->selectedApplicationId && ($app = $this->getSelectedApplication())) {
            // Gebruik NAAM in de URL i.p.v. id
            $appName = $app['name'] ?? 'Applicatie';

            $breadcrumbs[static::getUrl([
                'app' => $appName,
            ])] = $appName;
        }

        if ($this->selectedCategoryId && ($category = $this->getSelectedCategory())) {
            $app = $this->getSelectedApplication();
            $appName = $app['name'] ?? 'Applicatie';

            $breadcrumbs[static::getUrl([
                'app' => $appName,
                'cat' => $category['id'],
            ])] = $category['name'] ?? 'Categorie';
        }

        if ($this->selectedPageId && $this->selectedPageTitle) {
            $breadcrumbs['#'] = $this->selectedPageTitle;
        }

        return $breadcrumbs;
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }
}
