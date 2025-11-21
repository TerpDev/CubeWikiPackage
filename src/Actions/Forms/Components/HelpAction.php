<?php

namespace TerpDev\CubeWikiPackage\Actions\Forms\Components;

use Filament\Actions\Action;
use Illuminate\Support\HtmlString;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

class HelpAction extends Action
{
    protected function setUp(): void
    {
        $this->icon('heroicon-o-question-mark-circle')
            ->link()
            ->extraAttributes([
                'class' => 'leading-none align-middle',
            ])
            ->modal()
            ->modalWidth('lg')
            ->modalSubmitAction(false)
            ->modalContent(fn () => new HtmlString(
                static::resolveHtmlForSlug($this->name)
            ));

    }

    protected static function resolveHtmlForSlug(string $slug): string
    {
        $token = static::resolveApiToken();
        $service = app(WikiCubeApiService::class);
        $data = $service->fetchKnowledgeBase($token, null);
        $page = static::findPageBySlug($data, $slug);

        return $page['content_html'];
    }

    protected static function findPageBySlug(array $data, string $slug): ?array
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

    protected static function resolveApiToken(): ?string
    {
        $token = session('cubewiki_token');

        if (! $token) {
            $token = config('cubewikipackage.api_token');

            if ($token) {
                session(['cubewiki_token' => $token]);
            }
        }

        return $token ?: null;
    }
}
