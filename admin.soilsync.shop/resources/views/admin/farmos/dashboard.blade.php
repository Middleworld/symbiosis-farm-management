@extends('layouts.app')

@section('title', 'FarmOS Dashboard')

@section('content')
@php
    $appUrl = config('app.url');
    $appHost = parse_url($appUrl, PHP_URL_HOST);
    $appScheme = parse_url($appUrl, PHP_URL_SCHEME) ?: request()->getScheme();
    $farmosUrl = config('farmos.url'); // Default from config
    
    if ($appHost && strpos($appHost, 'admin.') === 0) {
        $baseDomain = substr($appHost, 6); // Remove 'admin.' prefix
        $farmosUrl = $appScheme . '://farmos.' . $baseDomain;
    }
@endphp
<div class="container-fluid p-0" style="height: calc(100vh - 60px);">
    <iframe 
        src="{{ $farmosUrl }}/" 
        style="width: 100%; height: 100%; border: none;"
        title="farmOS Dashboard"
        allow="fullscreen"
        allowfullscreen
    ></iframe>
</div>
@endsection
