(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    document.querySelectorAll('[data-drawer]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = document.querySelector(btn.getAttribute('data-drawer'));
            if (!target) return;
            if (target.classList.contains('dash-side')) {
                target.classList.toggle('is-open');
                return;
            }
            target.hidden = !target.hidden;
        });
    });

    document.querySelectorAll('[data-close-drawer]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const drawer = btn.closest('.drawer');
            if (drawer) drawer.hidden = true;
        });
    });

    document.querySelectorAll('.drawer').forEach(function (drawer) {
        drawer.addEventListener('click', function (event) {
            if (event.target === drawer) drawer.hidden = true;
        });
    });

    window.Cinquenta = {
        csrf: csrf,
        toast: function (message) {
            const region = document.getElementById('toast-region') || document.body;
            const el = document.createElement('div');
            el.className = 'toast';
            el.textContent = message;
            region.appendChild(el);
            setTimeout(function () { el.remove(); }, 3200);
        },
        fetch: function (url, options) {
            options = options || {};
            options.headers = Object.assign({
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }, options.headers || {});
            return fetch(url, options);
        }
    };
    window.Nexo = window.Cinquenta;

    function formatBRL(cents) {
        return 'R$ ' + (cents / 100).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    document.querySelectorAll('[data-gallery-src]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const main = document.getElementById('gallery-main');
            if (!main) return;
            main.src = btn.getAttribute('data-gallery-src');
            document.querySelectorAll('[data-gallery-src]').forEach(function (el) { el.classList.remove('is-active'); });
            btn.classList.add('is-active');
        });
    });

    const box = document.querySelector('[data-buybox]');
    if (box) {
        const totalEl = box.querySelector('[data-total]');
        const daysEl = box.querySelector('[data-days-label]');
        const packageInput = box.querySelector('[data-package-input]');
        let packagePrice = 0;
        let packageDays = 0;
        let packageRevisions = 0;
        let packageHours = 2;
        const first = box.querySelector('[data-package].is-active') || box.querySelector('[data-package]');
        if (first) {
            packagePrice = parseInt(first.getAttribute('data-price') || '0', 10);
            packageDays = parseInt(first.getAttribute('data-days') || '0', 10);
            packageHours = parseInt(first.getAttribute('data-hours') || '2', 10);
            packageRevisions = parseInt(first.getAttribute('data-revisions') || '0', 10);
        }

        function refreshTotal() {
            let extraPrice = 0;
            let extraDays = 0;
            box.querySelectorAll('[data-extra]:checked').forEach(function (input) {
                extraPrice += parseInt(input.getAttribute('data-price') || '0', 10);
                extraDays += parseInt(input.getAttribute('data-days') || '0', 10);
            });
            if (totalEl) totalEl.textContent = formatBRL(packagePrice + extraPrice);
            if (daysEl) daysEl.textContent = packageHours + 'h de vídeo · ' + (packageDays + extraDays) + ' dias';
        }

        box.querySelectorAll('[data-package]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                box.querySelectorAll('[data-package]').forEach(function (el) { el.classList.remove('is-active'); });
                tab.classList.add('is-active');
                packagePrice = parseInt(tab.getAttribute('data-price') || '0', 10);
                packageDays = parseInt(tab.getAttribute('data-days') || '0', 10);
                packageHours = parseInt(tab.getAttribute('data-hours') || '2', 10);
                packageRevisions = parseInt(tab.getAttribute('data-revisions') || '0', 10);
                if (packageInput) packageInput.value = tab.getAttribute('data-id') || '';
                const bodies = box.querySelectorAll('[data-package-body]');
                const tabs = Array.prototype.slice.call(box.querySelectorAll('[data-package]'));
                const index = tabs.indexOf(tab);
                bodies.forEach(function (body, i) { body.hidden = i !== index; });
                refreshTotal();
            });
        });

        box.querySelectorAll('[data-extra]').forEach(function (input) {
            input.addEventListener('change', refreshTotal);
        });
        refreshTotal();
    }

    document.querySelectorAll('[data-share]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const link = window.location.href;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link).then(function () {
                    window.Nexo.toast('Link copiado.');
                });
                return;
            }
            window.Nexo.toast(link);
        });
    });
})();
