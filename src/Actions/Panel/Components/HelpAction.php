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

    protected function getPages(): array
    {
        return CubeWikiPlugin::getImportantPages();
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
                        . ($this->contentHtml ?? '<p class="text-sm text-gray-500">Geen content.</p>')
                        . '</div>'
                    )),
            ])
            ->modalSubmitAction(false);
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
        $pluginPages     = CubeWikiPlugin::getImportantPages();
        $registeredSlugs = array_filter(array_map(fn ($p) => $p['slug'] ?? null, $pluginPages));

        if (! in_array($slug, $registeredSlugs, true)) {
            Notification::make()
                ->warning()
                ->title('Ongeldige pagina')
                ->body("De geselecteerde help-pagina [{$slug}] is niet geregistreerd in CubeWikiPlugin::importantPages().")
                ->send();

            return;
        }

        $token = $this->resolveApiToken();

        if (! $token) {
            Notification::make()
                ->warning()
                ->title('Geen API-token')
                ->body('Geen API-token beschikbaar. Stel CUBEWIKI_TOKEN in je .env in.')
                ->send();

            return;
        }

        $service = app(WikiCubeApiService::class);
        $data    = $service->fetchKnowledgeBase($token, null);

        $found = null;

        foreach ($data['applications'] ?? [] as $app) {
            foreach ($app['categories'] ?? [] as $cat) {
                foreach ($cat['pages'] ?? [] as $page) {
                    $pageSlug = $page['slug'] ?? $page['permalink'] ?? null;

                    if (! empty($pageSlug) && $pageSlug === $slug) {
                        $found = $page;
                        break 3;
                    }
                }
            }
        }

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

    public function render()
    {
        return view('cubewikipackage::panel.helpaction', [
            'icon'  => 'heroicon-o-question-mark-circle',
            'pages' => $this->getPages(),
        ]);
    }
}
