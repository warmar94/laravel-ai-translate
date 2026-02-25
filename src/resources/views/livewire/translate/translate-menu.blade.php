{{-- resources/views/livewire/shop/admin/translate/translate-menu.blade.php --}}

<div>

    {{-- Page Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Translation Manager</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Collect translatable strings and translate with AI</p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Extractable URLs</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($totalUrls) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Keys in en.json</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($totalKeysInEnJson) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Extraction</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 capitalize">{{ $stringExtractionProgress['status'] }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg flex items-center justify-center
                    {{ $stringExtractionProgress['status'] === 'completed' ? 'bg-green-100 dark:bg-green-900/20' : ($stringExtractionProgress['status'] === 'running' ? 'bg-yellow-100 dark:bg-yellow-900/20' : 'bg-gray-100 dark:bg-zinc-800') }}">
                    @if($stringExtractionProgress['status'] === 'running')
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    @elseif($stringExtractionProgress['status'] === 'completed')
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @else
                        <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Target Languages</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ count(config('translation.target_locales', [])) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Status Message --}}
    @if($statusMessage)
        <div class="mb-6 p-4 rounded-lg border
            {{ match($statusType) {
                'success' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-300',
                'error' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-300',
                'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300',
                default => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300',
            } }}">
            {{ $statusMessage }}
        </div>
    @endif

    {{-- Tabs --}}
    <div class="mb-6 border-b border-gray-200 dark:border-zinc-700">
        <nav class="flex gap-1 -mb-px">
            @foreach([
                'urls' => 'URLs',
                'extract' => 'Extract Strings',
                'translate' => 'Translate',
                'status' => 'Translation Status',
                'config' => 'Configuration',
            ] as $tab => $label)
                <button
                    wire:click="$set('activeTab', '{{ $tab }}')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                        {{ $activeTab === $tab
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-zinc-600' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- Tab: URLs                                                              --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'urls')

        {{-- Add Regular URLs --}}
        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6 mb-6">
            <h3 class="text-md font-semibold text-gray-900 dark:text-white mb-2">Add URLs</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Paste one URL per line. These pages will be scanned for translatable strings.</p>
            <textarea
                wire:model="bulkUrls"
                placeholder="https://mywebsite.com/team&#10;https://mywebsite.com/contact&#10;https://mywebsite.com/about"
                rows="4"
                class="w-full px-3 py-2 text-sm font-mono border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
            <div class="mt-3">
                <button wire:click="addBulkUrls" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add URLs
                </button>
            </div>
        </div>

        {{-- API Endpoints --}}
        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6 mb-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-md font-semibold text-gray-900 dark:text-white">API Endpoints</h3>
                @if($totalApiEndpoints > 0)
                    <button wire:click="refreshApiEndpoints" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Re-fetch All
                    </button>
                @endif
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Paste API endpoints that return a JSON array of URLs. The endpoint is saved and its response URLs are imported.</p>
            <textarea
                wire:model="apiEndpointInput"
                placeholder="https://mywebsite.com/api/sitemaps/blog&#10;https://mywebsite.com/api/sitemaps/products"
                rows="3"
                class="w-full px-3 py-2 text-sm font-mono border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
            <div class="mt-3">
                <button wire:click="addApiEndpoints" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
                    Fetch & Import URLs
                </button>
            </div>

            {{-- Saved API Endpoints --}}
            @if(count($this->apiEndpoints) > 0)
                <div class="mt-4 border-t border-gray-200 dark:border-zinc-700 pt-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Saved Endpoints</h4>
                    <div class="space-y-2">
                        @foreach($this->apiEndpoints as $ep)
                            <div class="flex items-center justify-between p-2.5 bg-gray-50 dark:bg-zinc-800 rounded-lg group" wire:key="api-{{ $ep->id }}">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded">API</span>
                                    <span class="text-sm font-mono text-gray-700 dark:text-gray-300 truncate">{{ $ep->url }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button wire:click="toggleUrlActive({{ $ep->id }})" class="p-1 rounded hover:bg-gray-200 dark:hover:bg-zinc-700 transition-colors" title="{{ $ep->active ? 'Active' : 'Inactive' }}">
                                        @if($ep->active)
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @else
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @endif
                                    </button>
                                    <button
                                        wire:click="removeUrl({{ $ep->id }})"
                                        wire:confirm="Remove this API endpoint?"
                                        class="p-1 rounded text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 opacity-0 group-hover:opacity-100 transition-all"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2">
                        <button wire:click="clearApiEndpoints" wire:confirm="Remove all API endpoints?" class="text-xs text-red-600 dark:text-red-400 hover:underline">
                            Clear All Endpoints
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- URL Table --}}
        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    URLs
                    <span class="text-sm font-normal text-gray-500">({{ $totalUrls }} extractable)</span>
                </h3>
                <div class="flex gap-2">
                    @if($totalUrls > 0)
                        <button wire:click="clearRegularUrls" wire:confirm="Delete all regular URLs? API endpoints will be kept." class="px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                            Clear URLs
                        </button>
                    @endif
                    @if($totalUrls > 0 || $totalApiEndpoints > 0)
                        <button wire:click="clearAllUrls" wire:confirm="Delete ALL URLs and API endpoints?" class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                            Clear Everything
                        </button>
                    @endif
                </div>
            </div>

            {{-- Search --}}
            <div class="mb-4">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        wire:model.live.debounce.300ms="urlFilter"
                        type="text"
                        placeholder="Search URLs..."
                        class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-zinc-700">
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300 w-16">#</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">URL</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300 w-20">Active</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300 w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->regularUrls as $urlRecord)
                            <tr wire:key="url-{{ $urlRecord->id }}" class="border-b border-gray-100 dark:border-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-800/50">
                                <td class="py-3 px-4 text-sm text-gray-400">{{ $urlRecord->id }}</td>
                                <td class="py-3 px-4">
                                    <span class="font-mono text-sm text-gray-700 dark:text-gray-300 break-all">{{ $urlRecord->url }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <button wire:click="toggleUrlActive({{ $urlRecord->id }})" class="p-1 rounded hover:bg-gray-200 dark:hover:bg-zinc-700 transition-colors">
                                        @if($urlRecord->active)
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @else
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @endif
                                    </button>
                                </td>
                                <td class="py-3 px-4">
                                    <button
                                        wire:click="removeUrl({{ $urlRecord->id }})"
                                        wire:confirm="Delete this URL?"
                                        class="p-1 rounded text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center">
                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    <p class="text-gray-500 dark:text-gray-400">No URLs added yet</p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Add URLs above to get started</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- Tab: Extract Strings                                                   --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'extract')
        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded">Step 2</span>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Collect Translation Strings</h2>
                </div>

                @if($stringExtractionProgress['status'] !== 'idle')
                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded
                        {{ match($stringExtractionProgress['status']) {
                            'completed' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                            'running' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
                            'failed' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                            default => 'bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-gray-300',
                        } }}">
                        {{ ucfirst($stringExtractionProgress['status']) }}
                    </span>
                @endif
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Scan all active URLs and extract translatable strings to <code class="bg-gray-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded text-xs">lang/en.json</code>
            </p>

            @if($stringExtractionProgress['total'] > 0)
                <div class="mb-6">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $stringExtractionProgress['completed'] }}</span> / {{ $stringExtractionProgress['total'] }} URLs processed
                            @if($stringExtractionProgress['failed'] > 0)
                                <span class="text-red-600 dark:text-red-400">({{ $stringExtractionProgress['failed'] }} failed)</span>
                            @endif
                        </span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $stringExtractionProgress['percentage'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-3 overflow-hidden">
                        <div
                            class="h-3 rounded-full transition-all duration-500 {{ $stringExtractionProgress['status'] === 'completed' ? 'bg-green-500' : 'bg-blue-500' }}"
                            style="width: {{ $stringExtractionProgress['percentage'] }}%"
                        ></div>
                    </div>
                </div>
            @endif

            <div class="flex gap-3">
                <button
                    wire:click="collectStrings"
                    @if($totalUrls == 0) disabled @endif
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z"/></svg>
                    Collect Strings
                </button>

                <button wire:click="refreshProgress" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </button>
            </div>

            @if($totalUrls == 0)
                <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-3 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    Please add URLs first
                </p>
            @endif
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- Tab: Translate                                                         --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'translate')
        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded">Step 3</span>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Translate All Keys</h2>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Use AI to translate all collected strings to target languages.</p>

            @if(!empty($translationProgress))
                <div class="space-y-4 mb-6">
                    @foreach($translationProgress as $locale => $progress)
                        <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ config('translation.languages.' . $locale, strtoupper($locale)) }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-400 rounded uppercase">{{ $locale }}</span>

                                    @if($progress['status'] !== 'idle')
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded
                                            {{ match($progress['status']) {
                                                'completed' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                                                'running' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
                                                default => 'bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-gray-300',
                                            } }}">
                                            {{ ucfirst($progress['status']) }}
                                        </span>
                                    @endif
                                </div>

                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $progress['completed'] }}</span> / {{ $progress['total'] }}
                                    @if($progress['failed'] > 0)
                                        <span class="text-red-600 dark:text-red-400">({{ $progress['failed'] }} failed)</span>
                                    @endif
                                </span>
                            </div>

                            <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-2.5 overflow-hidden">
                                <div
                                    class="h-2.5 rounded-full transition-all duration-500 {{ $progress['status'] === 'completed' ? 'bg-green-500' : 'bg-purple-500' }}"
                                    style="width: {{ $progress['percentage'] }}%"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex gap-3">
                <button wire:click="translateAll" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                    Translate All Keys
                </button>

                <button wire:click="refreshProgress" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </button>
            </div>

            @if($totalKeysInEnJson == 0)
                <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-3 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    No keys found in en.json. Please collect strings first.
                </p>
            @endif
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- Tab: Translation Status                                                --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'status')

        @if($showStringEditor && $editingLocale)
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Editing: {{ config('translation.languages.' . $editingLocale, strtoupper($editingLocale)) }}
                        </h2>
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-400 rounded uppercase">{{ $editingLocale }}</span>
                    </div>
                    <button wire:click="closeStringEditor" class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-zinc-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mb-4 relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        wire:model.live.debounce.300ms="stringSearch"
                        type="text"
                        placeholder="Search strings..."
                        class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-gray-50 dark:bg-zinc-800">
                            <tr>
                                <th class="text-left p-3 font-medium text-gray-600 dark:text-gray-400 w-1/3">English (source)</th>
                                <th class="text-left p-3 font-medium text-gray-600 dark:text-gray-400 w-1/3">
                                    {{ config('translation.languages.' . $editingLocale, strtoupper($editingLocale)) }}
                                </th>
                                <th class="text-left p-3 font-medium text-gray-600 dark:text-gray-400 w-auto">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                            @foreach($editableStrings as $idx => $string)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50" wire:key="string-{{ $idx }}">
                                    <td class="p-3">
                                        <span class="text-gray-700 dark:text-gray-300 break-words">{{ $string['en'] }}</span>
                                    </td>
                                    <td class="p-3">
                                        <input
                                            type="text"
                                            value="{{ $string['target'] }}"
                                            class="w-full px-2 py-1.5 text-sm border rounded-md bg-white dark:bg-zinc-900 border-gray-300 dark:border-zinc-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            x-on:keydown.enter="$wire.saveStringTranslation('{{ addslashes($string['key']) }}', $el.value)"
                                            x-on:blur="if($el.value !== '{{ addslashes($string['target']) }}') $wire.saveStringTranslation('{{ addslashes($string['key']) }}', $el.value)"
                                        />
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center gap-1">
                                            <button
                                                wire:click="translateSingleString('{{ addslashes($string['key']) }}')"
                                                class="p-1 rounded text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors"
                                                title="AI Translate"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                            </button>
                                            @if($string['is_translated'])
                                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @else
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(empty($editableStrings))
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        {{ $stringSearch ? 'No strings match your search.' : 'No strings found in en.json.' }}
                    </div>
                @endif
            </div>
        @endif

        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Translation Status</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Compare target languages against en.json. Click a language to edit translations.</p>

            @if($totalKeysInEnJson > 0)
                <div class="space-y-4">
                    @foreach($translationStatus as $locale => $status)
                        <div
                            class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4 cursor-pointer hover:border-blue-300 dark:hover:border-blue-600 transition-colors"
                            wire:click="openLocaleEditor('{{ $locale }}')"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $status['name'] }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-400 rounded uppercase">{{ $locale }}</span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-semibold {{ $status['percentage'] >= 100 ? 'text-green-600 dark:text-green-400' : ($status['percentage'] >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                        {{ $status['percentage'] }}%
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </div>
                            </div>

                            <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-2.5 overflow-hidden mb-2">
                                <div
                                    class="h-2.5 rounded-full transition-all duration-500 {{ $status['percentage'] >= 100 ? 'bg-green-500' : ($status['percentage'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                    style="width: {{ $status['percentage'] }}%"
                                ></div>
                            </div>

                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $status['translated'] }} of {{ $status['total_en'] }} strings translated, {{ $status['missing'] }} remaining
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <p>No keys found in en.json. Please collect strings first.</p>
                </div>
            @endif
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- Tab: Configuration                                                     --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'config')
        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System Configuration</h2>

            <div class="grid md:grid-cols-3 gap-4 text-sm">
                @php
                    $configItems = [
                        'Source Language' => config('translation.languages.' . config('translation.source_locale'), 'English'),
                        'Target Languages' => implode(', ', array_map(fn($l) => config('translation.languages.' . $l, strtoupper($l)), config('translation.target_locales', []))),
                        'AI Model' => config('translation.translation.model'),
                        'Batch Size' => config('translation.translation.batch_size'),
                        'URL Delay' => config('translation.urls.delay_between_requests') . 's',
                        'Rate Limit' => config('translation.translation.rate_limit_per_minute') . '/min',
                    ];
                @endphp

                @foreach($configItems as $label => $value)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-800 rounded-lg">
                        <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $value }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                <button
                    wire:click="resetProgress"
                    wire:confirm="Are you sure you want to reset all progress?"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Reset All Progress
                </button>
            </div>
        </div>
    @endif

    {{-- Auto-refresh when processing --}}
    @if($isProcessing || $stringExtractionProgress['status'] === 'running' || collect($translationProgress)->contains('status', 'running'))
        <div wire:poll.3s="refreshProgress"></div>
    @endif

</div>