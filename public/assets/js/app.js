(function () {
    'use strict';

    function toast(message) {
        var region = document.getElementById('toast-region') || document.body;
        var el = document.createElement('div');
        el.className = 'toast';
        el.setAttribute('role', 'status');
        el.textContent = message;
        region.appendChild(el);
        setTimeout(function () { el.remove(); }, 3200);
    }

    function formatBRL(cents) {
        return 'R$ ' + (cents / 100).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Public drawer menu
    document.querySelectorAll('[data-drawer]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.querySelector(btn.getAttribute('data-drawer'));
            if (!target) return;
            target.hidden = !target.hidden;
            btn.setAttribute('aria-expanded', String(!target.hidden));
            if (!target.hidden) {
                var first = target.querySelector('a, button');
                if (first) first.focus();
            }
        });
    });
    document.querySelectorAll('.drawer').forEach(function (drawer) {
        drawer.addEventListener('click', function (event) {
            if (event.target === drawer || event.target.hasAttribute('data-close-drawer')) drawer.hidden = true;
        });
    });

    // Panel sidebar on mobile
    var side = document.getElementById('menu-painel');
    var sideToggle = document.querySelector('[data-side-toggle]');
    if (side && sideToggle) {
        sideToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var open = side.classList.toggle('is-open');
            sideToggle.setAttribute('aria-expanded', String(open));
        });
        document.addEventListener('click', function (event) {
            if (side.classList.contains('is-open') && !side.contains(event.target)) {
                side.classList.remove('is-open');
                sideToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('.drawer:not([hidden])').forEach(function (d) { d.hidden = true; });
        if (side) side.classList.remove('is-open');
    });

    // Confirmation before destructive actions: <form data-confirm="Texto">
    document.addEventListener('submit', function (event) {
        var form = event.target;
        var message = form.getAttribute && form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            event.preventDefault();
            return;
        }
        var button = form.querySelector('button[type="submit"]:not([data-keep-enabled])');
        if (button && !event.defaultPrevented) {
            setTimeout(function () { button.disabled = true; }, 0);
        }
    });

    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { if (el.form) el.form.submit(); });
    });

    // Gallery
    document.querySelectorAll('[data-gallery-src]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var main = document.getElementById('gallery-main');
            if (!main) return;
            main.src = btn.getAttribute('data-gallery-src');
            document.querySelectorAll('[data-gallery-src]').forEach(function (el) { el.classList.remove('is-active'); });
            btn.classList.add('is-active');
        });
    });

    // Buy box total
    var box = document.querySelector('[data-buybox]');
    if (box) {
        var totalEl = box.querySelector('[data-total]');
        var daysEl = box.querySelector('[data-days-label]');
        var refresh = function () {
            var pkg = box.querySelector('input[name="package_id"]:checked');
            if (!pkg) return;
            var price = parseInt(pkg.getAttribute('data-price') || '0', 10);
            var days = parseInt(pkg.getAttribute('data-days') || '0', 10);
            box.querySelectorAll('[data-extra]:checked').forEach(function (input) {
                price += parseInt(input.getAttribute('data-price') || '0', 10);
                days += parseInt(input.getAttribute('data-days') || '0', 10);
            });
            if (totalEl) totalEl.textContent = formatBRL(price);
            if (daysEl) daysEl.textContent = pkg.getAttribute('data-hours') + 'h de vídeo · ' + days + ' dias';
        };
        box.addEventListener('change', refresh);
        refresh();
    }

    // Copy link
    document.querySelectorAll('[data-share]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var link = window.location.href;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link).then(function () { toast('Link copiado.'); });
            } else {
                toast(link);
            }
        });
    });

    // Register: company name only for companies
    var companyBlock = document.querySelector('[data-company-only]');
    if (companyBlock) {
        document.querySelectorAll('[data-toggle-company]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                companyBlock.hidden = radio.getAttribute('data-toggle-company') !== '1';
            });
        });
    }

    // Repeatable rows: <div data-repeat="packages" data-max="6"> + <template data-repeat-template="packages">
    document.querySelectorAll('[data-repeat]').forEach(function (list) {
        var name = list.getAttribute('data-repeat');
        var template = document.querySelector('template[data-repeat-template="' + name + '"]');
        var addBtn = document.querySelector('[data-repeat-add="' + name + '"]');
        var max = parseInt(list.getAttribute('data-max') || '50', 10);
        var sync = function () {
            var count = list.querySelectorAll('[data-repeat-row]').length;
            if (addBtn) addBtn.disabled = count >= max;
        };
        if (addBtn && template) {
            addBtn.addEventListener('click', function () {
                if (list.querySelectorAll('[data-repeat-row]').length >= max) return;
                list.appendChild(template.content.cloneNode(true));
                var rows = list.querySelectorAll('[data-repeat-row]');
                var input = rows[rows.length - 1].querySelector('input, textarea, select');
                if (input) input.focus();
                sync();
            });
        }
        list.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-repeat-remove]');
            if (!remove) return;
            var row = remove.closest('[data-repeat-row]');
            if (row) row.remove();
            sync();
        });
        sync();
    });

    // Character counters: <textarea data-count="400">
    document.querySelectorAll('[data-count]').forEach(function (field) {
        var max = parseInt(field.getAttribute('data-count'), 10);
        var out = document.createElement('small');
        out.setAttribute('aria-live', 'polite');
        field.insertAdjacentElement('afterend', out);
        var update = function () { out.textContent = field.value.length + ' / ' + max; };
        field.addEventListener('input', update);
        update();
    });

    // Keep chat scrolled to the newest message
    var thread = document.querySelector('.thread-body');
    if (thread) thread.scrollTop = thread.scrollHeight;
})();
