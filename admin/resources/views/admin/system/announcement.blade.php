@extends('layouts.master')
@section('title') Announcement @endsection
@section('css')
<link href="{{ URL::asset('assets/libs/quill/quill.min.css') }}" rel="stylesheet" type="text/css" />
<style>
.news-page .card{
    border:1px solid var(--rb-card-border,#e9ebec);
    border-radius:.5rem;
    box-shadow:0 1px 2px rgba(56,65,74,.08);
    overflow:hidden;
}
.news-page .card-header{
    background:var(--rb-blue,#405189)!important;
    border-bottom:0;
    padding:.8rem 1.15rem;
}
.news-page .card-header .card-title{
    color:#fff;
    font-weight:600;
    font-size:1rem;
    margin:0;
}
.news-page .btn-add-news,
.news-page .btn-save-news{
    background:var(--rb-success,#0ab39c)!important;
    border-color:var(--rb-success,#0ab39c)!important;
    color:#fff!important;
    font-weight:600;
    border-radius:.35rem;
    padding:.42rem .95rem;
}
.news-page .btn-add-news:hover,
.news-page .btn-save-news:hover{
    background:#099885!important;
    border-color:#099885!important;
    color:#fff!important;
}
.news-page .btn-cancel-news{
    background:var(--rb-danger,#f06548)!important;
    border-color:var(--rb-danger,#f06548)!important;
    color:#fff!important;
    font-weight:600;
    border-radius:.35rem;
    padding:.42rem .95rem;
}
.news-page .btn-cancel-news:hover{
    background:#d9573d!important;
    border-color:#d9573d!important;
    color:#fff!important;
}
.news-page .btn-back-list{
    background:transparent!important;
    border:1px solid rgba(255,255,255,.55)!important;
    color:#fff!important;
    font-weight:500;
    border-radius:.35rem;
    padding:.35rem .8rem;
}
.news-page .btn-back-list:hover{
    background:rgba(255,255,255,.12)!important;
    color:#fff!important;
}
.news-list-wrap table thead th{
    background:var(--rb-table-head,#405189)!important;
    color:#fff!important;
    font-weight:600;
    white-space:nowrap;
    border-color:var(--rb-blue-dark,#364574)!important;
    vertical-align:middle;
}
.news-list-wrap .news-cell{
    font-size:.98rem;
    font-weight:500;
    color:var(--rb-title,#212529);
    max-width:520px;
    white-space:pre-wrap;
    word-break:break-word;
}
.news-page .form-check-input{
    width:1.05em;
    height:1.05em;
    margin-top:.2em;
    border-color:#adb5bd;
    cursor:pointer;
}
.news-page .form-check-input:checked{
    background-color:var(--rb-blue,#405189);
    border-color:var(--rb-blue,#405189);
}
.news-form-wrap .card-body{
    background:#fff;
    padding:1.25rem 1.35rem 1rem;
}
.news-form-wrap .form-section{
    margin-bottom:1.15rem;
}
.news-form-wrap .section-label{
    display:block;
    color:var(--rb-muted,#878a99);
    font-size:.78rem;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:.04em;
    margin-bottom:.65rem;
}
.news-form-wrap .role-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(180px,280px));
    gap:.55rem 2.5rem;
}
@media (max-width:576px){
    .news-form-wrap .role-grid{grid-template-columns:1fr}
}
.news-form-wrap .form-check{
    display:flex;
    align-items:center;
    gap:.55rem;
    margin:0;
    min-height:1.6rem;
}
.news-form-wrap .form-check-label{
    color:var(--rb-title,#212529);
    font-weight:500;
    cursor:pointer;
    line-height:1.2;
}
.news-form-wrap .form-label{
    color:var(--rb-title,#212529);
    font-weight:600;
    font-size:.9rem;
    margin-bottom:.4rem;
}
.news-form-wrap .form-control{
    border:1px solid #ced4da;
    border-radius:.35rem;
    min-height:40px;
    background:#fff;
}
.news-form-wrap .form-control:focus{
    border-color:var(--rb-blue,#405189);
    box-shadow:0 0 0 .15rem rgba(64,81,137,.15);
}
.news-form-wrap .news-label{
    color:var(--rb-title,#212529);
    font-weight:700;
    font-size:.9rem;
    letter-spacing:.03em;
    margin-bottom:.5rem;
}
.news-form-wrap .editor-shell{
    border:1px solid #ced4da;
    border-radius:.4rem;
    overflow:visible;
    background:#fff;
}
.news-form-wrap .ql-toolbar.ql-snow{
    border:0!important;
    border-bottom:1px solid #e9ebec!important;
    background:#f8f9fb!important;
    border-radius:.4rem .4rem 0 0;
}
.news-form-wrap .ql-container.ql-snow{
    border:0!important;
    min-height:220px;
    font-size:1rem;
}
.news-form-wrap .ql-editor{
    min-height:220px;
    max-height:360px;
}
.news-form-wrap .form-footer{
    display:flex;
    align-items:center;
    gap:.65rem;
    margin:0 -1.35rem -1rem;
    padding:.9rem 1.35rem;
    border-top:1px solid #eef0f3;
    background:#fafbfc;
}
</style>
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') System @endslot
@slot('title') Announcement @endslot
@endcomponent

<div class="news-page">
    <div class="row news-list-wrap" id="newsListPanel">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">News List</h4>
                    <button type="button" class="btn btn-add-news" id="btnAddNews">Add News</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:50px">#</th>
                                    <th style="width:140px">Title</th>
                                    <th>News</th>
                                    <th style="width:120px">Create Date</th>
                                    <th style="width:120px">Expiry Date</th>
                                    <th style="width:90px">Status</th>
                                    <th style="width:100px">Action</th>
                                </tr>
                            </thead>
                            <tbody id="newsBody">
                                <tr><td colspan="7" class="text-center text-muted py-4">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row news-form-wrap d-none" id="newsFormPanel">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0" id="newsFormTitle">Add News</h4>
                    <button type="button" class="btn btn-back-list btn-sm" id="btnBackList">← Back to List</button>
                </div>
                <div class="card-body">
                    <form id="newsForm">
                        @csrf
                        <input type="hidden" name="id" id="news_id" value="">

                        <div class="form-section">
                            <span class="section-label">Show to roles</span>
                            <div class="role-grid">
                                @forelse($roles as $role)
                                <div class="form-check">
                                    <input class="form-check-input target-role" type="checkbox" name="target_roles[]"
                                        value="{{ $role->id }}" id="role_{{ $role->id }}">
                                    <label class="form-check-label" for="role_{{ $role->id }}">{{ $role->role_name }}</label>
                                </div>
                                @empty
                                <div class="text-muted small">No user roles found.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="news_title">Title</label>
                                    <input type="text" class="form-control" name="title" id="news_title"
                                        maxlength="191" placeholder="Enter Title" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="news_expiry_date">Expiry Date</label>
                                    <input type="date" class="form-control" name="expiry_date" id="news_expiry_date">
                                </div>
                            </div>
                        </div>

                        <div class="form-section mb-3">
                            <div class="news-label">NEWS</div>
                            <div class="editor-shell">
                                <textarea name="message" id="news_message" class="d-none"></textarea>
                                <div id="news_editor"></div>
                            </div>
                        </div>

                        <div class="form-footer">
                            <button type="submit" class="btn btn-save-news" id="btnSaveNews">Save News</button>
                            <button type="button" class="btn btn-cancel-news" id="btnCancelNews">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script src="{{ URL::asset('assets/libs/quill/quill.min.js') }}"></script>
<script>
var csrf = '{{ csrf_token() }}';
var newsEditor = null;

function toast(title, text, icon) {
    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        customClass: { confirmButton: 'btn btn-primary w-xs mt-2' },
        buttonsStyling: false,
        showCloseButton: true
    });
}

function syncNewsMessage() {
    if (!newsEditor) return;
    var html = newsEditor.root.innerHTML;
    if (html === '<p><br></p>') html = '';
    $('#news_message').val(html);
}

function loadNews() {
    $.post('{{ route("announcementList") }}', {_token: csrf}, function (res) {
        $('#newsBody').html(res.rows || '<tr><td colspan="7" class="text-center text-muted py-4">No news found</td></tr>');
    }, 'json').fail(function () {
        $('#newsBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load</td></tr>');
    });
}

function showList() {
    $('#newsFormPanel').addClass('d-none');
    $('#newsListPanel').removeClass('d-none');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showForm(edit) {
    $('#newsFormTitle').text(edit ? 'Edit News' : 'Add News');
    $('#newsListPanel').addClass('d-none');
    $('#newsFormPanel').removeClass('d-none');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetRoles(selected) {
    selected = selected || [];
    $('.target-role').each(function () {
        var id = parseInt($(this).val(), 10);
        $(this).prop('checked', selected.indexOf(id) !== -1);
    });
}

function initNewsEditor(html) {
    html = html || '';
    if (typeof Quill === 'undefined') {
        $('#news_message').removeClass('d-none').val(html);
        return;
    }

    var $shell = $('.editor-shell');
    $shell.html('<textarea name="message" id="news_message" class="d-none"></textarea><div id="news_editor"></div>');
    newsEditor = null;

    newsEditor = new Quill('#news_editor', {
        theme: 'snow',
        placeholder: 'Write announcement in English or Kannada...',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                [{ font: [] }, { size: ['small', false, 'large', 'huge'] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ color: [] }, { background: [] }],
                [{ script: 'sub' }, { script: 'super' }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ indent: '-1' }, { indent: '+1' }],
                [{ align: [] }],
                ['link', 'blockquote'],
                ['clean']
            ]
        }
    });

    if (html) {
        newsEditor.root.innerHTML = html;
    }
    syncNewsMessage();
    newsEditor.on('text-change', syncNewsMessage);
}

function openAddForm() {
    $('#newsForm')[0].reset();
    $('#news_id').val('');
    resetRoles([]);
    showForm(false);
    initNewsEditor('');
}

function openEditForm(data) {
    $('#news_id').val(data.id || '');
    $('#news_title').val(data.title || '');
    $('#news_expiry_date').val(data.expiry_date || '');
    resetRoles(data.target_roles || []);
    showForm(true);
    initNewsEditor(data.message || '');
}

$('#btnAddNews').on('click', openAddForm);
$('#btnCancelNews, #btnBackList').on('click', showList);

$(document).on('click', '.btn-edit-news', function () {
    var id = $(this).data('id');
    $.post('{{ route("announcementGet") }}', {_token: csrf, id: id}, function (res) {
        if (res.type !== 'success') {
            toast('Error', res.message || 'Failed', 'error');
            return;
        }
        openEditForm(res.data || {});
    }, 'json');
});

$(document).on('click', '.btn-delete-news', function () {
    var id = $(this).data('id');
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.post('{{ route("announcementDelete") }}', {_token: csrf, id: id}, function (res) {
            if (res.type === 'success') {
                Swal.fire('Deleted!', res.message, 'success');
                loadNews();
            } else {
                toast('Error', res.message || 'Failed', 'error');
            }
        }, 'json');
    });
});

$(document).on('change', '.announcement-status', function () {
    var $el = $(this);
    var id = $el.data('id');
    var status = $el.is(':checked') ? 1 : 0;
    $.post('{{ route("announcementToggle") }}', {_token: csrf, id: id, status: status}, function (res) {
        if (res.type !== 'success') {
            $el.prop('checked', !status);
            toast('Error', res.message || 'Failed', 'error');
        }
    }, 'json').fail(function () {
        $el.prop('checked', !status);
        toast('Error', 'Failed to update status', 'error');
    });
});

$('#newsForm').on('submit', function (e) {
    e.preventDefault();
    syncNewsMessage();
    var message = ($('#news_message').val() || '').trim();
    if (!message || message === '<p><br></p>') {
        toast('Error', 'News content is required', 'error');
        return;
    }
    var $btn = $('#btnSaveNews');
    $btn.prop('disabled', true).text('Please wait...');
    $.ajax({
        url: '{{ route("announcementSave") }}',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function (res) {
            $btn.prop('disabled', false).text('Save News');
            if (res && res.type === 'success') {
                toast('Success', res.message || 'Saved', 'success');
                showList();
                loadNews();
            } else {
                toast('Error', (res && res.message) ? res.message : 'Save failed', 'error');
            }
        },
        error: function (xhr) {
            $btn.prop('disabled', false).text('Save News');
            var msg = 'Something went wrong';
            try {
                var res = xhr.responseJSON;
                if (res && res.message) {
                    msg = res.message;
                } else if (res && res.errors) {
                    var first = Object.values(res.errors)[0];
                    msg = Array.isArray(first) ? first[0] : String(first);
                } else if (xhr.responseText) {
                    var m = xhr.responseText.match(/<title>(.*?)<\/title>/i)
                        || xhr.responseText.match(/SQLSTATE\[[^\]]+\]:[^<\n]+/i)
                        || xhr.responseText.match(/message["']\s*:\s*["']([^"']+)/i);
                    if (m && m[1]) msg = m[1].trim();
                    else if (xhr.status) msg = 'HTTP ' + xhr.status + ' — request failed';
                }
            } catch (err) {}
            toast('Error', msg, 'error');
        }
    });
});

loadNews();
</script>
@endsection
