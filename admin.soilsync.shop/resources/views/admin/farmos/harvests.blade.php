@extends('layouts.app')

@section('title', 'FarmOS - Harvest Logs')

@section('content')
<div class="container-fluid p-0" style="height: calc(100vh - 60px);">
    <iframe 
        src="{{ config('farmos.url') }}/logs/harvest" 
        style="width: 100%; height: 100%; border: none;"
        title="farmOS Harvest Logs"
    ></iframe>
</div>
@endsection
