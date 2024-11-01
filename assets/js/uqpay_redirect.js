/* global wc_uqpay_redirect_params */
(function () {
    if (wc_uqpay_redirect_params) {
        const form = document.createElement('form');
        form.setAttribute('method', 'post');
        form.setAttribute('action', wc_uqpay_redirect_params.api);
        form.setAttribute('style', 'visibility:hidden');
        form.className = 'hidden';
        Object.keys(wc_uqpay_redirect_params.body).forEach(function (key) {
            const input = document.createElement('input');
            input.setAttribute('name', key);
            input.setAttribute('value', wc_uqpay_redirect_params.body[key]);
            form.appendChild(input);
        });
        window.document.head.appendChild(form);
        form.submit();
    }
})();