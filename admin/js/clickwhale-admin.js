(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    // Link Live Preview

    function padTo2Digits(num) {
        return num.toString().padStart(2, '0');
    }

    function formatDate(date) {
        return (
            [
                date.getFullYear(),
                padTo2Digits(date.getMonth() + 1),
                padTo2Digits(date.getDate()),
            ].join('-') +
            ' ' +
            [
                padTo2Digits(date.getHours()),
                padTo2Digits(date.getMinutes()),
                padTo2Digits(date.getSeconds()),
            ].join(':')
        );
    }

    function setCurrentTopLevelPage() {
        $('#toplevel_page_clickwhale, #toplevel_page_clickwhale > a')
            .removeClass('wp-not-current-submenu')
            .addClass('wp-has-submenu wp-has-current-submenu wp-menu-open');
    }

    function setCurrentSubmenuPage($item) {
        $('#toplevel_page_clickwhale a').each(function () {
            var url = $(this).attr('href');
            if (url.endsWith($item)) {
                $(this).parent().addClass('current');
            }
        });
    }

    $(function () {
        var urlSearchParams = new URLSearchParams(window.location.search),
            params = Object.fromEntries(urlSearchParams.entries());
        if (params !== 'undefined') {
            if (params['page'] === 'clickwhale-edit-category') {
                setCurrentTopLevelPage();
                setCurrentSubmenuPage('clickwhale-categories');
            }
            if (params['page'] === 'clickwhale-edit-link') {
                setCurrentTopLevelPage();
                setCurrentSubmenuPage('clickwhale');
            }
            if (params['page'] === 'clickwhale-edit-linkpage') {
                setCurrentTopLevelPage();
                setCurrentSubmenuPage('clickwhale-linkpages');
            }
        }

        $(document)
            .on('click', '#button-reset-db', function () {
                var arr = [];
                for (var i = 0; i < localStorage.length; i++) {
                    if (localStorage.key(i).substring(0, 3) == 'PLtab_') {
                        arr.push(localStorage.key(i));
                    }
                }

                // Iterate over arr and remove the items by key
                for (var i = 0; i < arr.length; i++) {
                    localStorage.removeItem(arr[i]);
                }
            })
            .on('keyup change', '#slug', function () {
                var slug = $(this).val();

                slug = slug.replace(/\s+/g, '-').toLowerCase();
                slug = slug.indexOf('/') == 0 ? slug.substring(1) : slug;
                slug = slug.replace(/\\/g, "/");
                slug = slug.replace(/\/\//g, "/");
                slug = slug.replace(/\/\/\//g, "/");
                slug = slug.replace(/\/$/, '');

                $('#slug__text').find('span').html(slug);
            })
            .on('submit', '#form_edit_link', function () {
                if ($('#created_at').val() === '') {
                    $('#created_at').val(formatDate(new Date()));
                }
                $('#updated_at').val(formatDate(new Date()));
            });
    });

})(jQuery);
