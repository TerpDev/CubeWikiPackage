<?php

use Illuminate\Support\Facades\Http;

it('fetches API data from the knowledge base endpoint', function () {
    $url = config('cubewikipackage.api_url') . '/knowledge-base';

    $payload = [
        ['slug' => 'introduction', 'title' => 'Introduction'],
    ];

    Http::fake([
        $url => Http::response($payload, 200),
    ]);

    $response = Http::get($url);

    expect($response->successful())->toBeTrue();
    expect($response->json())->toEqual($payload);
});
test('', function () {
    
});
