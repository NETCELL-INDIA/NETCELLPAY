@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h3>API Logs</h3>

    <form method="GET" class="form-inline mb-3">
        <input type="text" name="url" value="{{ $filters['url'] ?? '' }}" placeholder="URL" class="form-control mr-2" />
        <input type="text" name="txnid" value="{{ $filters['txnid'] ?? '' }}" placeholder="TXN ID" class="form-control mr-2" />
        <input type="text" name="modal" value="{{ $filters['modal'] ?? '' }}" placeholder="Modal" class="form-control mr-2" />
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control mr-2" />
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control mr-2" />
        <button class="btn btn-primary">Filter</button>
    </form>

    <table class="table table-bordered table-sm">
        <thead><tr><th>ID</th><th>URL</th><th>Modal</th><th>TXN</th><th>Request</th><th>Response</th><th>Time</th></tr></thead>
        <tbody>
            @foreach($logs as $l)
                <tr>
                    <td>{{ $l->id }}</td>
                    <td>{{ $l->url }}</td>
                    <td>{{ $l->modal }}</td>
                    <td>{{ $l->txnid }}</td>
                    <td style="max-width:300px; overflow:auto">{{ $l->request }}</td>
                    <td style="max-width:300px; overflow:auto">{{ $l->response }}</td>
                    <td>{{ $l->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $logs->appends(request()->except('page'))->links() }}
</div>
@endsection
