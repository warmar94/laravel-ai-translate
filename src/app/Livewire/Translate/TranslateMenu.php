<?php

namespace App\Livewire\Translate;

use Livewire\Component;
use App\Models\Translate\TranslationUrl;
use App\Models\Translate\TranslationProgress;
use App\Models\Translate\MissingTranslation;
use App\Services\Translate\URLCollector;
use App\Services\Translate\StringExtractor;
use App\Services\Translate\AITranslator;
use App\Jobs\Translate\ScanUrlForStringsJob;
use App\Jobs\Translate\TranslateStringBatchJob;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

#[Layout('layouts.translate')]
#[Title('Translation Manager')]
class TranslateMenu extends Component
{
    // Tab
    public string $activeTab = 'urls';

    // URL inputs
    public string $bulkUrls = '';
    public string $apiEndpointInput = '';
    public string $urlFilter = '';

    // Counts
    public int $totalUrls = 0;
    public int $totalApiEndpoints = 0;
    public int $totalMissingKeys = 0;

    protected $logProcess;

    // Progress tracking
    public $stringExtractionProgress = [
        'total' => 0,
        'completed' => 0,
        'failed' => 0,
        'percentage' => 0,
        'status' => 'idle',
    ];
    public $translationProgress = [];

    // Status
    public $statusMessage = '';
    public $statusType = 'info';
    public $isProcessing = false;

    // Stats
    public int $totalKeysInEnJson = 0;

    // Translation status tab
    public array $translationStatus = [];

    // String editor
    public string $editingLocale = '';
    public string $stringSearch = '';
    public array $editableStrings = [];
    public bool $showStringEditor = false;

    // Missing keys tab
    public string $missingKeysFilter = '';
    public string $missingKeysLocaleFilter = '';
    public array $missingKeysGrouped = [];
    public array $translatingKeyIds = [];
    public array $missingKeysTranslatingLocales = [];

    public function mount()
    {
        $this->logProcess = config('translation.log_process', false);
        $this->loadProgress();
        $this->refreshCounts();
        $this->loadStats();
        $this->loadTranslationStatus();
    }

    // ─── URL Management ───//

    public function refreshCounts()
    {
        $this->totalUrls = TranslationUrl::extractable()->count();
        $this->totalApiEndpoints = TranslationUrl::apiEndpoints()->count();
        $this->totalMissingKeys = MissingTranslation::targetLocales()->count();
    }

    public function getRegularUrlsProperty()
    {
        $query = TranslationUrl::regularUrls()->orderBy('id', 'desc');
        if ($this->urlFilter) {
            $query->where('url', 'like', '%' . $this->urlFilter . '%');
        }
        return $query->get();
    }

    public function getApiEndpointsProperty()
    {
        return TranslationUrl::apiEndpoints()->orderBy('id', 'desc')->get();
    }

    public function addBulkUrls()
    {
        $input = trim($this->bulkUrls);
        if (empty($input)) {
            $this->statusMessage = "Please enter at least one URL.";
            $this->statusType = 'warning';
            return;
        }

        $lines = array_filter(array_map('trim', explode("\n", $input)));
        $collector = new URLCollector();
        $added = $collector->addBulk($lines);
        $skipped = count($lines) - $added;

        $this->statusMessage = "Added {$added} new URL(s)." . ($skipped > 0 ? " {$skipped} duplicate(s) skipped." : '');
        $this->statusType = 'success';
        $this->bulkUrls = '';
        $this->refreshCounts();
    }

    public function addApiEndpoints()
    {
        $input = trim($this->apiEndpointInput);
        if (empty($input)) {
            $this->statusMessage = "Please enter at least one API endpoint.";
            $this->statusType = 'warning';
            return;
        }

        $lines = array_filter(array_map('trim', explode("\n", $input)));
        $collector = new URLCollector();
        $totalAdded = $collector->collectFromApiEndpoints($lines);

        $this->statusMessage = "Processed " . count($lines) . " API endpoint(s). {$totalAdded} new URL(s) collected.";
        $this->statusType = 'success';
        $this->apiEndpointInput = '';
        $this->refreshCounts();
    }

