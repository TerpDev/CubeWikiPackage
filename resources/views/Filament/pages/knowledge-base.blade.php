<x-filament-panels::page>

    {{-- ========== TOKEN FORM ========== --}}
    @if (! $this->knowledgeBaseData)
        <x-filament::section
            heading="Connect to WikiCube"
            description="Enter your WikiCube API token to access your tenant's knowledge base."
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

        {{-- ========== APPLICATION FILTER ========== --}}
        @if (count($this->getApplicationOptions()) > 0)
            <x-filament::section
                heading="Select Application"
                description="Choose which application's knowledge base you want to view"
                icon="heroicon-o-funnel"
            >
                <div class="max-w-md">
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedApplicationId">
                            <option value="">-- Select an application --</option>
                            @foreach ($this->getApplicationOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </x-filament::section>
        @endif

        {{-- ========== APPLICATIONS ========== --}}
        @forelse ($this->getFilteredApplications() as $application)
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
        @endforelse
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>

