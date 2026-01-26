{{-- add the lang.blade.php into your view or layout --}}
@include('lang')

{{-- declare all your strings with proper translate syntrax --}}
<h2>@__t('we can translate')</h2>
<h2>@__t('any application')</h2>
<h2>@__t('any supported language')</h2>