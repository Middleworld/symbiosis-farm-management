@extends('layouts.app')

@section('content')
<div class="container-fluid p-0" style="height: calc(100vh - 60px);">
    <iframe 
        src="https://fieldkit.soilsync.shop" 
        style="width: 100%; height: 100%; border: none;"
        title="farmOS Field Kit"
        allow="camera; geolocation"
    ></iframe>
</div>
@endsection
