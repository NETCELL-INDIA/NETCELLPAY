@php
    $profileNav = [
        ['route' => 'myProfile', 'label' => 'My Profile', 'icon' => 'ri-user-3-line'],
        ['route' => 'changePassword', 'label' => 'Change Password', 'icon' => 'ri-lock-password-line'],
        ['route' => 'pinReset', 'label' => 'PIN Reset', 'icon' => 'ri-key-2-line'],
        ['route' => 'loginHistory', 'label' => 'Login History', 'icon' => 'ri-history-line'],
    ];
    $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
@endphp
<style>
    .rb-profile-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-bottom: 1rem;
        padding: 0.35rem;
        background: #f3f6f9;
        border: 1px solid #e9ebec;
        border-radius: 0.4rem;
    }
    .rb-profile-tabs a {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.85rem;
        border-radius: 0.3rem;
        font-size: 0.84rem;
        font-weight: 600;
        color: #495057;
        text-decoration: none;
        border: 1px solid transparent;
    }
    .rb-profile-tabs a:hover {
        color: #405189;
        background: #fff;
    }
    .rb-profile-tabs a.active {
        color: #fff;
        background: #405189;
        border-color: #405189;
    }
</style>
<nav class="rb-profile-tabs" aria-label="Profile sections">
    @foreach($profileNav as $item)
        <a href="{{ route($item['route']) }}" class="{{ $currentRoute === $item['route'] ? 'active' : '' }}">
            <i class="{{ $item['icon'] }}"></i> {{ $item['label'] }}
        </a>
    @endforeach
</nav>