    public function refreshApiEndpoints()
    {
        $collector = new URLCollector();
        $added = $collector->refreshAllApiEndpoints();
        $this->statusMessage = "Refreshed all API endpoints. {$added} new URL(s) added.";
        $this->statusType = 'success';
        $this->refreshCounts();
    }

    public function removeUrl(int $id)
    {
        $collector = new URLCollector();
        $collector->removeById($id);
        $this->refreshCounts();
        $this->statusMessage = "URL removed.";
        $this->statusType = 'success';
    }

    public function toggleUrlActive(int $id)
    {
        $collector = new URLCollector();
        $collector->toggleActive($id);
        $this->refreshCounts();
    }

    public function clearRegularUrls()
    {
        $collector = new URLCollector();
        $collector->clearRegularUrls();
        $this->refreshCounts();
        $this->statusMessage = "All regular URLs cleared.";
        $this->statusType = 'success';
    }

    public function clearApiEndpoints()
    {
        $collector = new URLCollector();
        $collector->clearApiEndpoints();
        $this->refreshCounts();
        $this->statusMessage = "All API endpoints cleared.";
        $this->statusType = 'success';
    }

    public function clearAllUrls()
    {
        $collector = new URLCollector();
        $collector->clearAll();
        $this->refreshCounts();
        $this->statusMessage = "Everything cleared.";
        $this->statusType = 'success';
    }

    // ─── Stats ───//

    public function loadStats()
    {
        $extractor = new StringExtractor();
        $keys = $extractor->getAllKeys('en');
        $this->totalKeysInEnJson = count($keys);
    }

    public function loadTranslationStatus()
    {
        $extractor = new StringExtractor();
        $enKeys = $extractor->getAllKeys('en');
        $totalEn = count($enKeys);

        $translator = new AITranslator();
        $locales = $translator->getTargetLocales();
        $languages = config('translation.languages', []);

        $this->translationStatus = [];

        foreach ($locales as $locale) {
            $targetKeys = $extractor->getAllKeys($locale);
            $translated = 0;

            foreach ($targetKeys as $key => $value) {
                if ($value !== $key && !empty($value)) {
                    $translated++;
                }
            }

            $percentage = $totalEn > 0 ? round(($translated / $totalEn) * 100, 1) : 0;

            $this->translationStatus[$locale] = [
                'name' => $languages[$locale] ?? strtoupper($locale),
                'locale' => $locale,
                'total_en' => $totalEn,
                'total_target' => count($targetKeys),
                'translated' => $translated,
                'missing' => $totalEn - $translated,
                'percentage' => $percentage,
            ];
        }
    }

    // ─── Progress ───//

    public function loadProgress()
    {
        $extraction = TranslationProgress::stringExtraction()->first();
        if ($extraction) {
            $this->stringExtractionProgress = [
                'total' => $extraction->total,
                'completed' => $extraction->completed,
                'failed' => $extraction->failed,
                'percentage' => $extraction->percentage,
                'status' => $extraction->status,
            ];
        }

        $translator = new AITranslator();
        $locales = $translator->getTargetLocales();

        foreach ($locales as $locale) {
            $progress = TranslationProgress::translation()->forLocale($locale)->first();
            if ($progress) {
                $this->translationProgress[$locale] = [
                    'total' => $progress->total,
                    'completed' => $progress->completed,
                    'failed' => $progress->failed,
                    'percentage' => $progress->percentage,
                    'status' => $progress->status,
                ];
            } else {
                $this->translationProgress[$locale] = [
                    'total' => 0, 'completed' => 0, 'failed' => 0,
                    'percentage' => 0, 'status' => 'idle',
                ];
            }
        }

        $this->loadStats();
    }

    // ─── Collect Strings ───//

