@extends('layouts.app')

@section('content')
<div class="container-fluid p-0" style="height: calc(100vh - 60px);">
    <iframe 
        src="{{ $farmosUrl }}" 
        style="width: 100%; height: 100%; border: none;"
        id="farmos-iframe"
        title="farmOS Interface"
        allow="fullscreen"
        allowfullscreen
    ></iframe>
</div>

<script>
// Auto-resize iframe based on content
const iframe = document.getElementById('farmos-iframe');

// Handle iframe navigation - update browser URL
iframe.addEventListener('load', function() {
    try {
        // Update Laravel URL to match farmOS page (if same origin or with proper CORS)
        const iframeUrl = iframe.contentWindow.location.pathname;
        if (iframeUrl) {
            // Update browser history without reload
            const newUrl = "{{ route('admin.farmos.embed') }}?path=" + encodeURIComponent(iframeUrl);
            window.history.pushState({path: iframeUrl}, '', newUrl);
        }
    } catch (e) {
        // Cross-origin restriction - can't read iframe URL
        console.log('iframe navigation (cross-origin)');
    }
});

// Listen for messages from farmOS iframe
window.addEventListener('message', function(event) {
    // Verify origin
    if (event.origin !== "{{ config('farmos.url') }}") return;
    
    // Handle messages from farmOS (e.g., "page changed", "logout", etc.)
    console.log('Message from farmOS:', event.data);
});
</script>
@endsection
