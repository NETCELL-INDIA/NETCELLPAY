@extends('layouts.master')
@section('title') Notification / Message Settings @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Extras @endslot
@slot('title') Notification / Message Settings @endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Which messages should go?</h4>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3" style="font-size:.9rem">
                    Turn OFF any message you do not want to send. WhatsApp uses your WhatsApp API + template content.
                    Email uses email templates. Push uses Android FCM (Pusher setting).
                </p>

                <form id="messageSettingsForm">
                    @csrf
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="email_global" name="email_global" value="1" @checked($emailGlobal)>
                        <label class="form-check-label" for="email_global"><strong>Email messages (global)</strong> — master switch for all email notifications</label>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Message</th>
                                    <th class="text-center" style="width:120px">WhatsApp</th>
                                    <th class="text-center" style="width:120px">Email</th>
                                    <th class="text-center" style="width:120px">Push</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $i => $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item['label'] }}</strong>
                                            <div class="text-muted small"><code>{{ $item['slug'] }}</code></div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" name="settings[{{ $i }}][whatsapp]" value="1"
                                                    @checked((int)($item['whatsapp_enabled'] ?? 1) === 1)>
                                            </div>
                                            <input type="hidden" name="settings[{{ $i }}][slug]" value="{{ $item['slug'] }}">
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" name="settings[{{ $i }}][email]" value="1"
                                                    @checked((int)($item['email_enabled'] ?? 1) === 1)>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" name="settings[{{ $i }}][push]" value="1"
                                                    @checked((int)($item['push_enabled'] ?? 1) === 1)>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">No message types found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-success" id="saveBtn">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
$('#messageSettingsForm').on('submit', function(e) {
    e.preventDefault();
    var btn = $('#saveBtn');
    btn.prop('disabled', true).text('Saving...');
    $.ajax({
        url: '{{ route("messageSettingsSave") }}',
        method: 'post',
        data: new FormData(this),
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            Swal.fire({ title: res.type === 'success' ? 'Saved' : 'Error', text: res.message, icon: res.type === 'success' ? 'success' : 'error' });
        },
        error: function() {
            Swal.fire({ title: 'Error', text: 'Could not save settings', icon: 'error' });
        },
        complete: function() {
            btn.prop('disabled', false).text('Save Settings');
        }
    });
});
</script>
@endsection
