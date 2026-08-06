<script src="{{ URL::asset('assets/libs/bootstrap/bootstrap.min.js') }}"></script>
<script src="{{ URL::asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('assets/libs/node-waves/node-waves.min.js') }}"></script>
<script src="{{ URL::asset('assets/libs/feather-icons/feather-icons.min.js') }}"></script>
<script src="{{ URL::asset('assets/js/pages/plugins/lord-icon-2.1.0.min.js') }}"></script>
<script src="{{ URL::asset('assets/js/plugins.min.js') }}"></script>
<script>
/* Legacy "Search" section titles → Rambhiya "Filters" */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.card-header .card-title, .card-header h4, .card-header h5, .card-header h6').forEach(function (el) {
        if ((el.textContent || '').trim() === 'Search') {
            el.textContent = 'Filters';
        }
    });
});
</script>
@yield('script')
@yield('script-bottom')
<script>
/* After page app.min.js: only #scrollbar scrolls — unwrap nested SimpleBar on #navbar-nav */
(function () {
    function unwrapNavbarSimpleBar() {
        var nav = document.getElementById('navbar-nav');
        if (!nav) return;
        nav.removeAttribute('data-simplebar');
        var inst = window.SimpleBar && SimpleBar.instances && SimpleBar.instances.get(nav);
        if (inst && typeof inst.unMount === 'function') {
            try { inst.unMount(); } catch (e) {}
            return;
        }
        if (!nav.querySelector(':scope > .simplebar-wrapper')) return;
        var content = nav.querySelector('.simplebar-wrapper .simplebar-content');
        if (!content) return;
        var items = Array.prototype.slice.call(content.children);
        Array.prototype.slice.call(nav.children).forEach(function (child) {
            if (child.classList && (
                child.classList.contains('simplebar-wrapper') ||
                child.classList.contains('simplebar-track') ||
                child.classList.contains('simplebar-placeholder') ||
                child.classList.contains('simplebar-height-auto-observer-wrapper')
            )) {
                nav.removeChild(child);
            }
        });
        items.forEach(function (item) { nav.appendChild(item); });
    }
    unwrapNavbarSimpleBar();
    setTimeout(unwrapNavbarSimpleBar, 50);
    setTimeout(unwrapNavbarSimpleBar, 400);
    window.addEventListener('load', function () {
        unwrapNavbarSimpleBar();
        setTimeout(unwrapNavbarSimpleBar, 200);
    });
})();
</script>
