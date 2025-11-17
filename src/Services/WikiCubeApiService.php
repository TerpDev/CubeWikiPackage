<?php

namespace TerpDev\CubeWikiPackage\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

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

        return Cache::remember($cacheKey, now()->addMinutes(config('cubewikipackage.cache_duration', 5)), function () use ($token, $applicationId) {
            try {
                $url = "{$this->apiUrl}/api/data/{$token}";

                $response = Http::timeout(30)
                    ->withOptions(['verify' => false])
                    ->get($url, array_filter([
                        'application_id' => $applicationId
                    ]));

                if ($response->successful()) {
                    $data = $response->json();

                    if (!isset($data['success']) || !$data['success']) {
                        throw new \Exception($data['message'] ?? 'Invalid token or no data found');
                    }

                    return $data;
                }

                throw new \Exception('Failed to fetch knowledge base data. Status: ' . $response->status());
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                throw new \Exception('Cannot connect to WikiCube API at ' . $this->apiUrl);
            } catch (\Exception $e) {
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
