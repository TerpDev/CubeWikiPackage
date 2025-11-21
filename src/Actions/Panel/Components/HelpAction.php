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

class HelpAction extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public ?string $title = null;
    public ?string $contentHtml = null;
    protected ?array $knowledgeBaseData = null;
    protected function getPages(): array
    {
        $rawPages = CubeWikiPlugin::getImportantPages();
        $data     = $this->getKnowledgeBaseData();

        $result = [];

        foreach ($rawPages as $item) {
            // 1) Alleen slug opgegeven: 'introduction'
            if (is_string($item)) {
                $slug  = $item;
                $title = $this->resolveTitleFromData($data, $slug) ?? $slug;
            }
            // 2) Array met slug en optioneel title
            elseif (is_array($item)) {
                $slug = $item['slug'] ?? null;

                if (! $slug) {
                    continue;
                }

                // Als dev zelf een title zet, die gebruiken.
                // Anders uit de API proberen te halen.
                $title = $item['title']
                    ?? $this->resolveTitleFromData($data, $slug)
                    ?? $slug;
            } else {
                continue;
            }

            $result[] = [
                'slug'  => $slug,
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
            ->modalHeading(fn () => $this->title ?? 'Help')
            ->modalWidth('3xl')
            ->form([
                Placeholder::make('page_content')
                    ->hiddenLabel()
                    ->content(fn () => new HtmlString(
                        '<div class="prose dark:prose-invert max-w-3xl mx-auto">'
                        . ($this->contentHtml)
                        . '</div>'
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
            ?? config('cubewikipackage.token')
            ?? env('CUBEWIKI_TOKEN');

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
        foreach ($data['applications'] ?? [] as $app) {
            foreach ($app['categories'] ?? [] as $cat) {
                foreach ($cat['pages'] ?? [] as $page) {
                    $pageSlug = $page['slug'] ?? $page['permalink'] ?? null;

                    if (! empty($pageSlug) && $pageSlug === $slug) {
                        return $page;
                    }
                }
            }
        }

        return null;
    }

    public function render()
    {
        return view('cubewikipackage::panel.helpaction', [
            'icon'  => 'heroicon-o-question-mark-circle',
            'pages' => $this->getPages(),
        ]);
    }
}
