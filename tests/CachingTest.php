<?php

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use TerpDev\CubeWikiPackage\Services\Requests\KnowledgeBaseRequest;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

it('will cache when caching is enabled', function (): void {

    config()->set('cubewikipackage.cache_enabled', true);
    config()->set('cubewikipackage.cache_duration', 5);

    $mockClient = MockClient::global([
        KnowledgeBaseRequest::class => MockResponse::make([
            'success' => true,
            'data' => ['value' => 'from-api'],
        ], 200),
    ]);

    $service = new WikiCubeApiService();

    $first = $service->fetchKnowledgeBase('test-token');

    $second = $service->fetchKnowledgeBase('test-token');

    expect($first['data']['value'])->toBe('from-api')
        ->and($second['data']['value'])->toBe('from-api');

    $mockClient->assertSentCount(1);
});
