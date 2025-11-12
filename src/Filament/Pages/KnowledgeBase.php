<?php

namespace TerpDev\CubeWikiPackage\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

class KnowledgeBase extends Page implements HasForms
{
    use InteractsWithForms;

    protected $listeners = [
        'wikicube.selectApp' => 'selectApplication',
        'wikicube.selectCat' => 'selectCategory',
        'wikicube.openPage' => 'openPage',
    ];

    public ?array $formData = [];

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'WikiCube';
    protected static string|null|\UnitEnum $navigationGroup = 'Knowledge Base';
    protected static ?int $navigationSort = 99;

    protected string $view = 'cubewikipackage::filament.pages.knowledge-base';

    public ?array $knowledgeBaseData = null;
    public ?string $apiToken = null;

    public ?int $selectedApplicationId = null;
    // Explicitly declare Livewire state properties to avoid dynamic property deprecation warnings
    public ?int $selectedCategoryId = null;
    public ?int $selectedPageId = null;
    public ?string $selectedPageTitle = null;
    public ?string $selectedPageContent = null;
    public ?string $selectedPageContentHtml = null;

    public static function getNavigationLabel(): string
    {
        return 'WikiCube Knowledge Base';
    }

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

        // If query params present, open that page immediately
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

        // Note: do NOT auto-open a page when only app is provided; only pages should display content.
    }


    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        // No knowledge base data
        if (!$this->knowledgeBaseData) {
            return $schema->schema([
                Placeholder::make('no_data')
                    ->label('Connect to WikiCube')
                    ->content(fn() => new HtmlString('
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <p>Open de Documentation wizard in de admin panel om een API token en applicatie te selecteren.</p>
                            <p class="mt-2">Klik op de "Documentation" knop om te beginnen.</p>
                        </div>
                    '))
            ])->statePath('formData');
        }

        // Page content is loaded
        if ($this->selectedPageContentHtml) {
            return $schema->schema([
                Placeholder::make('page_content')
                    ->label('')
                    ->content(fn() => new HtmlString(
                        '<div class="prose prose-lg max-w-none dark:prose-invert">' .
                            $this->selectedPageContentHtml .
                        '</div>'
                    ))
            ])->statePath('formData');
        }

        // Welcome message
        return $schema->schema([
            Placeholder::make('welcome')
                ->label('')
                ->content(fn() => new HtmlString('
                    <div class="text-center py-12">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                            Choose a page to read
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400">
                            Use the sidebar navigation on the left to browse through applications, categories, and pages.
                        </p>
                    </div>
                '))
        ])->statePath('formData');
    }


    public function updatedSelectedApplicationId(): void
    {
        $this->selectApplication($this->selectedApplicationId);
    }

    public function selectApplication(?int $appId): void
    {
        $this->selectedApplicationId = $appId ?: null;
        $this->selectedCategoryId = null;
        $this->selectedPageId = null;
        $this->selectedPageTitle = null;
        $this->selectedPageContent = null;
        $this->selectedPageContentHtml = null;

        if ($this->apiToken && $this->selectedApplicationId) {
            try {
                $service = app(WikiCubeApiService::class);
                $this->knowledgeBaseData = $service->fetchKnowledgeBase($this->apiToken, $this->selectedApplicationId);
            } catch (\Throwable $e) {
                Notification::make()
                    ->danger()
                    ->title('Fout bij laden')
                    ->body($e->getMessage())
                    ->send();
            }
        }
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId ?: null;
        $this->selectedPageId = null;
        $this->selectedPageTitle = null;
        $this->selectedPageContent = null;
        $this->selectedPageContentHtml = null;
    }

    public function openPage(int $pageId): void
    {
        $this->selectedPageId = $pageId;

        $page = $this->findPageById($pageId);
        if (!$page) {
            Notification::make()
                ->danger()
                ->title('Pagina niet gevonden')
                ->send();
            return;
        }

        $this->selectedPageTitle = $page['title'] ?? 'Untitled';

        // Get raw markdown content from API
        $rawContent = $page['content'] ?? '';
        $this->selectedPageContent = $rawContent;

        // USE THE PRE-CONVERTED HTML FROM API!
        $this->selectedPageContentHtml = $page['content_html'] ?? $this->convertMarkdownToHtml($rawContent);

        // Ensure the HTML receives Tailwind typography / utility classes so headings/lists render as expected
        $this->selectedPageContentHtml = $this->addTailwindClasses($this->selectedPageContentHtml);
    }

    protected function convertMarkdownToHtml(string $markdown): string
    {
        if (empty($markdown)) {
            return '<p class="text-gray-500">No content available.</p>';
        }

        try {
            // Use league/commonmark with custom renderer
            $config = [
                'html_input' => 'allow',
                'allow_unsafe_links' => false,
            ];

            $environment = new \League\CommonMark\Environment\Environment($config);
            $environment->addExtension(new \League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension());
            $environment->addExtension(new \League\CommonMark\Extension\GithubFlavoredMarkdownExtension());
            $environment->addExtension(new \League\CommonMark\Extension\Table\TableExtension());

            $converter = new \League\CommonMark\MarkdownConverter($environment);
            $html = $converter->convert($markdown)->getContent();

            // Add Tailwind classes to the HTML
            $html = $this->addTailwindClasses($html);

            return $html;
        } catch (\Throwable $e) {
            // Fallback to basic conversion
            return $this->basicMarkdownConvert($markdown);
        }
    }

    protected function addTailwindClasses(string $html): string
    {
        // Add classes to headings
        $html = preg_replace('/<h1>/', '<h1 class="text-4xl font-bold text-gray-900 dark:text-white mt-8 mb-4">', $html);
        $html = preg_replace('/<h2>/', '<h2 class="text-3xl font-bold text-gray-900 dark:text-white mt-6 mb-3">', $html);
        $html = preg_replace('/<h3>/', '<h3 class="text-2xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">', $html);
        $html = preg_replace('/<h4>/', '<h4 class="text-xl font-semibold text-gray-900 dark:text-white mt-4 mb-2">', $html);
        $html = preg_replace('/<h5>/', '<h5 class="text-lg font-semibold text-gray-900 dark:text-white mt-4 mb-2">', $html);
        $html = preg_replace('/<h6>/', '<h6 class="text-base font-semibold text-gray-900 dark:text-white mt-4 mb-2">', $html);

        // Add classes to paragraphs
        $html = preg_replace('/<p>/', '<p class="text-gray-700 dark:text-gray-300 my-4 leading-relaxed">', $html);

        // Add classes to links
        $html = preg_replace('/<a href/', '<a class="text-primary-600 dark:text-primary-400 hover:underline" href', $html);

        // Add classes to lists
        $html = preg_replace('/<ul>/', '<ul class="list-disc list-inside my-4 space-y-2 text-gray-700 dark:text-gray-300">', $html);
        $html = preg_replace('/<ol>/', '<ol class="list-decimal list-inside my-4 space-y-2 text-gray-700 dark:text-gray-300">', $html);
        $html = preg_replace('/<li>/', '<li class="ml-4">', $html);

        // Add classes to code
        $html = preg_replace('/<code>/', '<code class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-1.5 py-0.5 rounded text-sm font-mono">', $html);
        $html = preg_replace('/<pre>/', '<pre class="bg-gray-900 dark:bg-gray-950 text-gray-100 p-4 rounded-lg overflow-x-auto my-4">', $html);

        // Add classes to blockquotes
        $html = preg_replace('/<blockquote>/', '<blockquote class="border-l-4 border-primary-500 pl-4 italic my-4 text-gray-600 dark:text-gray-400">', $html);

        // Add classes to tables
        $html = preg_replace('/<table>/', '<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 my-4">', $html);
        $html = preg_replace('/<thead>/', '<thead class="bg-gray-50 dark:bg-gray-800">', $html);
        $html = preg_replace('/<th>/', '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">', $html);
        $html = preg_replace('/<td>/', '<td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">', $html);

        // Add classes to hr
        $html = preg_replace('/<hr>/', '<hr class="my-8 border-gray-300 dark:border-gray-600">', $html);
        $html = preg_replace('/<hr \/>/', '<hr class="my-8 border-gray-300 dark:border-gray-600" />', $html);

        return $html;
    }

    protected function renderMarkdown(?string $markdown): string
    {
        if (empty($markdown)) {
            return '<p class="text-gray-500">No content available.</p>';
        }

        // Always use our custom markdown converter for consistency
        return $this->basicMarkdownConvert($markdown);
    }

    protected function basicMarkdownConvert(string $markdown): string
    {
        $html = $markdown;

        // Headers (must be done first, before line breaks)
        $html = preg_replace('/^######\s+(.+?)$/m', '<h6 class="text-base font-semibold mt-4 mb-2">$1</h6>', $html);
        $html = preg_replace('/^#####\s+(.+?)$/m', '<h5 class="text-lg font-semibold mt-4 mb-2">$1</h5>', $html);
        $html = preg_replace('/^####\s+(.+?)$/m', '<h4 class="text-xl font-semibold mt-4 mb-2">$1</h4>', $html);
        $html = preg_replace('/^###\s+(.+?)$/m', '<h3 class="text-2xl font-semibold mt-6 mb-3">$1</h3>', $html);
        $html = preg_replace('/^##\s+(.+?)$/m', '<h2 class="text-3xl font-bold mt-8 mb-4">$1</h2>', $html);
        $html = preg_replace('/^#\s+(.+?)$/m', '<h1 class="text-4xl font-bold mt-8 mb-4">$1</h1>', $html);

        // Code blocks (triple backticks)
        $html = preg_replace('/```([a-z]*)\n(.*?)\n```/s', '<pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto my-4"><code>$2</code></pre>', $html);

        // Bold
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong class="font-semibold">$1</strong>', $html);
        $html = preg_replace('/__(.+?)__/', '<strong class="font-semibold">$1</strong>', $html);

        // Italic
        $html = preg_replace('/\*([^\*]+?)\*/', '<em class="italic">$1</em>', $html);
        $html = preg_replace('/_([^_]+?)_/', '<em class="italic">$1</em>', $html);

        // Inline code
        $html = preg_replace('/`([^`]+?)`/', '<code class="bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-sm font-mono">$1</code>', $html);

        // Links
        $html = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" class="text-primary-600 dark:text-primary-400 hover:underline">$1</a>', $html);

        // Unordered lists
        $html = preg_replace('/^\* (.+)$/m', '<li class="ml-4">$1</li>', $html);
        $html = preg_replace('/(<li class="ml-4">.*<\/li>\n?)+/s', '<ul class="list-disc list-inside my-4">$0</ul>', $html);

        // Ordered lists
        $html = preg_replace('/^\d+\.\s+(.+)$/m', '<li class="ml-4">$1</li>', $html);
        $html = preg_replace('/(<li class="ml-4">.*<\/li>\n?)+/s', '<ol class="list-decimal list-inside my-4">$0</ol>', $html);

        // Blockquotes
        $html = preg_replace('/^>\s+(.+)$/m', '<blockquote class="border-l-4 border-gray-300 dark:border-gray-600 pl-4 italic my-4">$1</blockquote>', $html);

        // Horizontal rules
        $html = preg_replace('/^---$/m', '<hr class="my-8 border-gray-300 dark:border-gray-600">', $html);

        // Paragraphs (split by double newlines)
        $paragraphs = explode("\n\n", $html);
        $html = '';
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if (empty($p)) continue;

            // Don't wrap if already wrapped in HTML tag
            if (preg_match('/^<(h[1-6]|ul|ol|pre|blockquote|hr)/', $p)) {
                $html .= $p . "\n";
            } else {
                $html .= '<p class="my-4">' . $p . '</p>' . "\n";
            }
        }

        return $html;
    }


    /* ---------- Helpers ---------- */

    public function getApplicationOptions(): array
    {
        $apps = $this->knowledgeBaseData['applications'] ?? [];
        return collect($apps)->mapWithKeys(fn($app) => [$app['id'] => $app['name']])->toArray();
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

    protected function getHeaderActions(): array
    {
        if (!$this->knowledgeBaseData) return [];

        return [
            Action::make('refresh')
                ->label('Vernieuwen')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    if (!$this->apiToken) return;

                    $service = app(WikiCubeApiService::class);
                    $service->clearCache($this->apiToken, $this->selectedApplicationId);

                    try {
                        $this->knowledgeBaseData = $service->fetchKnowledgeBase(
                            $this->apiToken,
                            $this->selectedApplicationId
                        );

                        if ($this->selectedPageId) {
                            $this->openPage($this->selectedPageId);
                        }

                        Notification::make()
                            ->success()
                            ->title('Kennisbank vernieuwd')
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Fout bij vernieuwen')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
