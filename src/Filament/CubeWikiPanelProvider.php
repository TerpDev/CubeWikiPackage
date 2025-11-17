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
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn(): string => Blade::render('<livewire:cubewiki-sidebar />')
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
