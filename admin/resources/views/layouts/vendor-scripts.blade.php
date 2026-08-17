<script src="{{ admin_asset('assets/libs/bootstrap/bootstrap.min.js') }}"></script>
<script src="{{ admin_asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ admin_asset('assets/libs/node-waves/node-waves.min.js') }}"></script>
<script src="{{ admin_asset('assets/libs/feather-icons/feather-icons.min.js') }}"></script>
<script src="{{ admin_asset('assets/js/pages/plugins/lord-icon-2.1.0.min.js') }}"></script>
<script src="{{ admin_asset('assets/js/plugins.min.js') }}?v=20260817-plugins2"></script>
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
<script src="{{ admin_asset('assets/js/app.min.js') }}"></script>
<script>
/* After page app.min.js: keep horizontal top menu intact */
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
    function flattenMoreMenu() {
        var more = document.getElementById('sidebarMore');
        var nav = document.getElementById('navbar-nav');
        if (!more || !nav) return;
        var list = more.querySelector('ul');
        var moreItem = more.closest('li.nav-item');
        if (list) {
            Array.prototype.slice.call(list.children).forEach(function (item) {
                nav.appendChild(item);
            });
        }
        if (moreItem) {
            moreItem.remove();
        }
    }
    function unmountSimpleBar(el) {
        if (!el) return;
        el.removeAttribute('data-simplebar');
        var inst = window.SimpleBar && SimpleBar.instances && SimpleBar.instances.get(el);
        if (inst && typeof inst.unMount === 'function') {
            try { inst.unMount(); } catch (e) {}
        }
    }
    function syncHMenuOffset() {
        var menu = document.querySelector('.app-menu.navbar-menu');
        if (!menu) return;
        document.documentElement.style.setProperty('--np-hmenu-h', menu.offsetHeight + 'px');
    }
    function markActiveMenu() {
        var path = (location.pathname || '').replace(/\/+$/, '');
        document.querySelectorAll('#navbar-nav a.nav-link[href]').forEach(function (a) {
            var href = a.getAttribute('href') || '';
            if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
            try {
                var p = new URL(href, location.origin).pathname.replace(/\/+$/, '');
                if (!p || p === '/admin') return;
                if (path === p || path.indexOf(p + '/') === 0) {
                    a.classList.add('active');
                    var item = a.closest('#navbar-nav > .nav-item');
                    if (item) {
                        var top = item.querySelector(':scope > a.menu-link');
                        if (top) top.classList.add('active');
                    }
                }
            } catch (e) {}
        });
    }
    function setupHorizontalMenus() {
        if (document.documentElement.getAttribute('data-layout') !== 'horizontal') return;
        unmountSimpleBar(document.getElementById('scrollbar'));
        unmountSimpleBar(document.getElementById('navbar-nav'));
        syncHMenuOffset();
        markActiveMenu();
        if (window.innerWidth < 1025) return;
        document.querySelectorAll('#navbar-nav > .nav-item > a.menu-link[data-bs-toggle="collapse"]').forEach(function (link) {
            if (link.dataset.hmenuBound) return;
            link.dataset.hmenuBound = '1';
            link.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
            });
        });
    }
    unwrapNavbarSimpleBar();
    flattenMoreMenu();
    setupHorizontalMenus();
    setTimeout(function () { unwrapNavbarSimpleBar(); flattenMoreMenu(); setupHorizontalMenus(); }, 50);
    setTimeout(function () { unwrapNavbarSimpleBar(); flattenMoreMenu(); setupHorizontalMenus(); }, 400);
    window.addEventListener('resize', syncHMenuOffset);
    window.addEventListener('load', function () {
        unwrapNavbarSimpleBar();
        flattenMoreMenu();
        setupHorizontalMenus();
        setTimeout(function () { unwrapNavbarSimpleBar(); flattenMoreMenu(); setupHorizontalMenus(); }, 200);
    });
})();
</script>
