/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

/* global define */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'googlepay',
                component: 'UnboundCommerce_GooglePay/js/view/payment/method-renderer/googlepay'
            }
        );
        return Component.extend({});
    }
);
