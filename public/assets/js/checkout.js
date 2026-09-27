(function () {
    var mount = document.getElementById('mp-checkout');
    if (!mount || typeof MercadoPago === 'undefined') return;

    var publicKey = mount.getAttribute('data-public-key') || '';
    var amount = parseFloat(mount.getAttribute('data-amount') || '0');
    if (!publicKey || amount <= 0) {
        toast('Checkout indisponível. Recarregue a página.');
        return;
    }
    var email = mount.getAttribute('data-email') || '';
    var doc = (mount.getAttribute('data-doc') || '').replace(/\D+/g, '');
    var payUrl = mount.getAttribute('data-pay') || '';
    var statusUrl = mount.getAttribute('data-status') || '';
    var pixBox = document.getElementById('mp-pix');
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var poll = null;

    function toast(message) {
        if (window.CinquentaToast) {
            window.CinquentaToast(message);
            return;
        }
        var region = document.getElementById('toast-region');
        if (!region) return;
        var el = document.createElement('div');
        el.className = 'toast';
        el.textContent = message;
        region.appendChild(el);
        setTimeout(function () { el.remove(); }, 4200);
    }

    function showPix(pix) {
        if (!pixBox || !pix || !pix.qr_code) return;
        mount.hidden = true;
        pixBox.hidden = false;
        var img = pix.qr_code_base64
            ? '<img alt="QR Code Pix" src="data:image/png;base64,' + pix.qr_code_base64 + '">'
            : '';
        pixBox.innerHTML = '<p><strong>Pague com Pix</strong></p>' + img
            + '<label class="field"><span>Código copia e cola</span><textarea readonly rows="3">' + pix.qr_code + '</textarea></label>'
            + '<p class="muted small mb-0">O contrato confirma sozinho quando o Pix for aprovado. Não feche esta página.</p>';
        if (poll) clearInterval(poll);
        poll = setInterval(function () {
            fetch(statusUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.contract && data.contract !== 'awaiting_payment') {
                        clearInterval(poll);
                        window.location.reload();
                    }
                })
                .catch(function () {});
        }, 4000);
    }

    var mp = new MercadoPago(publicKey, { locale: 'pt-BR' });
    var identification = doc.length === 14
        ? { type: 'CNPJ', number: doc }
        : (doc.length === 11 ? { type: 'CPF', number: doc } : undefined);
    var payer = {
        email: email,
        entityType: doc.length === 14 ? 'association' : 'individual'
    };
    if (identification) {
        payer.identification = identification;
    }

    mp.bricks().create('payment', 'mp-checkout', {
        initialization: {
            amount: amount,
            payer: payer
        },
        customization: {
            visual: { style: { theme: 'default' } },
            paymentMethods: {
                creditCard: 'all',
                debitCard: 'all',
                prepaidCard: 'all',
                bankTransfer: 'all',
                maxInstallments: 12
            }
        },
        callbacks: {
            onReady: function () {},
            onError: function () {
                toast('Não foi possível carregar o checkout. Recarregue a página.');
            },
            onSubmit: function (payload) {
                var formData = payload && payload.formData ? payload.formData : payload;
                return fetch(payUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(formData || {})
                }).then(function (res) {
                    return res.json().then(function (data) {
                        if (data.status === 'approved' && data.redirect) {
                            window.location.href = data.redirect;
                            return;
                        }
                        if (data.pix && data.pix.qr_code) {
                            showPix(data.pix);
                            return;
                        }
                        if (!data.ok) {
                            throw new Error(data.message || 'Pagamento recusado.');
                        }
                    });
                }).catch(function (err) {
                    toast(err && err.message ? err.message : 'Pagamento recusado. Tente outro meio.');
                    throw err;
                });
            }
        }
    });
})();
