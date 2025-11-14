<?php

namespace TerpDev\CubeWikiPackage\Filament;

use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

class CubeWikiPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('cubewiki')
            ->path('cubewiki')
            ->colors([
                'primary' => Color::Amber,
            ])

            ->discoverResources(in: __DIR__ . '/Resources', for: 'TerpDev\\CubeWikiPackage\\Filament\\Resources')
            ->discoverPages(in: __DIR__ . '/Pages', for: 'TerpDev\\CubeWikiPackage\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: __DIR__ . '/Widgets', for: 'TerpDev\\CubeWikiPackage\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            // Inject the Livewire application selector into the sidebar
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn(): string => Blade::render('<livewire:cubewiki-sidebar />')
            )
            // Only render the help action when a knowledge-base page is opened
//            ->renderHook(
//                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
//                fn(): string => (int) request()->query('page') > 0
//                    ? Blade::render('<livewire:cubewikipackage-helpaction />')
//                    : ''
//            )

        ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn(): string => <<<'HTML'
<style>
/* Container used in KnowledgeBase: <div class="wk-doc">…</div> */
.wk-doc { max-width: 72ch; }
.dark .wk-doc { color-scheme: dark; }

/* Headings */
.wk-doc h1, .wk-doc h2, .wk-doc h3, .wk-doc h4, .wk-doc h5, .wk-doc h6 {
  line-height: 1.25;
  font-weight: 700;
  color: rgb(17 24 39); /* gray-900 */
  margin-top: 2.25rem;
  margin-bottom: 1rem;
}
.dark .wk-doc h1, .dark .wk-doc h2, .dark .wk-doc h3,
.dark .wk-doc h4, .dark .wk-doc h5, .dark .wk-doc h6 {
  color: rgb(255 255 255);
}
.wk-doc h1 { font-size: 2.25rem; } /* text-4xl */
.wk-doc h2 { font-size: 1.875rem; } /* text-3xl */
.wk-doc h3 { font-size: 1.5rem; } /* text-2xl */
.wk-doc h4 { font-size: 1.25rem; } /* text-xl */

/* Pretty hash marker like your example */
.wk-doc h1::before, .wk-doc h2::before, .wk-doc h3::before {
  content: "#";
  color: rgb(217 119 6);        /* amber-600 */
  margin-right: .5rem;
  font-weight: 700;
}

/* Paragraphs */
.wk-doc p {
  color: rgb(55 65 81);         /* gray-700 */
  margin: 1rem 0;
  line-height: 1.75;
}
.dark .wk-doc p { color: rgb(209 213 219); } /* gray-300 */

/* Links */
.wk-doc a { color: rgb(217 119 6); text-decoration: underline; }
.dark .wk-doc a { color: rgb(245 158 11); }

/* Lists */
.wk-doc ul { list-style: disc; padding-left: 1.25rem; margin: 1rem 0; }
.wk-doc ol { list-style: decimal; padding-left: 1.25rem; margin: 1rem 0; }
.wk-doc li { margin: .25rem 0; }

/* Code */
.wk-doc code {
  background: rgb(243 244 246); /* gray-100 */
  padding: .15rem .35rem;
  border-radius: .25rem;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  font-size: .875rem;
}
.dark .wk-doc code { background: rgb(17 24 39); color: rgb(229 231 235); }

.wk-doc pre {
  background: rgb(17 24 39);
  color: white;
  padding: 1rem;
  border-radius: .5rem;
  overflow-x: auto;
  margin: 1rem 0;
}

/* Tables */
.wk-doc table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
.wk-doc thead { background: rgb(249 250 251); } /* gray-50 */
.dark .wk-doc thead { background: rgb(31 41 55); } /* gray-800 */
.wk-doc th, .wk-doc td { text-align: left; padding: .5rem .75rem; border-bottom: 1px solid rgb(229 231 235); }
.dark .wk-doc th, .dark .wk-doc td { border-color: rgb(55 65 81); }

/* Blockquote */
.wk-doc blockquote {
  border-left: 4px solid rgb(217 119 6);
  padding-left: .75rem;
  color: rgb(75 85 99);
  font-style: italic;
  margin: 1rem 0;
}
.dark .wk-doc blockquote { color: rgb(156 163 175); }
</style>
HTML
            )
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                $token = session('cubewiki_token');
                $selectedAppId = request()->integer('app') ?: (int)session('cubewiki_application_id');

                $categoryItems = [];

                if ($token && $selectedAppId) {

                    $service = app(WikiCubeApiService::class);
                    $data = $service->fetchKnowledgeBase($token, $selectedAppId);
                    $app = collect($data['applications'] ?? [])->firstWhere('id', $selectedAppId);

                    if ($app) {

                        foreach (($app['categories'] ?? []) as $category) {
                            $pageChildren = [];

                            foreach (($category['pages'] ?? []) as $page) {
                                $pageChildren[] = NavigationItem::make($page['title'] ?? 'Page')
                                    ->icon('heroicon-o-document-text')
                                    ->url(url('/cubewiki/knowledge-base?app=' . $selectedAppId . '&cat=' . ($category['id'] ?? '') . '&page=' . ($page['id'] ?? '')))
                                    ->isActiveWhen(fn(): bool => (int)request()->query('page') === (int)($page['id'] ?? 0));
                            }

                            if (empty($pageChildren)) {
                                $pageChildren[] = NavigationItem::make('No pages')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->url('#');
                            }

                            $categoryItem = NavigationItem::make($category['name'] ?? 'Category')
                                ->icon('heroicon-o-folder')
                                // remove URL so clicking expands instead of navigating
                                ->url(url('/cubewiki/knowledge-base?app=' . $selectedAppId . '&cat=' . ($category['id'] ?? '')))
                                ->isActiveWhen(fn(): bool => (int)request()->query('cat') === (int)($category['id'] ?? 0))
                                ->childItems($pageChildren);
                            $categoryItems[] = $categoryItem;
                        }
                    }
                }

                return $builder->groups([
                    NavigationGroup::make('Categories')
                        ->items($categoryItems)
                        ->collapsible(true)
                        ->collapsed(false),
                ]);
            })
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