    public function collectStrings()
    {
        try {
            $collector = new URLCollector();
            $urls = $collector->getExtractableUrls();

            if (empty($urls)) {
                $this->statusMessage = "No active URLs to extract from.";
                $this->statusType = 'error';
                return;
            }

            TranslationProgress::updateOrCreate(
                ['type' => 'string_extraction', 'locale' => null],
                [
                    'total' => count($urls),
                    'completed' => 0,
                    'failed' => 0,
                    'started_at' => now(),
                    'updated_at' => now(),
                    'completed_at' => null,
                ]
            );

            $delay = config('translation.urls.delay_between_requests', 1);

            foreach ($urls as $url) {
                ScanUrlForStringsJob::dispatch($url, $delay);
            }

            $this->statusMessage = "Started collecting strings from " . count($urls) . " URLs!";
            $this->statusType = 'success';
            $this->isProcessing = true;
            $this->loadProgress();
        } catch (\Exception $e) {
            $this->statusMessage = "Error: " . $e->getMessage();
            $this->statusType = 'error';
        }
    }

    // ─── Translate ───//

    public function translateAll()
    {
        try {
            $extractor = new StringExtractor();
            $sourceLocale = config('translation.source_locale', 'en');
            $sourceKeys = $extractor->getAllKeys($sourceLocale);

            if (empty($sourceKeys)) {
                $this->statusMessage = "No strings found in {$sourceLocale}.json.";
                $this->statusType = 'error';
                return;
            }

            $translator = new AITranslator();
            if (!$translator->isConfigured()) {
                $this->statusMessage = "OpenAI API key not configured.";
                $this->statusType = 'error';
                return;
            }

            $locales = $translator->getTargetLocales();
            $batchSize = config('translation.translation.batch_size', 20);
            $totalUntranslated = 0;

            foreach ($locales as $locale) {
                $existing = $extractor->getAllKeys($locale);
                $untranslated = [];

                foreach ($sourceKeys as $key => $value) {
                    if (!isset($existing[$key]) || $existing[$key] === $key) {
                        $untranslated[$key] = $value;
                    }
                }

                if (empty($untranslated)) continue;

                $totalUntranslated += count($untranslated);

                TranslationProgress::updateOrCreate(
                    ['type' => 'translation', 'locale' => $locale],
                    [
                        'total' => count($untranslated),
                        'completed' => 0,
                        'failed' => 0,
                        'started_at' => now(),
                        'updated_at' => now(),
                        'completed_at' => null,
                    ]
                );

                $batches = array_chunk($untranslated, $batchSize, true);
                foreach ($batches as $batch) {
                    TranslateStringBatchJob::dispatch($batch, $locale);
                }
            }

            if ($totalUntranslated == 0) {
                $this->statusMessage = "All strings are already translated!";
                $this->statusType = 'info';
            } else {
                $this->statusMessage = "Started translating {$totalUntranslated} strings to " . count($locales) . " languages!";
                $this->statusType = 'success';
                $this->isProcessing = true;
            }

            $this->loadProgress();
        } catch (\Exception $e) {
            $this->statusMessage = "Error: " . $e->getMessage();
            $this->statusType = 'error';
        }
    }

    // ─── Missing Keys ───//

    public function loadMissingKeys()
    {
        $query = MissingTranslation::targetLocales()->recentFirst();

        if ($this->missingKeysLocaleFilter) {
            $query->forLocale($this->missingKeysLocaleFilter);
        }

        if ($this->missingKeysFilter) {
            $query->where('key', 'like', '%' . $this->missingKeysFilter . '%');
        }

        $missing = $query->get();

        $this->missingKeysGrouped = [];
        foreach ($missing as $row) {
            $this->missingKeysGrouped[$row->locale][] = [
                'id' => $row->id,
                'key' => $row->key,
                'occurrences' => $row->occurrences,
                'first_seen' => $row->first_seen->diffForHumans(),
                'last_seen' => $row->last_seen->diffForHumans(),
            ];
        }

        // Clean up finished batch locale indicators
        foreach ($this->missingKeysTranslatingLocales as $locale => $status) {
            $progress = $this->translationProgress[$locale] ?? null;
            $hasKeys = isset($this->missingKeysGrouped[$locale]);
            if (!$hasKeys || ($progress && in_array($progress['status'], ['completed', 'idle']))) {
                unset($this->missingKeysTranslatingLocales[$locale]);
            }
        }

        // Clean up finished individual key indicators
        if (!empty($this->translatingKeyIds)) {
            $this->translatingKeyIds = MissingTranslation::whereIn('id', $this->translatingKeyIds)
                ->pluck('id')
                ->toArray();
        }

        $this->refreshCounts();
    }

