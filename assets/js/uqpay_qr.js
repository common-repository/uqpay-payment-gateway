/* global jQuery, wc_uqpay_order_query_params*/
jQuery(function ($) {
    const query_api = wc_uqpay_order_query_params['query_url'];
    delete wc_uqpay_order_query_params['query_url'];
    let hasCheckError = 0;
    let hasQuery = 0;
    const interval = window.setInterval(function () {
        $.ajax({
            url: query_api,
            data: wc_uqpay_order_query_params,
            type: 'POST',
            success: function (rsp) {
                hasQuery += 1;
                const result = JSON.parse(rsp);
                if (result.success) {
                    if (result.data.redirect) {
                        clearInterval(interval);
                        window.location.replace(result.data.redirect);
                    }
                } else {
                    hasCheckError += 1;
                    if (hasCheckError === 5) {
                        clearInterval(interval);
                        alert(result.message);
                    }
                }
                if (hasQuery === 100) {
                    clearInterval(interval);
                    alert("Ops looks you still not scan the code");
                }
            },
            error: function () {
                clearInterval(interval);
                alert("Network Connection Error");
            }
        });
    }, 3000);
});