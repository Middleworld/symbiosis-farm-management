@extends('layouts.app')

@section('content')
@php
    $appUrl = config('app.url');
    $appHost = parse_url($appUrl, PHP_URL_HOST);
    $appScheme = parse_url($appUrl, PHP_URL_SCHEME) ?: request()->getScheme();
    $fieldkitUrl = 'https://fieldkit.soilsync.shop'; // Default fallback
    
    if ($appHost && strpos($appHost, 'admin.') === 0) {
        $baseDomain = substr($appHost, 6); // Remove 'admin.' prefix
        $fieldkitUrl = $appScheme . '://fieldkit.' . $baseDomain;
    }
@endphp
<div class="container-fluid p-0" style="height: calc(100vh - 60px);">
    <iframe 
        src="{{ $fieldkitUrl }}" 
        style="width: 100%; height: 100%; border: none;"
        title="farmOS Field Kit"
        allow="camera; geolocation; fullscreen"
        allowfullscreen
    ></iframe>
</div>
@endsection
