<?php

namespace TerpDev\CubeWikiPackage\Actions\Forms\Components;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

class HelpAction extends Action
{
    public static function forSlug(string $slug, ?string $label = null): static
    {
        $name = "cubewiki-help.{$slug}";

        $action = static::make($name)
            ->icon('heroicon-o-question-mark-circle')
            ->modal()
            ->modalWidth('lg')
            ->modalSubmitAction(false)
            ->modalHeading(fn() => static::resolveTitleForSlug($slug, $label))
            ->modalContent(fn() => new HtmlString(
                static::resolveHtmlForSlug($slug)
            ));

        if ($label) {
            $action->label($label);
        } else {
            $action->label('Help');
        }

        return $action;
    }

    protected static function resolveHtmlForSlug(string $slug): string
    {
        $token = static::resolveApiToken();


        $service = app(WikiCubeApiService::class);
        $data = $service->fetchKnowledgeBase($token, null);


        $page = static::findPageBySlug($data, $slug);

        if (!$page) {
            return '<p class="text-sm text-gray-500">Pagina niet gevonden in WikiCube.</p>';
        }

        return $page['content_html'];
    }

    protected static function resolveTitleForSlug(string $slug, ?string $fallbackLabel = null): string
    {
        $token = static::resolveApiToken();
        $service = app(WikiCubeApiService::class);
        $data = $service->fetchKnowledgeBase($token, null);

        $page = static::findPageBySlug($data, $slug);

        return $page['title'];
    }
    protected static function findPageBySlug(array $data, string $slug): ?array
    {
        foreach ($data['applications'] ?? [] as $app) {
            foreach ($app['categories'] ?? [] as $cat) {
                foreach ($cat['pages'] ?? [] as $page) {
                    $pageSlug = $page['slug'] ?? $page['permalink'] ?? null;

                    if (!empty($pageSlug) && $pageSlug === $slug) {
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

        if (!$token) {
            $token = config('cubewikipackage.token')
                ?? env('CUBEWIKI_TOKEN');

            if ($token) {
                session(['cubewiki_token' => $token]);
            }
        }

        return $token ?: null;
    }
}
