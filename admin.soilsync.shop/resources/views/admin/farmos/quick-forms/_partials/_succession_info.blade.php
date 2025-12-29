@if(request('succession_number'))
<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle"></i>
    <strong>Succession {{ request('succession_number') }}</strong>
    @if(request('crop_name'))
        - {{ request('crop_name') }}
        @if(request('variety_name') && request('variety_name') !== 'Generic')
            ({{ request('variety_name') }})
        @endif
    @endif
</div>
@endif