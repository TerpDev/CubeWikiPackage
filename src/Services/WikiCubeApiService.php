<?php

namespace TerpDev\CubeWikiPackage\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WikiCubeApiService
{
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('cubewikipackage.api_url', 'https://wikicube.test'), '/');
    }

    public function fetchKnowledgeBase(string $token, ?int $applicationId = null): array
    {
        $cacheKey = $this->getCacheKey($token, $applicationId);

        Log::info('WikiCubeApiService::fetchKnowledgeBase CALLED', [
            'token'      => substr($token, 0, 6) . '***',
            'appId'      => $applicationId,
            'cache_key'  => $cacheKey,
        ]);

        return Cache::remember($cacheKey, now()->addMinutes(config('cubewikipackage.cache_duration', 5)), function () use ($token, $applicationId, $cacheKey) {
            try {
                $url = "{$this->apiUrl}/api/data/{$token}";

                Log::info('WikiCubeApiService::calling API', [
                    'url'  => $url,
                    'appId' => $applicationId,
                ]);

                $response = Http::timeout(30)
                    ->withOptions(['verify' => false])
                    ->get($url, array_filter([
                        'application_id' => $applicationId
                    ]));

                if ($response->successful()) {
                    $data = $response->json();

                    Log::info('WikiCubeApiService::API response OK', [
                        'apps_count' => count($data['applications'] ?? []),
                        'app_slugs'  => collect($data['applications'] ?? [])->pluck('slug', 'id')->all(),
                    ]);

                    if (!isset($data['success']) || !$data['success']) {
                        throw new \Exception($data['message'] ?? 'Invalid token or no data found');
                    }

                    return $data;
                }

                Log::warning('WikiCubeApiService::API response FAILED', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                throw new \Exception('Failed to fetch knowledge base data. Status: ' . $response->status());
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('WikiCubeApiService::connection error', [
                    'message' => $e->getMessage(),
                ]);
                throw new \Exception('Cannot connect to WikiCube API at ' . $this->apiUrl);
            } catch (\Exception $e) {
                Log::error('WikiCubeApiService::general error', [
                    'message' => $e->getMessage(),
                ]);
                throw new \Exception('Error: ' . $e->getMessage());
            }
        });
    }

    public function clearCache(string $token, ?int $applicationId = null): void
    {
        Cache::forget($this->getCacheKey($token, $applicationId));
    }

    protected function getCacheKey(string $token, ?int $applicationId = null): string
    {
        return $applicationId
            ? "wikicube_data_{$token}_app_{$applicationId}"
            : "wikicube_data_{$token}";
    }
}
