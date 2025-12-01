<?php

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use TerpDev\CubeWikiPackage\Services\Requests\KnowledgeBaseRequest;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

it('will cache when caching is enabled', function () {
    config()->set('cubewikipackage.cache_enabled', true);
    config()->set('cubewikipackage.cache_duration', 5);

    $mockClient = new MockClient([
        KnowledgeBaseRequest::class => MockResponse::make([
            'success' => true,
            'data' => ['value' => 'from-api'],
        ], 200),
    ]);

    $service = new WikiCubeApiService();
    $service->withMockClient($mockClient);

    // Eerste call -> echte (fake) HTTP-call
    $service->fetchKnowledgeBase('test-token');

    // Tweede call -> zou uit cache moeten komen
    $service->fetchKnowledgeBase('test-token');

    // Caching AAN: maar 1 HTTP-call
    $mockClient->assertSentCount(1);
});
it('will not cache when caching is disabled', function () {
    config()->set('cubewikipackage.cache_enabled', false);
    config()->set('cubewikipackage.cache_duration', 5);

    $mockClient = new MockClient([
        KnowledgeBaseRequest::class => MockResponse::make([
            'success' => true,
            'data' => ['value' => 'no-cache'],
        ], 200),
    ]);

    $service = new WikiCubeApiService();
    $service->withMockClient($mockClient);

    $service->fetchKnowledgeBase('test-token');

    $service->fetchKnowledgeBase('test-token');

    $mockClient->assertSentCount(2);
});
