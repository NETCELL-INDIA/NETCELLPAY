@extends('layouts.master')
@section('title') New campaign @endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1') Smart Notifications @endslot
@slot('title') New campaign @endslot
@endcomponent

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<p class="text-muted">The phone tray shows the title, short text, and image only. Fonts, colors, and lists are kept for the in-app message. Every campaign still requires promotional consent.</p>

<form method="post" action="{{ route('smartNotificationPreview') }}" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-lg-7">
            <div class="card" style="border-top: 3px solid #175CD3;">
                <div class="card-header"><h4 class="card-title mb-0">Message</h4></div>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-6">
                            <label class="form-label" for="campaign_name">Campaign name</label>
                            <input class="form-control" id="campaign_name" name="campaign_name" maxlength="120" value="{{ old('campaign_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="title">Notification title</label>
                            <input class="form-control" id="title" name="title" maxlength="120" required value="{{ old('title') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="message">Short description</label>
                            <textarea class="form-control" id="message" name="message" rows="3" maxlength="1000" required>{{ old('message') }}</textarea>
                            <div class="form-text">This is the text on the phone tray.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Full message</label>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <select class="form-select form-select-sm" style="width:auto" id="fontName">
                                    <option value="Arial">Arial</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Tahoma">Tahoma</option>
                                    <option value="Verdana">Verdana</option>
                                </select>
                                <select class="form-select form-select-sm" style="width:auto" id="fontSize">
                                    @foreach([10,12,14,16,18,24,28,36] as $size)
                                    <option value="{{ $size }}">{{ $size }} px</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-cmd="bold"><b>B</b></button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-cmd="italic"><i>I</i></button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-cmd="underline"><u>U</u></button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-cmd="strikeThrough"><s>S</s></button>
                                <input type="color" id="textColor" value="#111111" title="Text color">
                                <input type="color" id="backColor" value="#ffffff" title="Background color">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-cmd="justifyLeft">Left</button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-cmd="justifyCenter">Center</button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-cmd="insertUnorderedList">Bullets</button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-cmd="insertOrderedList">Numbers</button>
                                <select class="form-select form-select-sm" style="width:auto" id="lineHeight">
                                    <option value="1">Line 1</option>
                                    <option value="1.5" selected>Line 1.5</option>
                                    <option value="2">Line 2</option>
                                </select>
                                <select class="form-select form-select-sm" style="width:auto" id="emoji">
                                    <option value="">Emoji</option>
                                    @foreach(['🙂','🎉','✅','⚠️','📱','💰'] as $emoji)
                                    <option value="{{ $emoji }}">{{ $emoji }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-outline-secondary" type="button" id="clearFormat">Clear</button>
                            </div>
                            <div id="richBody" contenteditable="true" class="form-control" style="min-height:140px"></div>
                            <textarea name="body_html" id="body_html" hidden></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="category">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                @foreach($categories as $value => $label)
                                <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="priority">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                @foreach(['normal' => 'Normal', 'high' => 'High', 'low' => 'Low'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="button_label">Button label</label>
                            <input class="form-control" id="button_label" name="button_label" maxlength="40" value="{{ old('button_label') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="image_url">Image URL</label>
                            <input class="form-control" id="image_url" name="image_url" maxlength="500" placeholder="https://" value="{{ old('image_url') }}">
                            <input class="form-control mt-1" type="file" name="image_file" accept="image/jpeg,image/png">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="banner_url">Banner URL</label>
                            <input class="form-control" id="banner_url" name="banner_url" maxlength="500" placeholder="https://" value="{{ old('banner_url') }}">
                            <input class="form-control mt-1" type="file" name="banner_file" accept="image/jpeg,image/png">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="deep_link">Deep link</label>
                            <input class="form-control" id="deep_link" name="deep_link" maxlength="500" placeholder="https://netcellpay.in/..." value="{{ old('deep_link') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="action_url">Button URL</label>
                            <input class="form-control" id="action_url" name="action_url" maxlength="500" placeholder="https://" value="{{ old('action_url') }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">Who receives it</h4></div>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-6">
                            <label class="form-label" for="audience">Audience</label>
                            <select class="form-select" id="audience" name="audience" required>
                                <option value="all" @selected(old('audience', 'all') === 'all')>All active users</option>
                                <option value="role:6" @selected(old('audience') === 'role:6')>Retailers</option>
                                <option value="role:5" @selected(old('audience') === 'role:5')>Distributors</option>
                                <option value="role:4" @selected(old('audience') === 'role:4')>Master Distributors</option>
                                <option value="new" @selected(old('audience') === 'new')>New registrations, last 30 days</option>
                                <option value="inactive" @selected(old('audience') === 'inactive')>Inactive users</option>
                                <option value="user" @selected(old('audience') === 'user')>One mobile number</option>
                                <option value="userid" @selected(old('audience') === 'userid')>One user id</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="mobile_number">Mobile number</label>
                            <input class="form-control" id="mobile_number" name="mobile_number" value="{{ old('mobile_number') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="user_id">User ID</label>
                            <input class="form-control" id="user_id" name="user_id" value="{{ old('user_id') }}">
                        </div>
                        @if($states->isNotEmpty())
                        <div class="col-md-4">
                            <label class="form-label" for="state_id">State</label>
                            <select class="form-select" id="state_id" name="state_id">
                                <option value="">All states</option>
                                @foreach($states as $state)
                                <option value="{{ $state->id }}" @selected((string) old('state_id') === (string) $state->id)>{{ $state->state_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <div class="col-12"><div class="form-text">District targeting is not available because user records do not store a district.</div></div>
                        @endif
                        @if($hasRegistrationDate)
                        <div class="col-md-4">
                            <label class="form-label" for="registered_from">Registered from</label>
                            <input class="form-control" type="date" id="registered_from" name="registered_from" value="{{ old('registered_from') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="registered_to">Registered to</label>
                            <input class="form-control" type="date" id="registered_to" name="registered_to" value="{{ old('registered_to') }}">
                        </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label" for="send_mode">When</label>
                            <select class="form-select" id="send_mode" name="send_mode">
                                <option value="now" @selected(old('send_mode', 'now') === 'now')>Send now</option>
                                <option value="schedule" @selected(old('send_mode') === 'schedule')>Schedule</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="schedule_date">Schedule date</label>
                            <input class="form-control" type="date" id="schedule_date" name="schedule_date" value="{{ old('schedule_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="schedule_time">Schedule time</label>
                            <input class="form-control" type="time" id="schedule_time" name="schedule_time" value="{{ old('schedule_time') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="expires_date">Expiry date</label>
                            <input class="form-control" type="date" id="expires_date" name="expires_date" value="{{ old('expires_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="expires_time">Expiry time</label>
                            <input class="form-control" type="time" id="expires_time" name="expires_time" value="{{ old('expires_time') }}">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Review recipients</button>
                            <a class="btn btn-outline-secondary" href="{{ route('smartNotifications') }}">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">Phone tray</h4></div>
                <div class="card-body">
                    <div style="max-width:320px;border:1px solid #ddd;border-radius:16px;padding:12px;background:#111;color:#fff">
                        <div class="d-flex gap-2">
                            <div style="width:28px;height:28px;border-radius:6px;background:#175CD3"></div>
                            <div>
                                <div style="font-size:12px;opacity:.7">NETCELL PAY</div>
                                <div id="previewTitle" style="font-weight:700"></div>
                                <div id="previewText" style="font-size:13px"></div>
                            </div>
                        </div>
                        <img id="previewImage" alt="" style="display:none;width:100%;margin-top:8px;border-radius:8px">
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">In-app message</h4></div>
                <div class="card-body">
                    <div id="previewRich" style="border:1px solid #ddd;border-radius:12px;padding:12px;min-height:120px"></div>
                    <div id="previewButton" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
@section('script')
<script>
(function () {
    var editor = document.getElementById('richBody');
    var htmlField = document.getElementById('body_html');
    function sync() {
        htmlField.value = editor.innerHTML;
        document.getElementById('previewTitle').textContent = document.getElementById('title').value || 'Title';
        document.getElementById('previewText').textContent = document.getElementById('message').value || 'Short description';
        document.getElementById('previewRich').innerHTML = editor.innerHTML;
        var image = document.getElementById('image_url').value;
        var img = document.getElementById('previewImage');
        if (image.indexOf('https://') === 0) {
            img.src = image;
            img.style.display = 'block';
        } else {
            img.style.display = 'none';
        }
        var label = document.getElementById('button_label').value;
        document.getElementById('previewButton').textContent = label ? label : '';
    }
    document.querySelectorAll('[data-cmd]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.execCommand(button.getAttribute('data-cmd'), false, null);
            sync();
        });
    });
    document.getElementById('fontName').addEventListener('change', function () {
        document.execCommand('fontName', false, this.value);
        sync();
    });
    document.getElementById('fontSize').addEventListener('change', function () {
        document.execCommand('fontSize', false, '4');
        editor.querySelectorAll('font[size]').forEach(function (node) {
            node.removeAttribute('size');
            node.style.fontSize = document.getElementById('fontSize').value + 'px';
        });
        sync();
    });
    document.getElementById('textColor').addEventListener('input', function () {
        document.execCommand('foreColor', false, this.value);
        sync();
    });
    document.getElementById('backColor').addEventListener('input', function () {
        document.execCommand('backColor', false, this.value);
        sync();
    });
    document.getElementById('lineHeight').addEventListener('change', function () {
        editor.style.lineHeight = this.value;
        sync();
    });
    document.getElementById('emoji').addEventListener('change', function () {
        if (this.value) {
            document.execCommand('insertText', false, this.value);
            this.value = '';
            sync();
        }
    });
    document.getElementById('clearFormat').addEventListener('click', function () {
        document.execCommand('removeFormat', false, null);
        sync();
    });
    ['title', 'message', 'image_url', 'button_label'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', sync);
    });
    editor.addEventListener('input', sync);
    document.querySelector('form').addEventListener('submit', sync);
    sync();
})();
</script>
@endsection
