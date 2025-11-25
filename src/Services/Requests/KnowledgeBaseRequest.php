<?php

namespace TerpDev\CubeWikiPackage\Services\Requests;

use Illuminate\Support\Facades\Cache;
use Saloon\CachePlugin\Contracts\Cacheable;
use Saloon\CachePlugin\Contracts\Driver;
use Saloon\CachePlugin\Drivers\LaravelCacheDriver;
use Saloon\CachePlugin\Traits\HasCaching;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;

class KnowledgeBaseRequest extends Request implements Cacheable
{
    use HasCaching;

    protected Method $method = Method::GET;

    public function __construct(
        protected string $token,
        protected ?int $applicationId = null,
    ) {
        if (! $this->configCachingEnabled()) {
            $this->disableCaching();
        }
    }

    public function resolveEndpoint(): string
    {
        return "/api/data/{$this->token}";
    }

//    public function getToken(): string
//    {
//        return $this->token;
//    }
    public function resolveCacheDriver(): Driver
    {
        return new LaravelCacheDriver(Cache::store());
    }

    public function cacheExpiryInSeconds(): int
    {
        $value   = config('cubewikipackage.cache_duration', 5);
        $minutes = is_numeric($value) ? (int) $value : 5;

        if ($minutes < 1) {
            $minutes = 1;
        }

        return $minutes * 60;
    }
    protected function cacheKey(PendingRequest $pendingRequest): ?string
    {
        return $this->applicationId
            ? "wikicube_data_{$this->token}_app_{$this->applicationId}"
            : "wikicube_data_{$this->token}";
    }

    protected function configCachingEnabled(): bool
    {
        $value  = config('cubewikipackage.cache_enabled', true);
        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $parsed ?? (bool) $value;
    }
}
