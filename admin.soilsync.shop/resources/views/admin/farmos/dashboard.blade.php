@extends('layouts.app')

@section('title', 'FarmOS - Dashboard')

@section('content')
<div class="container-fluid p-0" style="height: calc(100vh - 60px);">
    <iframe 
        src="{{ config('farmos.url') }}" 
        style="width: 100%; height: 100%; border: none;"
        title="farmOS Desktop"
    ></iframe>
</div>
@endsection
