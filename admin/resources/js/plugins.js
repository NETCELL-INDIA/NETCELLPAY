/*
Template Name: Velzon - Admin & Dashboard Template
Author: Themesbrand
Version: 1.2.0
Website: https://Themesbrand.com/
Contact: Themesbrand@gmail.com
File: Common Plugins Js File
*/

(function () {
    function pluginAsset(path) {
        path = String(path || '').replace(/^\//, '');
        var scripts = document.getElementsByTagName('script');
        for (var i = scripts.length - 1; i >= 0; i--) {
            var abs = scripts[i].src || '';
            if (abs.indexOf('assets/js/plugins.min.js') !== -1 || abs.indexOf('assets/js/plugins.js') !== -1) {
                return abs.replace(/assets\/js\/plugins(?:\.min)?\.js.*$/i, path);
            }
        }
        var prefix = location.pathname.indexOf('/admin') === 0 ? '/admin/' : '/';
        return location.origin + prefix + path;
    }

    // NodeList is always truthy — must check .length or every page loads these scripts.
    if (
        document.querySelectorAll('[toast-list]').length ||
        document.querySelectorAll('[data-choices]').length ||
        document.querySelectorAll('[data-provider]').length
    ) {
        document.writeln("<script type='text/javascript' src='https://cdn.jsdelivr.net/npm/toastify-js'><\/script>");
        document.writeln("<script type='text/javascript' src='" + pluginAsset('assets/libs/choices.js/choices.js.min.js') + "'><\/script>");
        document.writeln("<script type='text/javascript' src='" + pluginAsset('assets/libs/flatpickr/flatpickr.min.js') + "'><\/script>");
    }
})();
