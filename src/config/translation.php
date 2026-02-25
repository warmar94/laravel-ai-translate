<?php
// config/translation.php

return [

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    | Enable detailed logging for debugging. When false, only errors are logged.
    */
    'log_process' => false,
    
    /*
    |--------------------------------------------------------------------------
    | Source Language
    |--------------------------------------------------------------------------
    | The source language for your application. This is where strings will
    | be extracted to initially.
    */
    'source_locale' => 'en',
    
    /*
    |--------------------------------------------------------------------------
    | Target Languages
    |--------------------------------------------------------------------------
    | Define all target languages with their locale codes and full names.
    | The full name is used for AI translation prompts.
    */
    'languages' => [
        'en' => 'English',
        'ar' => 'Arabic',
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Display Names
    |--------------------------------------------------------------------------
    | How each language name appears in each language's interface
    | Structure: 'viewing_language' => ['lang_code' => 'Display Name']
    */
    'language_names' => [
        'en' => [
            'en' => 'English',
            'ar' => 'Arabic',
        ],
        'ar' => [
            'en' => 'الإنجليزية',
            'ar' => 'العربية',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Target Locales (for translation)
    |--------------------------------------------------------------------------
    | Which languages to actually translate to (excluding source language)
    */
    'target_locales' => ['ar'],

    /*
    |--------------------------------------------------------------------------
    | RTL Languages
    |--------------------------------------------------------------------------
    | Languages that should be displayed right-to-left
    */
    'rtl_languages' => ['ar'],
    
    /*
    |--------------------------------------------------------------------------
    | Language Files
    |--------------------------------------------------------------------------
    | Define which locale files exist and should be managed
    */
    'language_files' => [
        'en' => lang_path('en.json'),
        'ar' => lang_path('ar.json'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | URL Collection Settings
    |--------------------------------------------------------------------------
    */
    'urls' => [
        'delay_between_requests' => 1, // seconds
        'batch_size' => 50,
        'timeout' => 20,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | String Extraction Settings
    |--------------------------------------------------------------------------
    */
    'extraction' => [
        'scan_internal' => true, // Use internal Laravel requests
        'clear_cache' => true,   // Clear view cache before scanning
    ],
    
    /*
    |--------------------------------------------------------------------------
    | AI Translation Settings
    |--------------------------------------------------------------------------
    */
    'translation' => [
        'ai_provider' => 'openai',
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'api_key' => env('OPENAI_API_KEY'),
        'batch_size' => 20,
        'concurrent_jobs' => 5,
        'rate_limit_per_minute' => 300,
        'system_prompt' => 'You are a professional translator. Translate the following text to {language}. Return ONLY the translated text with no explanations, greetings, or additional commentary. Preserve any HTML tags, placeholders like :name, and formatting.',
        'max_retries' => 3,
    ],
];