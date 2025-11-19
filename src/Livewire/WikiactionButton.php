<?php

namespace TerpDev\CubeWikiPackage\Livewire;

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

class WikiactionButton extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    /**
     * help = grote help dropdown in de topbar (slide-over)
     * hint = klein tekstlinkje / modal bij een formulier-veld
     */
    public string $variant = 'help';

    // voor hint-variant (per veld)
    public ?string $slug = null;
    public ?string $label = null;

    // gedeelde state voor modal/slide-over
    public ?string $title = null;
    public ?string $contentHtml = null;
    public ?string $breadcrumb = null;   // ← nieuw

    protected function getPages(): array
    {
        return CubeWikiPlugin::getImportantPages();
    }

    public function getActions(): array
    {
        return [
            $this->helpAction(),
            $this->hintAction(),
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
                    ->content(function () {
                        return new HtmlString(
                            '<div class="prose dark:prose-invert max-w-3xl mx-auto">' .
                            ($this->contentHtml) .
                            '</div>'
                        );
                    }),
            ])
            ->modalSubmitAction(false);
    }

    protected function hintAction(): Action
    {
        return Action::make('hint')
            ->label('Hint')
            ->icon('heroicon-o-question-mark-circle')
            ->modal()
            ->modalHeading(fn () => $this->title ?? ($this->label ?? 'Hint'))
            ->modalWidth('md')
            ->form([
                Placeholder::make('hint_content')
                    ->hiddenLabel()
                    ->content(function () {
                        return new HtmlString(
                            '<div class="prose dark:prose-invert">' .
                            ($this->contentHtml) .
                            '</div>'
                        );
                    }),
            ])
            ->modalSubmitAction(false);
    }

    public function openBySlug(string $slug): void
    {
        if ($this->variant === 'help') {
            $pluginPages = CubeWikiPlugin::getImportantPages();
            $registeredSlugs = array_filter(array_map(fn ($p) => $p['slug'] ?? null, $pluginPages));

            if (! in_array($slug, $registeredSlugs, true)) {
                Notification::make()
                    ->warning()
                    ->title('Ongeldige pagina')
                    ->body("De geselecteerde help-pagina [{$slug}] is niet geregistreerd voor deze plugin.")
                    ->send();

                return;
            }
        }

        $token = session('cubewiki_token');
        $appId = session('cubewiki_application_id');

        if (! $token) {
            Notification::make()
                ->warning()
                ->title('Geen API-token')
                ->body('Geen API-token beschikbaar. Open eerst de Documentation wizard.')
                ->send();

            return;
        }

        $service = app(WikiCubeApiService::class);
        $data = $service->fetchKnowledgeBase($token, $appId);
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

        $this->title = $found['title'] ?? ($found['name']);
        $this->contentHtml = $found['content_html']
            ?? ($found['content'] ?? '<p class="text-sm text-gray-500">Geen content beschikbaar.</p>');

        // Bepaal welke action we mounten
        if ($this->variant === 'hint') {
            $this->mountAction('hint');
        } else {
            $this->mountAction('help');
        }
    }

    public function render()
    {
        if ($this->variant === 'hint') {
            return view('cubewikipackage::livewire.hintaction', [
                'icon'  => 'heroicon-o-question-mark-circle',
                'label' => $this->label ?? 'Hint',
                'slug'  => $this->slug,
            ]);
        }

        // Help = dropdown in topbar
        return view('cubewikipackage::livewire.helpaction', [
            'icon'  => 'heroicon-o-question-mark-circle',
            'pages' => $this->getPages(),
        ]);
    }
}
