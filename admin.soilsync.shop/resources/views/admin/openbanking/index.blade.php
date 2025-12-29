@extends('layouts.app')

@section('title', 'Open Banking - Bank Connections')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <div class="d-flex align-items-center">
                        <h6 class="mb-0">Open Banking - Bank Connections</h6>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    
                    {{-- Success/Error Messages --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show mx-4 mt-3" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mx-4 mt-3" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show mx-4 mt-3" role="alert">
                            {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Connected Banks --}}
                    @if($connections->count() > 0)
                        <div class="mx-4 mt-4">
                            <h6 class="text-uppercase text-body text-xs font-weight-bolder">Connected Banks</h6>
                            
                            @foreach($connections as $connection)
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                                    <i class="ni ni-building text-lg opacity-10"></i>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <h6 class="mb-0">{{ $connection->bank_name }}</h6>
                                                <p class="text-sm text-secondary mb-0">
                                                    Status: 
                                                    @if($connection->status === 'authorized')
                                                        <span class="badge badge-sm bg-gradient-success">Authorized</span>
                                                    @elseif($connection->status === 'registered')
                                                        <span class="badge badge-sm bg-gradient-warning">Registered</span>
                                                    @else
                                                        <span class="badge badge-sm bg-gradient-secondary">{{ ucfirst($connection->status) }}</span>
                                                    @endif
                                                    
                                                    @if($connection->token_expires_at)
                                                        | Token expires: {{ $connection->token_expires_at->diffForHumans() }}
                                                    @endif
                                                </p>
                                                @if($connection->accounts->count() > 0)
                                                    <p class="text-xs text-muted mb-0 mt-1">
                                                        {{ $connection->accounts->count() }} account(s) connected
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="col-auto">
                                                @if($connection->status === 'registered')
                                                    <a href="{{ route('admin.openbanking.authorize', $connection) }}" class="btn btn-sm btn-primary mb-0">
                                                        Authorize Access
                                                    </a>
                                                @elseif($connection->status === 'authorized')
                                                    <form action="{{ route('admin.openbanking.sync-accounts', $connection) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-info mb-0">
                                                            <i class="fas fa-sync"></i> Sync Accounts
                                                        </button>
                                                    </form>
                                                    
                                                    @if($connection->isTokenExpired())
                                                        <form action="{{ route('admin.openbanking.refresh-token', $connection) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-warning mb-0">
                                                                Refresh Token
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif
                                                
                                                <form action="{{ route('admin.openbanking.disconnect', $connection) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger mb-0" onclick="return confirm('Disconnect from {{ $connection->bank_name }}?')">
                                                        Disconnect
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- Accounts List --}}
                                        @if($connection->accounts->where('is_active', true)->count() > 0)
                                            <div class="mt-3">
                                                <hr class="horizontal dark">
                                                <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-2">Accounts</h6>
                                                <div class="table-responsive">
                                                    <table class="table align-items-center mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Account</th>
                                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Type</th>
                                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Balance</th>
                                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Last Updated</th>
                                                                <th class="text-secondary opacity-7"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($connection->accounts->where('is_active', true) as $account)
                                                                <tr>
                                                                    <td>
                                                                        <p class="text-sm font-weight-bold mb-0">
                                                                            {{ $account->nickname ?? 'Account ' . $account->account_id }}
                                                                        </p>
                                                                        <p class="text-xs text-secondary mb-0">
                                                                            {{ $account->formatted_account_number }}
                                                                        </p>
                                                                    </td>
                                                                    <td>
                                                                        <p class="text-xs text-secondary mb-0">
                                                                            {{ $account->account_type }}
                                                                            @if($account->account_subtype)
                                                                                <br>{{ $account->account_subtype }}
                                                                            @endif
                                                                        </p>
                                                                    </td>
                                                                    <td class="align-middle text-center">
                                                                        @if($account->balance !== null)
                                                                            <span class="text-sm font-weight-bold">
                                                                                {{ $account->currency }} {{ number_format($account->balance, 2) }}
                                                                            </span>
                                                                        @else
                                                                            <span class="text-xs text-secondary">N/A</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="align-middle text-center">
                                                                        <span class="text-xs">
                                                                            {{ $account->balance_updated_at ? $account->balance_updated_at->diffForHumans() : 'Never' }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="align-middle text-end">
                                                                        <form action="{{ route('admin.openbanking.sync-transactions', $account) }}" method="POST" class="d-inline">
                                                                            @csrf
                                                                            <button type="submit" class="btn btn-sm btn-info mb-0">
                                                                                Sync Transactions
                                                                            </button>
                                                                        </form>
                                                                        <a href="{{ route('admin.openbanking.account-details', $account) }}" class="btn btn-sm btn-outline-secondary mb-0">
                                                                            View Details
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Available Banks to Connect --}}
                    <div class="mx-4 mt-4">
                        <h6 class="text-uppercase text-body text-xs font-weight-bolder">Connect New Bank</h6>
                        
                        <div class="row mt-3">
                            @foreach($availableBanks as $bank)
                                @php
                                    $alreadyConnected = $connections->firstWhere('bank_id', $bank['id']);
                                @endphp
                                
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <img src="{{ $bank['logo'] }}" alt="{{ $bank['name'] }}" class="img-fluid mb-3" style="max-height: 60px;">
                                            <h6 class="mb-2">{{ $bank['name'] }}</h6>
                                            <p class="text-xs text-secondary mb-3">
                                                Sandbox Environment<br>
                                                APIs: {{ implode(', ', $bank['supported_apis']) }}
                                            </p>
                                            
                                            @if($alreadyConnected)
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    Already Connected
                                                </button>
                                            @else
                                                <form action="{{ route('admin.openbanking.register') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="bank_id" value="{{ $bank['id'] }}">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        Connect Bank
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
