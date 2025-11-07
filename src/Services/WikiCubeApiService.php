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

    public function fetchKnowledgeBase(string $token): array
    {
        $cacheKey = "wikicube_data_{$token}";

        return Cache::remember($cacheKey, now()->addMinutes(config('cubewikipackage.cache_duration', 5)), function () use ($token) {
            try {
                $response = Http::timeout(30)
                    ->withOptions([
                        'verify' => false, // Voor lokale development met self-signed certificates
                    ])
                    ->get("{$this->apiUrl}/api/data/{$token}");

                if ($response->successful()) {
                    $data = $response->json();

                    if (!isset($data['success']) || !$data['success']) {
                        throw new \Exception($data['message'] ?? 'Invalid token or no data found');
                    }

                    return $data;
                }

                throw new \Exception('Failed to fetch knowledge base data. Status: ' . $response->status());
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                throw new \Exception('Cannot connect to WikiCube API at ' . $this->apiUrl . '. Please check if the URL is correct and the API is running.');
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

