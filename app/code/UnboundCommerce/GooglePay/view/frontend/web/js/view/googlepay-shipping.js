/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

/* global define */
define(
    [
        'jquery',
        'uiComponent',
        'ko',
        'Magento_Checkout/js/model/payment/renderer-list',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/create-billing-address',
        'Magento_Checkout/js/action/create-shipping-address',
        'UnboundCommerce_GooglePay/js/model/googlepay-config'
    ],
    function ($, Component, ko, rendererList, customer, quote, createBillingAddress, createShippingAddress, googlepayConfig) {
        'use strict';
        rendererList.push(
            {
                type: 'googlepay',
                component: 'UnboundCommerce_GooglePay/js/view/payment/method-renderer/googlepay'
            }
        );
        return Component.extend(
            {
                defaults: {
                    template: 'UnboundCommerce_GooglePay/button-checkout-shipping-page',
                },
                googlepayButtonWidget: 'UnboundCommerce_GooglePay/js/googlepay-button',

                isCustomerLoggedIn: customer.isLoggedIn,

                _this: null,
                /**
                 * Init
                 */
                initialize: function () {
                    this._super();
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
                 * Sets shipping address for quote
                 */
                setShippingAddress: function (data) {
                    var address = data.shippingAddress;
                    var street = address.address1 + " "+address.address2+" "+address.address3;
                    var shippingAddress = {
                        street: [street],
                        city: address.locality,
                        region_code: address.postalCode,
                        postcode: address.administrativeArea,
                        countryId: address.countryCode,
                        firstname: address.name
                    };

                    shippingAddress = createShippingAddress(shippingAddress);
                    quote.shippingAddress(shippingAddress);
                },

                /**
                 * Sets billing address for quote
                 */
                setBillingAddress: function (data) {
                    var address = data.paymentMethodData.info.billingAddress;
                    var street = address.address1 + " "+address.address2+" "+address.address3;
                    var billingAddress = {
                        street: [street],
                        city: address.locality,
                        region_code: address.postalCode,
                        postcode: address.administrativeArea,
                        countryId: address.countryCode,
                        firstname: address.name
                    };

                    billingAddress = createBillingAddress(billingAddress);
                    quote.billingAddress(billingAddress);
                },

                /**
                 * Sets shipping and billing address
                 *
                 * @param {Object} payload
                 */
                onPaymentMethodReceived: function (payload) {
                    this.setShippingAddress(payload);
                    this.setBillingAddress(payload);
                },

                /**
                 * Gets payment widget config
                 *
                 * @param   {String} containerId
                 * @returns JSON
                 */
                getWidgetConfig: function (containerId) {
                    var code = this.getCode();
                    var widgetConfig = JSON.parse(window.checkoutConfig.payment[code].widgetConfig);
                    widgetConfig[this.googlepayButtonWidget]['containerId'] = containerId;
                    return widgetConfig;
                },
            }
        );
    }
);