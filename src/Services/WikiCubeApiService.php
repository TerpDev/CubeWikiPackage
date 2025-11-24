<?php

namespace TerpDev\CubeWikiPackage\Services;

use Illuminate\Support\Facades\Cache;
use Saloon\CachePlugin\Contracts\Cacheable;
use Saloon\CachePlugin\Contracts\Driver;
use Saloon\CachePlugin\Drivers\LaravelCacheDriver;
use Saloon\CachePlugin\Traits\HasCaching;
use Saloon\Enums\Method;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use TerpDev\CubeWikiPackage\Services\Requests\KnowledgeBaseRequest;

class WikiCubeApiService extends Connector implements Cacheable
{
    use HasCaching;

    protected Method $method = Method::GET;

    public function resolveBaseUrl(): string
    {
        return rtrim(config('cubewikipackage.api_url', 'https://wikicube.test'), '/');
    }

    public function fetchKnowledgeBase(string $token, ?int $applicationId = null): array
    {
        $request = new KnowledgeBaseRequest($token, $applicationId);

        $this->toggleCachingForRequest($request);

        return $this->sendRequest($request);
    }

    protected function sendRequest(Request $request): array
    {
        $response = $this->send($request);
        $response->throw();

        $data = $response->json();

        if (! ($data['success'] ?? false)) {
            throw new \Exception($data['message'] ?? 'Invalid token or no data found');
        }

        return $data;
    }

    protected function shouldCache(): bool
    {
        $value = config('cubewikipackage.cache_enabled', true);

        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $parsed ?? (bool) $value;
    }

    protected function cacheDurationMinutes(): int
    {
        $value = config('cubewikipackage.cache_duration', 5);

        $minutes = is_numeric($value) ? (int) $value : 5;

        return max($minutes, 1);
    }

    protected function toggleCachingForRequest(Request $request): void
    {
        if (! $this->shouldCache()) {
            $this->disableCaching();

            if (method_exists($request, 'disableCaching')) {
                $request->disableCaching();
            }

            return;
        }

        $this->enableCaching();

        if (method_exists($request, 'enableCaching')) {
            $request->enableCaching();
        }
    }

    protected function getCacheKey(string $token, ?int $applicationId = null): string
    {
        return $applicationId
            ? "wikicube_data_{$token}_app_{$applicationId}"
            : "wikicube_data_{$token}";
    }

    public function resolveCacheDriver(): Driver
    {
        return new LaravelCacheDriver(Cache::store());
    }

    public function cacheExpiryInSeconds(): int
    {
        return $this->cacheDurationMinutes() * 60;
    }

    protected function cacheKey(PendingRequest $pendingRequest): ?string
    {
        $request = $pendingRequest->getRequest();

        if ($request instanceof KnowledgeBaseRequest) {
            return $this->getCacheKey($request->getToken(), $request->getApplicationId());
        }

        return null;
    }
}
