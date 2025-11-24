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

    public ?string $title = null;

    public ?string $contentHtml = null;

    protected ?array $knowledgeBaseData = null;
    protected ?array $currentApp = null;
    protected ?array $currentCategory = null;
    protected ?array $currentPage = null;
    protected function getPages(): array
    {
        $rawPages = CubeWikiPlugin::getImportantPages();
        $data = $this->getKnowledgeBaseData();

        $result = [];

        foreach ($rawPages as $item) {
            if (is_string($item)) {
                $slug = $item;
                $title = $this->resolveTitleFromData($data, $slug) ?? $slug;
            }
            // 2) Array met slug en optioneel title
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

                $last = array_key_last($breadcrumbs);
                $i = 0;

                foreach ($breadcrumbs as $label) {
                    $html .= e($label);

                    if ($i !== $last) {
                        $html .= '<span class="text-gray-400 dark:text-gray-500 mx-1">›</span>';
                    }

                    $i++;
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

        $this->title = $found['title'] ?? ($found['name'] ?? 'Help');
        $this->contentHtml = $found['content_html']
            ?? ($found['content'] ?? '<p class="text-sm text-gray-500">Geen content beschikbaar.</p>');

        $this->mountAction('help');
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
                        // hier bewaren we alles voor de breadcrumbs
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
            $appName = $this->currentApp['name'] ?? 'Applicatie';

            $breadcrumbs[$baseUrl.'?app='.urlencode($appName)] = $appName;
        }

        if ($this->currentCategory) {
            $appName = $this->currentApp['name'] ?? 'Applicatie';

            $breadcrumbs[$baseUrl.'?app='.urlencode($appName).'&cat='.$this->currentCategory['id']] =
                $this->currentCategory['name'] ?? 'Categorie';
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
