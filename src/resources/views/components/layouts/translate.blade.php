<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Translation Manager</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        @livewireStyles
    </head>
    <body class="flex flex-col min-h-screen bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-white transition-colors duration-300 font-['Instrument_Sans']">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
            {{ $slot ?? '' }}
        </div>

        @livewireScripts
    </body>
</html>