/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

/* global define */
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'UnboundCommerce_GooglePay/js/model/googlepay-config'
    ],
    function (Component, googlepayConfig) {
        'use strict';

        return Component.extend(
            {
                defaults: {
                    template: 'UnboundCommerce_GooglePay/payment/button-checkout-page',
                    transactionResult: ''
                },

                googlepayButtonWidget: 'UnboundCommerce_GooglePay/js/googlepay-button',

                /**
                 * Set list of observable attributes
                 *
                 * @returns {exports.initObservable}
                 */
                initObservable: function () {
                    this._super();
                    return this;
                },

                /**
                 * Get GooglePay payment code
                 *
                 * @returns {String}
                 */
                getCode: function () {
                    return 'googlepay';
                },

                /**
                 * Get data
                 *
                 * @returns {Object}
                 */
                getData: function () {
                    return {
                        'method': this.item.method
                    };
                },

                /**
                 * Get payment title
                 *
                 * @returns {String}
                 */
                getTitle: function () {
                    return window.checkoutConfig.payment[this.getCode()].title;
                },

                /**
                 * Returns payment acceptance mark image path
                 *
                 * @returns {String}
                 */
                getGooglePayMark: function () {
                    return window.checkoutConfig.payment[this.getCode()].googlePayMark;
                },

                /**
                 * Get payment widget config
                 *
                 * @param   {String} containerId
                 * @returns JSON
                 */
                getWidgetConfig: function (containerId) {
                    let code = this.getCode();
                    let widgetConfig = JSON.parse(window.checkoutConfig.payment[code].widgetConfig);
                    widgetConfig[this.googlepayButtonWidget]['containerId'] = containerId;
                    widgetConfig[this.googlepayButtonWidget]['shippingAddressRequired'] = false;
                    return widgetConfig;
                }

            }
        );
    }
);