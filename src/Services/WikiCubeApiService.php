<?php

namespace TerpDev\CubeWikiPackage\Services;

use Saloon\Http\Connector;
use TerpDev\CubeWikiPackage\Services\Requests\KnowledgeBaseRequest;

class WikiCubeApiService extends Connector
{
    public function resolveBaseUrl(): string
    {
        return rtrim(config('cubewikipackage.api_url', 'https://wikicube.test'), '/');
    }

    public function fetchKnowledgeBase(string $token, ?int $applicationId = null): array
    {
        $request = new KnowledgeBaseRequest($token, $applicationId);
        $response = $this->send($request);

        // Gooit exception bij 4xx/5xx
        $response->throw();

        $data = $response->json();

        if (! ($data['success'] ?? false)) {
            throw new \RuntimeException($data['message'] ?? 'Invalid token or no data found');
        }

        return $data;
    }
}