    public function updatedMissingKeysFilter()
    {
        $this->loadMissingKeys();
    }

    public function updatedMissingKeysLocaleFilter()
    {
        $this->loadMissingKeys();
    }

    public function translateMissingKey(int $id)
    {
        if (in_array($id, $this->translatingKeyIds)) return;

        $missing = MissingTranslation::find($id);
        if (!$missing) return;

        $translator = new AITranslator();
        if (!$translator->isConfigured()) {
            $this->statusMessage = "OpenAI API key not configured.";
            $this->statusType = 'error';
            return;
        }

        // Dispatch as a single-item batch job
        TranslateStringBatchJob::dispatch(
            [$missing->key => $missing->key],
            $missing->locale
        );

        // Remove from missing table immediately
        $missing->delete();

        $this->statusMessage = "Queued \"{$missing->key}\" for {$missing->locale} translation.";
        $this->statusType = 'success';

        $this->loadMissingKeys();
    }

    public function translateAllMissingForLocale(string $locale)
    {
        if (isset($this->missingKeysTranslatingLocales[$locale])) return;

        $translator = new AITranslator();
        if (!$translator->isConfigured()) {
            $this->statusMessage = "OpenAI API key not configured.";
            $this->statusType = 'error';
            return;
        }

        $missing = MissingTranslation::forLocale($locale)->get();
        if ($missing->isEmpty()) {
            $this->statusMessage = "No missing keys for {$locale}.";
            $this->statusType = 'info';
            return;
        }

        $batchSize = config('translation.translation.batch_size', 20);
        $strings = $missing->pluck('key', 'key')->toArray();

        TranslationProgress::updateOrCreate(
            ['type' => 'translation', 'locale' => $locale],
            [
                'total' => count($strings),
                'completed' => 0,
                'failed' => 0,
                'started_at' => now(),
                'updated_at' => now(),
                'completed_at' => null,
            ]
        );

        $batches = array_chunk($strings, $batchSize, true);
        foreach ($batches as $batch) {
            TranslateStringBatchJob::dispatch($batch, $locale);
        }

        MissingTranslation::clearLocale($locale);

        $this->missingKeysTranslatingLocales[$locale] = true;
        $this->isProcessing = true;

        $localeName = config('translation.languages.' . $locale, strtoupper($locale));
        $this->statusMessage = "Queued " . count($strings) . " missing keys for {$localeName}.";
        $this->statusType = 'success';

        $this->loadMissingKeys();
        $this->loadProgress();
    }

    public function refreshMissingKeysProgress()
    {
        $this->loadProgress();
        $this->loadMissingKeys();
        $this->loadTranslationStatus();

        // Check if all batch translations finished
        $anyRunning = false;
        foreach ($this->missingKeysTranslatingLocales as $locale => $status) {
            $progress = $this->translationProgress[$locale] ?? null;
            if ($progress && $progress['status'] === 'running') {
                $anyRunning = true;
            } else {
                unset($this->missingKeysTranslatingLocales[$locale]);
            }
        }

        if (!$anyRunning) {
            $this->missingKeysTranslatingLocales = [];
            if (empty($this->translatingKeyIds)) {
                $this->isProcessing = false;
            }
        }
    }

