<x-filament-panels::page>

    {{-- ========== TOKEN FORM ========== --}}
    @if (! $this->knowledgeBaseData)
        <x-filament::section
            heading="Connect to WikiCube"
            description="Enter your WikiCube API token to access your tenant’s knowledge base."
            icon="heroicon-o-key"
        >
            <form wire:submit.prevent="loadKnowledgeBase" class="space-y-6">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.defer="tokenApiToken"
                        placeholder="Paste your WikiCube API token..."
                        required
                    />
                </x-filament::input.wrapper>

                @error('tokenApiToken')
                <p class="mt-2 text-sm text-danger-600 dark:text-danger-400">
                    {{ $message }}
                </p>
                @enderror

                <x-filament::button
                    type="submit"
                    color="primary"
                    icon="heroicon-o-rocket-launch"
                    class="mt-4"
                >
                    Load Knowledge Base
                </x-filament::button>
            </form>
        </x-filament::section>
    @else

        {{-- ========== TENANT OVERVIEW ========== --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-building-office" class="h-6 w-6" />
                    {{ $knowledgeBaseData['tenant']['name'] ?? 'Unknown Tenant' }}
                </div>
            </x-slot>

            <x-slot name="description">
                ID: {{ $knowledgeBaseData['tenant']['id'] ?? '-' }}
                • Slug: {{ $knowledgeBaseData['tenant']['slug'] ?? '-' }}
            </x-slot>

            @php
                $applications = $knowledgeBaseData['applications'] ?? [];
                $totalCategories = collect($applications)->sum(fn ($a) => count($a['categories'] ?? []));
                $totalPages = collect($applications)->sum(
                    fn ($a) => collect($a['categories'] ?? [])->sum(fn ($c) => count($c['pages'] ?? []))
                );
            @endphp

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-filament::card>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary-600">{{ count($applications) }}</div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Applications</p>
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary-600">{{ $totalCategories }}</div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Categories</p>
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary-600">{{ $totalPages }}</div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pages</p>
                    </div>
                </x-filament::card>
            </div>
        </x-filament::section>

        {{-- ========== APPLICATIONS ========== --}}
        @forelse ($applications as $application)
            <x-filament::section
                :heading="$application['name']"
                :description="count($application['categories'] ?? []) . ' categories • ' .
                              collect($application['categories'] ?? [])->sum(fn ($c) => count($c['pages'] ?? [])) . ' pages'"
                icon="heroicon-o-rectangle-stack"
                collapsible
                collapsed
            >
                <div class="space-y-4">
                    @forelse ($application['categories'] ?? [] as $category)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex items-center gap-2">
                                    <x-filament::icon icon="heroicon-o-folder" class="h-5 w-5 text-primary-600" />
                                    <h4 class="font-semibold text-gray-900 dark:text-white">
                                        {{ $category['name'] }}
                                    </h4>
                                </div>
                            </div>

                            {{-- Pages list --}}
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($category['pages'] ?? [] as $page)
                                    <div x-data="{ open: false }" class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <button
                                            type="button"
                                            @click="open = !open"
                                            class="flex w-full items-center justify-between gap-2 text-left"
                                        >
                                            <div class="flex items-center gap-2">
                                                <x-filament::icon icon="heroicon-o-document-text" class="h-4 w-4 text-gray-500" />
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ $page['title'] }}
                                                </span>
                                            </div>
                                            <x-filament::icon
                                                icon="heroicon-o-chevron-down"
                                                class="h-4 w-4 text-gray-400 transition-transform"
                                                x-bind:class="{ 'rotate-180': open }"
                                            />
                                        </button>

                                        <div
                                            x-show="open"
                                            x-collapse
                                            class="mt-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-900"
                                        >
                                            <div class="prose prose-sm max-w-none dark:prose-invert">
                                                {!! \Illuminate\Support\Str::markdown($page['content'] ?? '') !!}
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No pages in this category.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            No categories in this application.
                        </div>
                    @endforelse
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <div class="py-12 text-center">
                    <x-filament::icon icon="heroicon-o-document-text" class="mx-auto mb-4 h-16 w-16 text-gray-300 dark:text-gray-600" />
                    <h3 class="mb-1 text-lg font-medium text-gray-900 dark:text-white">No applications found</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">There are no applications available for this tenant.</p>
                </div>
            </x-filament::section>
        @endforelse
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
