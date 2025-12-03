<?php

namespace TerpDev\CubeWikiPackage\Actions\Panel\Components;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use TerpDev\CubeWikiPackage\Filament\CubeWikiPlugin;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

class HelpAction extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public bool $hostOnly = false;

    protected $listeners = [
        'cubewiki-open-help' => 'openBySlug',
    ];

    public ?string $title = null;

    public ?string $contentHtml = null;

    protected ?array $knowledgeBaseData = null;

    protected ?array $currentApp = null;

    protected ?array $currentCategory = null;

    protected ?array $currentPage = null;

    public function mount(bool $hostOnly = false): void
    {
        $this->hostOnly = $hostOnly;
    }

    protected function getListeners(): array
    {
        if (! $this->hostOnly) {
            return [];
        }

        return $this->listeners;
    }

    protected function getPages(): array
    {
        $rawPages = CubeWikiPlugin::getImportantPages();
        $data = $this->getKnowledgeBaseData();

        $result = [];

        foreach ($rawPages as $item) {
            if (is_string($item)) {
                $slug = $item;
                $title = $this->resolveTitleFromData($data, $slug) ?? $slug;
            } // 2) Array met slug en optioneel title
            elseif (is_array($item)) {
                $slug = $item['slug'] ?? null;

                if (! $slug) {
                    continue;
                }
                $title = $item['title']
                    ?? $this->resolveTitleFromData($data, $slug)
                    ?? $slug;
            } else {
                continue;
            }

            $result[] = [
                'slug' => $slug,
                'title' => $title,
            ];
        }

        return $result;
    }

    public function getActions(): array
    {
        if (! $this->hostOnly) {
            return [];
        }

        return [
            $this->helpAction(),
        ];
    }

    protected function helpAction(): Action
    {
        return Action::make('help')
            ->icon('heroicon-o-question-mark-circle')
            ->slideOver()
            ->modalHeading(function () {
                $breadcrumbs = $this->getLocalBreadcrumbs();

                $html = '<div class="text-sm font-medium text-gray-500 dark:text-gray-400 flex items-center gap-2">';

                $lastKey = array_key_last($breadcrumbs);

                foreach ($breadcrumbs as $key => $label) {
                    $html .= e($label);

                    if ($key !== $lastKey) {
                        $html .= '<span class="text-gray-400 dark:text-gray-500 mx-1">›</span>';
                    }
                }

                $html .= '</div>';

                return new \Illuminate\Support\HtmlString($html);
            })
            ->modalWidth('3xl')
            ->form([
                Placeholder::make('page_content')
                    ->hiddenLabel()
                    ->content(fn () => new HtmlString(
                        '<div class="prose dark:prose-invert max-w-3xl mx-auto">'
                        .($this->contentHtml)
                        .'</div>'
                    )),
            ])
            ->modalSubmitAction(false);
    }

    protected function getKnowledgeBaseData(): ?array
    {
        if ($this->knowledgeBaseData !== null) {
            return $this->knowledgeBaseData;
        }

        $token = $this->resolveApiToken();

        if (! $token) {
            return $this->knowledgeBaseData = null;
        }

        $service = app(WikiCubeApiService::class);

        return $this->knowledgeBaseData = $service->fetchKnowledgeBase($token, null);
    }

    protected function resolveApiToken(): ?string
    {
        $token = session('cubewiki_token')
            ?? config('cubewikipackage.api_token');

        if ($token) {
            session(['cubewiki_token' => $token]);
        }

        return $token ?: null;
    }

    public function openBySlug(string $slug): void
    {
        $data = $this->getKnowledgeBaseData();

        if (! $data) {
            Notification::make()
                ->warning()
                ->title('Geen API-token')
                ->body('Geen API-token beschikbaar. Stel CUBEWIKI_TOKEN in je .env in.')
                ->send();

            return;
        }

        $found = $this->findPageBySlug($data, $slug);

        if (! $found) {
            Notification::make()
                ->warning()
                ->title('Pagina niet gevonden')
                ->body("Pagina met slug [{$slug}] niet gevonden in WikiCube data.")
                ->send();

            return;
        }

        $this->title = $found['title'];
        $this->contentHtml = $this->processHeadings($found['content_html'] ?? '');

        $this->mountAction('help');
    }

    protected function processHeadings(string $html): string
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);

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
                $id = $node->getAttribute('id') ?: \Illuminate\Support\Str::slug($text);

                if (! $node->hasAttribute('id')) {
                    $node->setAttribute('id', $id);
                }

                $existingClass = $node->getAttribute('class');
                $newClass = trim($existingClass.' scroll-mt-24');
                $node->setAttribute('class', $newClass);

                $this->prependMarkdownPrefix($node, $level);
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);

        if ($body) {
            $innerHtml = '';
            foreach ($body->childNodes as $child) {
                $innerHtml .= $dom->saveHTML($child);
            }

            return $innerHtml;
        }

        return $html;
    }

    protected function prependMarkdownPrefix(\DOMElement $node, int $level): void
    {
        if ($node->hasAttribute('data-markdown-prefixed') || ! $node->ownerDocument) {
            return;
        }

        $doc = $node->ownerDocument;
        $prefixText = '# ';

        $prefixSpan = $doc->createElement('span');
        $prefixSpan->setAttribute('data-markdown-prefix', 'true');
        $prefixSpan->setAttribute('style', 'color: var(--primary-600, var(--primary-color, currentColor));');
        $prefixSpan->setAttribute('aria-hidden', 'true');
        $prefixSpan->textContent = $prefixText;

        if ($node->firstChild) {
            $node->insertBefore($prefixSpan, $node->firstChild);
        } else {
            $node->appendChild($prefixSpan);
        }

        $node->setAttribute('data-markdown-prefixed', 'true');
    }

    protected function resolveTitleFromData(?array $data, string $slug): ?string
    {
        if (! $data) {
            return null;
        }

        $page = $this->findPageBySlug($data, $slug);

        return $page['title'] ?? $page['name'] ?? null;
    }

    protected function findPageBySlug(array $data, string $slug): ?array
    {
        // reset
        $this->currentApp = null;
        $this->currentCategory = null;
        $this->currentPage = null;

        foreach ($data['applications'] ?? [] as $app) {
            foreach ($app['categories'] ?? [] as $cat) {
                foreach ($cat['pages'] ?? [] as $page) {
                    $pageSlug = $page['slug'] ?? $page['permalink'] ?? null;

                    if (! empty($pageSlug) && $pageSlug === $slug) {
                        $this->currentApp = $app;
                        $this->currentCategory = $cat;
                        $this->currentPage = $page;

                        return $page;
                    }
                }
            }
        }

        return null;
    }

    public function getLocalBreadcrumbs(): array
    {
        $breadcrumbs = [];

        $baseUrl = '/'.CubeWikiPlugin::$cubeWikiPanelPath.'/knowledge-base';

        if ($this->currentApp) {
            $appName = $this->currentApp['name'];

            $breadcrumbs[$baseUrl.'?app='.urlencode($appName)] = $appName;
        }

        if ($this->currentCategory) {
            $appName = $this->currentApp['name'];

            $breadcrumbs[$baseUrl.'?app='.urlencode($appName).'&cat='.$this->currentCategory['id']] =
                $this->currentCategory['name'];
        }

        if ($this->currentPage) {
            $breadcrumbs['#'] = $this->currentPage['title'] ?? ($this->currentPage['name']);
        }

        return $breadcrumbs;
    }

    public function render()
    {
        return view('cubewikipackage::panel.helpaction', [
            'icon' => 'heroicon-o-question-mark-circle',
            'pages' => $this->getPages(),
        ]);
    }
}
