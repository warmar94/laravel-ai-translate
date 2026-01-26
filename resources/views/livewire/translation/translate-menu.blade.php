{{-- resources/views/livewire/translation/translate-menu.blade.php --}}

<div class="max-w-7xl mx-auto p-6 space-y-8">
    
    {{-- Header --}}
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Translation Management System</h1>
        <p class="text-gray-600">Automate translation collection and AI-powered translations</p>
        
        {{-- Quick Stats --}}
        <div class="mt-4 flex gap-6">
            <div class="text-sm">
                <span class="text-gray-600">Total URLs:</span>
                <span class="font-bold text-blue-600">{{ number_format($totalUrls) }}</span>
            </div>
            <div class="text-sm">
                <span class="text-gray-600">Keys in en.json:</span>
                <span class="font-bold text-green-600">{{ number_format($totalKeysInEnJson) }}</span>
            </div>
        </div>
    </div>

    {{-- Status Message --}}
    @if($statusMessage)
    <div class="border-s-4 p-4 rounded {{ $statusType === 'success' ? 'bg-green-50 border-green-500' : ($statusType === 'error' ? 'bg-red-50 border-red-500' : ($statusType === 'warning' ? 'bg-yellow-50 border-yellow-500' : 'bg-blue-50 border-blue-500')) }}">
        <p class="{{ $statusType === 'success' ? 'text-green-700' : ($statusType === 'error' ? 'text-red-700' : ($statusType === 'warning' ? 'text-yellow-700' : 'text-blue-700')) }}">
            {{ $statusMessage }}
        </p>
    </div>
    @endif

    {{-- Step 1: URL Collection --}}
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">
            1️⃣ Generate URLs
        </h2>
        
        <div class="grid md:grid-cols-2 gap-6">
            {{-- Manual URLs --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Manual URLs (one per line)
                </label>
                <textarea 
                    wire:model="manualUrls"
                    rows="6"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm text-black"
                    placeholder="https://licenseplate.ae/upload-plates
https://licenseplate.ae/contact
https://licenseplate.ae/about"
                ></textarea>
            </div>
            
            {{-- API Endpoints --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    API Endpoints (one per line)
                </label>
                <textarea 
                    wire:model="apiEndpoints"
                    rows="6"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm text-black"
                    placeholder="https://licenseplate.ae/api/sitemaps/plate
https://licenseplate.ae/api/sitemaps/articles
https://licenseplate.ae/api/sitemaps/articles"
                ></textarea>
                <p class="text-xs text-gray-500 mt-1">
                    Format: <code>url</code> or <code>name|url</code>
                </p>
            </div>
        </div>
        
        <div class="mt-6 flex items-center justify-between flex-wrap gap-4">
            <div class="flex gap-4">
                <button 
                    wire:click="generateUrls"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg transition duration-200"
                >
                    🔗 Generate URLs
                </button>
                
                @if($totalUrls > 0)
                <button 
                    wire:click="clearUrlsJson"
                    class="bg-gray-400 hover:bg-gray-500 text-white font-semibold px-6 py-3 rounded-lg transition duration-200"
                    onclick="return confirm('Clear all URLs?')"
                >
                    🗑️ Clear URLs
                </button>
                @endif
            </div>
            
            @if($totalUrls > 0)
            <div class="text-gray-700 font-medium">
                Total URLs: <span class="text-blue-600 text-xl">{{ number_format($totalUrls) }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Step 2: String Extraction --}}
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                2️⃣ Collect Translation Strings
            </h2>
            
            {{-- Status Badge --}}
            @if($stringExtractionProgress['status'] !== 'idle')
            <span class="px-3 py-1 rounded-full text-sm font-semibold
                {{ $stringExtractionProgress['status'] === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                {{ $stringExtractionProgress['status'] === 'running' ? 'bg-blue-100 text-blue-800 animate-pulse' : '' }}
                {{ $stringExtractionProgress['status'] === 'failed' ? 'bg-red-100 text-red-800' : '' }}
            ">
                {{ ucfirst($stringExtractionProgress['status']) }}
            </span>
            @endif
        </div>
        
        <p class="text-gray-600 mb-4">
            Scan all URLs and extract translatable strings to <code class="bg-gray-100 px-2 py-1 rounded">lang/en.json</code>
        </p>
        
        @if($stringExtractionProgress['total'] > 0)
        <div class="mb-4">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>
                    <strong>{{ $stringExtractionProgress['completed'] }}</strong> / {{ $stringExtractionProgress['total'] }} URLs processed
                    @if($stringExtractionProgress['failed'] > 0)
                        <span class="text-red-600">({{ $stringExtractionProgress['failed'] }} failed)</span>
                    @endif
                </span>
                <span class="font-semibold">{{ $stringExtractionProgress['percentage'] }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-6 relative overflow-hidden">
                <div 
                    class="bg-green-500 h-6 rounded-full transition-all duration-500 flex items-center justify-center text-white text-xs font-semibold"
                    style="width: {{ $stringExtractionProgress['percentage'] }}%"
                >
                    @if($stringExtractionProgress['percentage'] > 10)
                        {{ $stringExtractionProgress['percentage'] }}%
                    @endif
                </div>
            </div>
        </div>
        @endif
        
        <div class="flex gap-4">
            <button 
                wire:click="collectStrings"
                class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                @if($totalUrls == 0) disabled @endif
            >
                📝 Collect Strings
            </button>
            
            <button 
                wire:click="refreshProgress"
                class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg transition duration-200"
            >
                🔄 Refresh Progress
            </button>
        </div>
        
        @if($totalUrls == 0)
        <p class="text-sm text-orange-600 mt-2">⚠️ Please generate URLs first</p>
        @endif
    </div>

    {{-- Step 3: Translation --}}
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">
            3️⃣ Translate All Keys
        </h2>
        
        <p class="text-gray-600 mb-4">
            Use AI to translate all strings to target languages
        </p>
        
        @if(!empty($translationProgress))
        <div class="space-y-4 mb-6">
            @foreach($translationProgress as $locale => $progress)
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-lg uppercase">
                            {{ config('translation.languages.' . $locale, strtoupper($locale)) }}
                        </span>
                        
                        {{-- Status Badge --}}
                        @if($progress['status'] !== 'idle')
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            {{ $progress['status'] === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $progress['status'] === 'running' ? 'bg-purple-100 text-purple-800 animate-pulse' : '' }}
                        ">
                            {{ ucfirst($progress['status']) }}
                        </span>
                        @endif
                    </div>
                    
                    <span class="text-sm text-gray-600">
                        <strong>{{ $progress['completed'] }}</strong> / {{ $progress['total'] }}
                        @if($progress['failed'] > 0)
                        <span class="text-red-600">({{ $progress['failed'] }} failed)</span>
                        @endif
                    </span>
                </div>
                
                <div class="w-full bg-gray-200 rounded-full h-5 relative overflow-hidden">
                    <div 
                        class="bg-purple-500 h-5 rounded-full transition-all duration-500 flex items-center justify-center text-white text-xs font-semibold"
                        style="width: {{ $progress['percentage'] }}%"
                    >
                        @if($progress['percentage'] > 10)
                            {{ $progress['percentage'] }}%
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
        
        <button 
            wire:click="translateAll"
            class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-6 py-3 rounded-lg transition duration-200"
        >
            🌐 Translate All Keys
        </button>
        
        @if($totalKeysInEnJson == 0)
        <p class="text-sm text-orange-600 mt-2">⚠️ No keys found in en.json. Please collect strings first.</p>
        @endif
    </div>

    {{-- System Info --}}
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">
            ⚙️ System Configuration
        </h2>
        
        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-700">Source Language:</span>
                <span class="text-gray-600">
                    {{ config('translation.languages.' . config('translation.source_locale'), 'English') }}
                </span>
            </div>
            
            <div>
                <span class="font-medium text-gray-700">Target Languages:</span>
                <span class="text-gray-600">
                    @php
                        $targets = config('translation.target_locales', []);
                        $languages = config('translation.languages', []);
                        $names = array_map(fn($locale) => $languages[$locale] ?? strtoupper($locale), $targets);
                    @endphp
                    {{ implode(', ', $names) }}
                </span>
            </div>
            
            <div>
                <span class="font-medium text-gray-700">AI Model:</span>
                <span class="text-gray-600">{{ config('translation.translation.model') }}</span>
            </div>
            
            <div>
                <span class="font-medium text-gray-700">Batch Size:</span>
                <span class="text-gray-600">{{ config('translation.translation.batch_size') }}</span>
            </div>
            
            <div>
                <span class="font-medium text-gray-700">URL Delay:</span>
                <span class="text-gray-600">{{ config('translation.urls.delay_between_requests') }}s</span>
            </div>
            
            <div>
                <span class="font-medium text-gray-700">Rate Limit:</span>
                <span class="text-gray-600">{{ config('translation.translation.rate_limit_per_minute') }}/min</span>
            </div>
        </div>
        
        <div class="mt-6 flex gap-4">
            <button 
                wire:click="resetProgress"
                class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-lg transition duration-200"
                onclick="return confirm('Are you sure you want to reset all progress?')"
            >
                🗑️ Reset Progress
            </button>
        </div>
    </div>

    {{-- Auto-refresh for progress --}}
    <script>
        setInterval(() => {
            @this.call('refreshProgress');
        }, 3000); // Refresh every 3 seconds when jobs are running
    </script>
</div>