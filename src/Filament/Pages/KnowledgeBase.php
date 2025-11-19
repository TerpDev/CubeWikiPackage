<?php

namespace TerpDev\CubeWikiPackage\Filament\Pages;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

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

    /**
     * Headings van de huidige pagina (voor TOC rechts)
     *
     * @var array<int, array{text:string,level:int,id:string}>
     */
    public array $pageHeadings = [];

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
        // Geen data → melding
        if (!$this->knowledgeBaseData) {
            return $schema
                ->schema([
                    Placeholder::make('no_data')
                        ->hiddenLabel()
                        ->content(fn() => new HtmlString('
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <p>Open de Documentation wizard om een API-token en applicatie te selecteren.</p>
                            </div>
                        ')),
                ])
                ->statePath('formData');
        }

        // Er is een geselecteerde pagina → 2 kolommen: content + TOC
        if ($this->selectedPageContentHtml) {
            return $schema
                ->schema([
                    // Linker kolom: document-content
                    Placeholder::make('page_content')
                        ->hiddenLabel()
                        ->columnSpan(['md' => 3, 'lg' => 3])
                        ->content(fn() => new HtmlString(
                            '<div class="prose dark:prose-invert pt-4">'
                            . $this->selectedPageContentHtml .
                            '</div>'
                        )),

                    // Rechter kolom: TOC
                    Placeholder::make('toc')
                        ->hiddenLabel()
                        ->columnSpan(['md' => 1, 'lg' => 1])
                        ->visible(fn () => count($this->pageHeadings) > 0)
                        ->content(fn () => new HtmlString(
                            '<div class="hidden lg:block border-l border-gray-200 dark:border-gray-800 pl-4 sticky top-24">'
                            . $this->renderTocHtml() .
                            '</div>'
                        )),


                ])
                ->columns([
                    'md' => 4,
                    'lg' => 4,
                ])
                ->statePath('formData');
        }

        // Nog geen pagina gekozen
        return $schema
            ->schema([
                Placeholder::make('welcome')
                    ->hiddenLabel()
                    ->content(fn() => new HtmlString('
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

        if ($this->apiToken && $this->selectedApplicationId) {
            $service = app(WikiCubeApiService::class);
            $this->knowledgeBaseData = $service->fetchKnowledgeBase(
                $this->apiToken,
                $this->selectedApplicationId
            );
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

        $this->selectedPageTitle = $page['title'] ?? 'Untitled';
        $rawHtml = (string)($page['content_html'] ?? '');
        $this->selectedPageContentHtml = trim($rawHtml) !== ''
            ? $rawHtml
            : '<p class="text-gray-500">No content available.</p>';

        // Headings verwerken (id's + TOC)
        $this->processHeadings();

        Log::debug('cubewiki.headings', $this->pageHeadings);
    }

    protected function processHeadings(): void
    {
        $this->pageHeadings = [];

        if (!$this->selectedPageContentHtml) {
            return;
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $this->selectedPageContentHtml);

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');

        foreach ($nodes as $node) {
            $text = trim($node->textContent);

            if ($text === '') {
                continue;
            }

            $level = (int) substr($node->nodeName, 1);
            $id    = $node->getAttribute('id') ?: Str::slug($text);

            if (! $node->hasAttribute('id')) {
                $node->setAttribute('id', $id);
            }

            // bestaand class-attribuut ophalen en scroll-mt-24 toevoegen
            $existingClass = $node->getAttribute('class') ?? '';
            $newClass = trim($existingClass . ' scroll-mt-24');
            $node->setAttribute('class', $newClass);

            $this->pageHeadings[] = [
                'text'  => $text,
                'level' => $level,
                'id'    => $id,
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

        $html  = '<div class="py-2 text-sm dark:text-gray-300 text-gray-700 space-y-2">';
        $html .= '  <div class="flex flex-col gap-1.5">';

        foreach ($this->pageHeadings as $heading) {
            $html .= sprintf(
                '<a href="#%s" class="block no-underline text-gray-200 hover:bg-white/5 p-2 rounded-lg duration-300 text-md font-medium">%s</a>',
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
        return collect($this->knowledgeBaseData['applications'] ?? [])
            ->firstWhere('id', $this->selectedApplicationId);
    }

    public function getCategoriesForSelectedApp(): array
    {
        return $this->getSelectedApplication()['categories'] ?? [];
    }

    public function getSelectedCategory(): ?array
    {
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

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getLocalBreadcrumbs(): array
    {
        $breadcrumbs = [];

        if ($this->selectedApplicationId && ($app = $this->getSelectedApplication())) {
            $breadcrumbs[static::getUrl([
                'app' => $app['id'],
            ])] = $app['name'] ?? 'Applicatie';
        }

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

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return null;
    }
}
