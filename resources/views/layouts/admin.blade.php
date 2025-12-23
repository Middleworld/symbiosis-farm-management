@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @yield('content')
</div>
@endsection

@push('scripts')
<script src="/js/ai-helper-widget.js"></script>
@endpush
