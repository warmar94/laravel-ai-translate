{{-- add the lang.blade.php into your view or layout --}}
@include('lang')

{{-- declare all your strings with proper translate syntrax --}}
<h2>{{ __('this is a sentence') }}</h2>
<h2>{{ __('this is also a sentence') }}</h2>