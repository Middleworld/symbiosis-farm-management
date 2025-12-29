@extends('layouts.app')

@section('title', 'Email Signatures')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-signature mr-2"></i>Email Signatures
                    </h5>
                    <div class="card-tools">
                        <a href="{{ route('admin.email.create-signature') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Create Signature
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($signatures->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Default</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($signatures as $signature)
                                        <tr>
                                            <td>{{ $signature->name }}</td>
                                            <td>
                                                @if($signature->is_default)
                                                    <span class="badge badge-success">Yes</span>
                                                @else
                                                    <span class="badge badge-secondary">No</span>
                                                @endif
                                            </td>
                                            <td>{{ $signature->created_at->format('M j, Y') }}</td>
                                            <td>
                                                <a href="{{ route('admin.email.edit-signature', $signature->id) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                @if(!$signature->is_default)
                                                    <form action="{{ route('admin.email.delete-signature', $signature->id) }}"
                                                          method="POST" class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to delete this signature?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">No email signatures found.</p>
                            <a href="{{ route('admin.email.create-signature') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Your First Signature
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection