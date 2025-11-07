<x-filament-panels::page>
    <div class="space-y-8">
        @if (! $knowledgeBaseData)
            <section class="rounded-xl border border-gray-200 bg-white p-8 shadow dark:border-gray-700 dark:bg-gray-800">
                <header class="mb-6 flex items-center gap-3">
                    <div class="rounded-lg bg-primary-100 p-3 dark:bg-primary-900/30">
{{--                        <x-heroicon-o-key class="h-6 w-6 text-primary-600 dark:text-primary-400" />--}}
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Enter your API token</h2>
                </header>

                <form wire:submit="loadKnowledgeBase" class="space-y-5 p-8">
                    <label class="block">
                        <span class="sr-only">API token</span>
                        <input
                            type="text"
                            wire:model="apiToken"
                            placeholder="Paste your API token…"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        />
                    </label>

                    @error('apiToken')
                    <p class="text-sm font-medium text-red-600 dark:text-red-400 flex items-center gap-2">
{{--                        <x-heroicon-o-exclamation-triangle class="h-4 w-4" />--}}
                        {{ $message }}
                    </p>
                    @enderror

                    @if ($errorMessage)
                        <div class="rounded-lg border border-red-300 bg-red-50 p-4 dark:border-red-700 dark:bg-red-900/20">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200 flex items-start gap-2">
                                <x-filament::icon icon="heroicon-o-exclamation-circle" class="mt-0.5 h-5 w-5" />
                                {{ $errorMessage }}
                            </p>
                        </div>
                    @endif

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="loadKnowledgeBase" class="inline-flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-rocket-launch" class="h-5 w-5" />
                            Load knowledge base
                        </span>
                        <span wire:loading wire:target="loadKnowledgeBase" class="inline-flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 animate-spin" />
                            Loading…
                        </span>
                    </button>
                </form>
            </section>
        @endif

        {{-- Knowledge Base Content --}}
        @if ($knowledgeBaseData)
            <section class="space-y-8">
                {{-- Tenant card --}}
                <div class="rounded-xl border-2 border-primary-200 bg-primary-50 p-8 dark:border-primary-700 dark:bg-primary-900/20">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <div class="mb-2 flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-building-office" class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                                <h3 class="text-2xl font-bold text-primary-900 dark:text-primary-100">
                                    {{ $knowledgeBaseData['tenant']['name'] ?? 'Unknown tenant' }}
                                </h3>
                            </div>
                            <p class="text-sm text-primary-800 dark:text-primary-300">
                                ID: {{ $knowledgeBaseData['tenant']['id'] }} • Slug: {{ $knowledgeBaseData['tenant']['slug'] }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <x-filament::button color="gray" size="sm" wire:click="refreshData" wire:loading.attr="disabled">
                                <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4" wire:loading.class="animate-spin" wire:target="refreshData" />
                            </x-filament::button>
                            <x-filament::button color="danger" size="sm" wire:click="clearToken">
                                <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                            </x-filament::button>
                        </div>
                    </div>

                    @php
                        $applications    = $knowledgeBaseData['applications'] ?? [];
                        $totalCategories = collect($applications)->sum(fn ($app) => count($app['categories'] ?? []));
                        $totalPages      = collect($applications)->sum(fn ($app) => collect($app['categories'] ?? [])->sum(fn ($cat) => count($cat['pages'] ?? [])));
                    @endphp

                    <div class="mt-6 grid grid-cols-3 gap-4">
                        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-900">
                            <div class="text-2xl font-bold text-primary-700 dark:text-primary-400">
                                {{ count($applications) }}
                            </div>
                            <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">Applications</div>
                        </div>
                        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-900">
                            <div class="text-2xl font-bold text-primary-700 dark:text-primary-400">
                                {{ $totalCategories }}
                            </div>
                            <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">Categories</div>
                        </div>
                        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-900">
                            <div class="text-2xl font-bold text-primary-700 dark:text-primary-400">
                                {{ $totalPages }}
                            </div>
                            <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">Pages</div>
                        </div>
                    </div>
                </div>

                {{-- Applications --}}
                @forelse ($applications as $application)
                    <article class="rounded-xl border-2 border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
                        {{-- Header --}}
                        <header class="border-b border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-900">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <x-filament::icon icon="heroicon-o-device-phone-mobile" class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $application['name'] }}
                                    </h4>
                                </div>
                                <span class="rounded-full bg-primary-100 px-3 py-1 text-xs font-medium text-primary-800 dark:bg-primary-900/30 dark:text-primary-300">
                                    {{ count($application['categories'] ?? []) }} categories •
                                    {{ collect($application['categories'] ?? [])->sum(fn ($c) => count($c['pages'] ?? [])) }} pages
                                </span>
                            </div>
                        </header>

                        {{-- Categories --}}
                        @forelse ($application['categories'] ?? [] as $category)
                            <section class="border-b border-gray-100 p-5 pl-10 last:border-0 dark:border-gray-700">
                                <div class="mb-3 flex items-center gap-2">
                                    <x-filament::icon icon="heroicon-o-folder" class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                                    <h5 class="font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $category['name'] }}
                                    </h5>
                                </div>

                                {{-- Pages --}}
                                @forelse ($category['pages'] ?? [] as $page)
                                    <div x-data="{ open: false }" @click="open = !open" class="mb-2 ml-6 cursor-pointer rounded-r border-l-4 border-gray-300 py-2 pl-4 hover:border-primary-500 hover:bg-primary-50 dark:border-gray-600 dark:hover:bg-primary-900/20">
                                        <div class="flex items-center gap-2">
                                            <x-filament::icon icon="heroicon-o-document-text" class="h-4 w-4 text-gray-500" />
                                            <span class="flex-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ $page['title'] }}
                                            </span>
                                            <x-filament::icon icon="heroicon-o-chevron-down" class="h-4 w-4 text-gray-400" ::class="{ 'rotate-180': open }" />
                                        </div>

                                        <div x-show="open" x-collapse class="mt-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                                            <div class="prose prose-sm max-w-none dark:prose-invert">
                                                {!! \Illuminate\Support\Str::markdown($page['content'] ?? '') !!}
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="ml-6 text-sm italic text-gray-500 dark:text-gray-400">No pages in this category</p>
                                @endforelse
                            </section>
                        @empty
                            <div class="p-5 text-center text-sm text-gray-500 dark:text-gray-400">
                                No categories in this application
                            </div>
                        @endforelse
                    </article>
                @empty
                    <div class="rounded-xl border border-gray-200 bg-white p-12 text-center shadow dark:border-gray-700 dark:bg-gray-800">
                        <x-filament::icon icon="heroicon-o-document-text" class="mx-auto mb-4 h-16 w-16 text-gray-300 dark:text-gray-600" />
                        <h3 class="mb-1 text-lg font-medium text-gray-900 dark:text-white">No applications found</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">There are no applications available for this tenant.</p>
                    </div>
                @endforelse
            </section>
        @endif
    </div>
</x-filament-panels::page>
