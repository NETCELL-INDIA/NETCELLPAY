@php
    $msg = $body ?? ($msg ?? '');
@endphp
@include('admin.emails.email', ['subject' => $subject ?? 'Welcome', 'msg' => $msg])
