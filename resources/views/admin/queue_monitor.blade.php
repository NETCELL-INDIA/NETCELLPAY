@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h3>Queue Monitor</h3>
    <p>Pending jobs: {{ $jobsCount }}</p>
    <p>Failed jobs: {{ $failedCount }}</p>

    <h4>Recent Failed Jobs</h4>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Connection</th><th>Queue</th><th>Exception</th><th>Failed At</th></tr></thead>
        <tbody>
            @foreach($recentFailed as $f)
                <tr>
                    <td>{{ $f->id }}</td>
                    <td>{{ $f->connection }}</td>
                    <td>{{ $f->queue }}</td>
                    <td style="max-width:500px; overflow:auto">{{ $f->exception }}</td>
                    <td>{{ $f->failed_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