    public function clearResolvedMissingKeys()
    {
        $extractor = new StringExtractor();
        $cleared = 0;

        $allMissing = MissingTranslation::targetLocales()->get();

        foreach ($allMissing as $missing) {
            $targetKeys = $extractor->getAllKeys($missing->locale);

            if (
                isset($targetKeys[$missing->key]) &&
                $targetKeys[$missing->key] !== $missing->key &&
                !empty($targetKeys[$missing->key])
            ) {
                $missing->delete();
                $cleared++;
            }
        }

        $this->statusMessage = "Cleared {$cleared} resolved missing key(s).";
        $this->statusType = 'success';
        $this->loadMissingKeys();
    }

    public function clearAllMissingKeys()
    {
        MissingTranslation::clearAll();
        $this->statusMessage = "All missing keys cleared.";
        $this->statusType = 'success';
        $this->loadMissingKeys();
    }

    // ─── String Editor ───//

    public function openLocaleEditor(string $locale)
    {
        $this->editingLocale = $locale;
        $this->stringSearch = '';
        $this->loadEditableStrings();
        $this->showStringEditor = true;
    }

    public function loadEditableStrings()
    {
        $extractor = new StringExtractor();
        $enKeys = $extractor->getAllKeys('en');
        $targetKeys = $extractor->getAllKeys($this->editingLocale);

        $this->editableStrings = [];

        foreach ($enKeys as $key => $enValue) {
            $targetValue = $targetKeys[$key] ?? '';
            $isTranslated = !empty($targetValue) && $targetValue !== $key;

            if ($this->stringSearch) {
                $search = strtolower($this->stringSearch);
                if (
                    strpos(strtolower($key), $search) === false &&
                    strpos(strtolower($targetValue), $search) === false
                ) {
                    continue;
                }
            }

            $this->editableStrings[] = [
                'key' => $key,
                'en' => $enValue,
                'target' => $targetValue,
                'is_translated' => $isTranslated,
            ];
        }
    }

    public function updatedStringSearch()
    {
        $this->loadEditableStrings();
    }

    public function saveStringTranslation(string $key, string $value)
    {
        if (empty($this->editingLocale)) return;

        $filePath = lang_path("{$this->editingLocale}.json");
        $existing = [];
        if (file_exists($filePath)) {
            $existing = json_decode(file_get_contents($filePath), true) ?? [];
        }

        $existing[$key] = $value;
        ksort($existing);

        file_put_contents(
            $filePath,
            json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $this->loadEditableStrings();
        $this->loadTranslationStatus();
        $this->statusMessage = "Translation saved.";
        $this->statusType = 'success';
    }

    public function translateSingleString(string $key)
    {
        if (empty($this->editingLocale)) return;

        $extractor = new StringExtractor();
        $enKeys = $extractor->getAllKeys('en');
        $sourceText = $enKeys[$key] ?? $key;

        $translator = new AITranslator();
        if (!$translator->isConfigured()) {
            $this->statusMessage = "OpenAI API key not configured.";
            $this->statusType = 'error';
            return;
        }

        $translated = $translator->translate($sourceText, $this->editingLocale);

        if ($translated) {
            $this->saveStringTranslation($key, $translated);
            $this->statusMessage = "AI translated successfully.";
            $this->statusType = 'success';
        } else {
            $this->statusMessage = "AI translation failed.";
            $this->statusType = 'error';
        }
    }

    public function closeStringEditor()
    {
        $this->showStringEditor = false;
        $this->editingLocale = '';
        $this->editableStrings = [];
        $this->stringSearch = '';
    }

    // ─── General ───//

    public function refreshProgress()
    {
        $this->loadProgress();
        $this->loadTranslationStatus();
    }

    public function resetProgress()
    {
        TranslationProgress::truncate();
        $this->loadProgress();
        $this->statusMessage = "Progress reset successfully!";
        $this->statusType = 'success';
    }

    public function updatedActiveTab($value)
    {
        if ($value === 'missing') {
            $this->loadMissingKeys();
        }
    }

    public function render()
    {
        return view('livewire.translate.translate-menu')
            ->layoutData([
                'title' => 'Translation Manager',
                'description' => 'Translation and localization management tools.',
                'keywords' => 'translation, localization, language tools',
            ]);
    }
}