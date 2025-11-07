<?php

namespace TerpDev\CubeWikiPackage\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WikiCubeApiService
{
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('cubewikipackage.api_url', 'http://wikicube.test');
    }

    public function fetchKnowledgeBase(string $token): array
    {
        $cacheKey = "wikicube_data_{$token}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($token) {
            try {
                $response = Http::timeout(10)
                    ->get("{$this->apiUrl}/api/data/{$token}");

                if ($response->successful()) {
                    $data = $response->json();

                    if (!isset($data['success']) || !$data['success']) {
                        throw new \Exception($data['message'] ?? 'Invalid token');
                    }

                    return $data;
                }

                throw new \Exception('Failed to fetch knowledge base data');
            } catch (\Exception $e) {
                throw new \Exception('Error: ' . $e->getMessage());
            }
        });
    }

    public function clearCache(string $token): void
    {
        Cache::forget("wikicube_data_{$token}");
    }
}

