@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ $client->name }} is requesting permission to access your account</div>

                <div class="card-body">
                    <p class="mb-4">
                        <strong>{{ $client->name }}</strong> is requesting access to your account.
                        This application will be able to:
                    </p>

                    <ul class="list-group mb-4">
                        @foreach($scopes as $scope)
                            <li class="list-group-item">{{ $scope->description }}</li>
                        @endforeach
                    </ul>

                    <form method="post" action="{{ route('passport.authorizations.approve') }}">
                        @csrf

                        <input type="hidden" name="state" value="{{ $request->state }}">
                        <input type="hidden" name="client_id" value="{{ $client->id }}">

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success" name="approve" value="1">
                                Authorize
                            </button>

                            <button type="submit" class="btn btn-danger" name="deny" value="1">
                                Deny
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection