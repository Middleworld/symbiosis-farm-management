@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-seedling"></i> {{ $formTitle ?? 'farmOS Quick Form' }}
                    </h4>
                    <div>
                        <a href="{{ $farmosUrl }}" target="_blank" class="btn btn-sm btn-light">
                            <i class="fas fa-external-link-alt"></i> Open in farmOS
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="alert alert-info m-3 mb-0" id="loading-message">
                        <i class="fas fa-spinner fa-spin"></i> Loading farmOS form... 
                        <small class="d-block mt-1">If you're not logged into farmOS, the form will redirect you to login first.</small>
                    </div>
                    
                    <iframe 
                        src="{{ $farmosUrl }}{{ strpos($farmosUrl, '?') !== false ? '&' : '?' }}iframe_embed=1" 
                        style="width: 100%; min-height: 800px; border: none; display: none;"
                        id="farmos-quickform"
                        title="farmOS Quick Form"
                        onload="handleIframeLoad()"
                    ></iframe>
                </div>
            </div>

            @if(isset($backUrl))
            <div class="mt-3">
                <a href="{{ $backUrl }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to {{ $backLabel ?? 'Previous Page' }}
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
let iframeLoaded = false;

function handleIframeLoad() {
    const iframe = document.getElementById('farmos-quickform');
    const loadingMessage = document.getElementById('loading-message');
    
    // Hide loading, show iframe
    loadingMessage.style.display = 'none';
    iframe.style.display = 'block';
    
    iframeLoaded = true;
    
    // Inject CSS to hide farmOS sidebar and toolbar
    try {
        const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;
        
        // Create and inject style tag
        const style = iframeDocument.createElement('style');
        style.textContent = `
            /* Hide farmOS UI elements when embedded */
            #toolbar-administration,
            .toolbar,
            .toolbar-bar,
            .toolbar-menu-administration,
            #block-gin-branding,
            #block-gin-local-actions,
            #block-gin-breadcrumbs,
            .region-header,
            .gin-sidebar-left,
            .gin-sidebar,
            #gin-sidebar,
            .layout-region-node-secondary {
                display: none !important;
            }
            
            /* Full width content */
            .gin--vertical-toolbar .region-content,
            .gin--vertical-toolbar .gin-sidebar-left ~ .region-content,
            .layout-content,
            #block-gin-content {
                margin-left: 0 !important;
                margin-right: 0 !important;
                padding-left: 1rem !important;
                max-width: 100% !important;
            }
            
            /* Remove toolbar padding */
            body.toolbar-fixed,
            body.gin--vertical-toolbar {
                padding-top: 0 !important;
                padding-left: 0 !important;
            }
            
            /* Full width container */
            .layout-container,
            .gin-layer-wrapper {
                margin: 0 !important;
                max-width: 100% !important;
            }
        `;
        
        iframeDocument.head.appendChild(style);
        
        // Auto-resize based on content
        const height = iframeDocument.body.scrollHeight;
        if (height > 500) {
            iframe.style.minHeight = height + 'px';
        }
    } catch (e) {
        // Cross-origin - can't access iframe content
        console.log('Cross-origin iframe - cannot inject styles or auto-resize');
    }
}

// Listen for form submission success from farmOS
window.addEventListener('message', function(event) {
    // Verify origin
    const farmosOrigin = "{{ parse_url(config('farmos.url'), PHP_URL_SCHEME) . '://' . parse_url(config('farmos.url'), PHP_URL_HOST) }}";
    if (event.origin !== farmosOrigin) return;
    
    console.log('Message from farmOS:', event.data);
    
    // Handle different message types
    if (event.data.type === 'formSubmitted') {
        // Show success message
        alert('✅ Form submitted successfully!\n\nLog created in farmOS.');
        
        // Optionally redirect back
        @if(isset($redirectAfterSubmit) && $redirectAfterSubmit)
        setTimeout(() => {
            window.location.href = "{{ $backUrl ?? route('admin.dashboard') }}";
        }, 1500);
        @endif
    }
});

// Auto-reload if iframe takes too long (might be stuck on login)
setTimeout(() => {
    if (!iframeLoaded) {
        const loadingMessage = document.getElementById('loading-message');
        loadingMessage.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i> 
            <strong>Taking longer than expected...</strong>
            <p class="mb-2">You may need to log into farmOS first. 
            <a href="{{ config('farmos.url') }}" target="_blank">Click here to open farmOS in a new tab</a>, 
            log in, then refresh this page.</p>
        `;
    }
}, 5000);
</script>

<style>
/* Make iframe responsive */
#farmos-quickform {
    transition: opacity 0.3s ease;
}
</style>
@endsection
