@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Deliveries Index</h1>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <a href="{{ route('deliveries.create') }}" class="btn btn-primary">Create Delivery</a>
            <a href="{{ route('packing_slips.print') }}" class="btn btn-secondary">Print Packing Slips</a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deliveries as $delivery)
                        <tr>
                            <td>{{ $delivery->id }}</td>
                            <td>{{ $delivery->address }}</td>
                            <td>{{ $delivery->status }}</td>
                            <td>
                                <a href="{{ route('deliveries.show', $delivery->id) }}" class="btn btn-info">View</a>
                                <a href="{{ route('deliveries.edit', $delivery->id) }}" class="btn btn-warning">Edit</a>
                                <form action="{{ route('deliveries.destroy', $delivery->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection