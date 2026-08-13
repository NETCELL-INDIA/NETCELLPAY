@extends('layouts.master')
@section('title') User Website List @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Website @endslot
@slot('title')User Website List@endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Public website pages</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">Domain: <strong>{{ $company->domain ?? request()->getHost() }}</strong>. Edit a page, then Save. Changes show on the user website.</p>
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Path</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pages as $page)
                            <tr>
                                <td>{{ $page['name'] }}</td>
                                <td><code>{{ $page['path'] }}</code></td>
                                <td>
                                    @if(!empty($page['editable']))
                                        <button type="button" class="btn btn-sm btn-info editPage" data-slug="{{ $page['slug'] }}">Edit</button>
                                    @endif
                                    <a class="btn btn-sm btn-outline-primary" href="{{ rtrim($siteBase, '/').($page['path'] === '/' ? '/' : $page['path']) }}" target="_blank">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="pageModal" class="modal" tabindex="-1" style="display:none">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="page_form">
                @csrf
                <input type="hidden" name="slug" id="page_slug">
                <div class="modal-header">
                    <h5 class="modal-title" id="pageModalLabel">Edit page</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Page title</label>
                        <input type="text" class="form-control" name="title" id="page_title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control" name="heading" id="page_heading">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Page content</label>
                        <textarea class="form-control" name="body" id="page_body" rows="12" placeholder="Write the page text here"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="page_save_btn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
function Error_Msg(title, text, icon) {
    Swal.fire({ title: title, text: text, icon: icon });
}
$(document).on('click', '.editPage', function () {
    var slug = $(this).data('slug');
    $.post('{{ route('websitePageGet') }}', { slug: slug, _token: '{{ csrf_token() }}' }, function (res) {
        if (res.type !== 'success') {
            return Error_Msg('Error', res.message, 'error');
        }
        $('#page_slug').val(res.data.slug);
        $('#page_title').val(res.data.title || '');
        $('#page_heading').val(res.data.heading || '');
        $('#page_body').val(res.data.body || '');
        $('#pageModalLabel').text('Edit ' + (res.data.name || 'page'));
        $('#pageModal').modal('show');
    });
});
$('#page_form').on('submit', function (e) {
    e.preventDefault();
    $('#page_save_btn').prop('disabled', true).text('Saving...');
    $.post('{{ route('websitePageSave') }}', $(this).serialize(), function (data) {
        $('#page_save_btn').prop('disabled', false).text('Save');
        Error_Msg(data.type, data.message, data.type);
        if (data.type === 'success') {
            $('#pageModal').modal('hide');
        }
    }).fail(function () {
        $('#page_save_btn').prop('disabled', false).text('Save');
        Error_Msg('error', 'Unable to save.', 'error');
    });
});
</script>
@endsection
