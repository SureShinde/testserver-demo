/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

define(
    [
    'jquery',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/section-config',
    'Magento_Checkout/js/model/payment/additional-validators',
    'mage/url',
    'mage/storage',
    'jquery/ui',
    'uiRegistry',
    'mage/validation',
    'domReady!'
    ], function ($, customerData, sectionConfig, additionalValidators, urlBuilder, storage) {
        'use strict';

        var _this, $button;

        $.widget(
            'unboundCommerce.googlepayButton', {
                options: {},

                baseRequest: {
                    apiVersion: 2,
                    apiVersionMinor: 0
                },

                environment: "TEST",

                tokenizationSpecification: null,

                baseCardPaymentMethod: null,

                selectedAddress: null,

                selectedShippingOption: null,

                miniCartOptions: {
                    agreementsPath: '.minicart-gpay-agreements div.checkout-agreements input',
                    containerId: null,
                    paymentsClient: null,
                    shippingAddressRequired: true
                },

                cartPageOptions: {
                    agreementsPath: '.cart-gpay-agreements div.checkout-agreements input',
                    containerId: null,
                    paymentsClient: null,
                    shippingAddressRequired: true
                },

                checkoutPageOptions: {
                    containerId: null,
                    paymentsClient: null,
                    shippingAddressRequired: false
                },

                shippingPageOptions: {
                    agreementsPath: '.shipping-gpay-agreements div.checkout-agreements input',
                    containerId: null,
                    paymentsClient: null,
                    shippingAddressRequired: true
                },

                /**
                 * Creates button
                 */
                _create: function () {
                    _this = this;
                    $button = this.element;
                    let pageOptions = null;
                    if (_this.options.containerId === "googlePayCartContainer") {
                        if (_this.options.isMiniCart) {
                            pageOptions = _this.miniCartOptions;
                            _this.miniCartOptions.shippingAddressRequired = _this.options.shippingAddressRequired;
                        } else {
                            pageOptions = _this.cartPageOptions;
                            _this.cartPageOptions.shippingAddressRequired = _this.options.shippingAddressRequired;
                        }
                    } else if (_this.options.containerId === "googlePayCheckoutContainer") {
                        pageOptions = _this.checkoutPageOptions;
                        _this.checkoutPageOptions.shippingAddressRequired = _this.options.shippingAddressRequired;
                    } else if (_this.options.containerId === "googlePayShippingContainer") {
                        pageOptions = _this.shippingPageOptions;
                        _this.shippingPageOptions.shippingAddressRequired = _this.options.shippingAddressRequired;
                    }
                    pageOptions.containerId = _this.options.containerId;
                    if (_this.options.gatewayInfo.environment === "production") {
                        _this.environment = "PRODUCTION";
                    }

                    _this.tokenizationSpecification = {
                        type: 'PAYMENT_GATEWAY',
                        parameters: _this.getGatewayParams()
                    };
                    if (_this.options.gatewayInfo.gateway_id === "cybersource") {
                        _this.options.billingAddressRequired = true;
                    }
                    _this.baseCardPaymentMethod = {
                        type: 'CARD',
                        parameters: {
                            allowedAuthMethods: ["PAN_ONLY", "CRYPTOGRAM_3DS"],
                            allowedCardNetworks: _this.options.ccTypes,
                            billingAddressRequired: _this.options.billingAddressRequired,
                            billingAddressParameters: {
                                "format": "FULL",
                                phoneNumberRequired: _this.options.isTelephoneRequired
                            }
                        }
                    };

                    let script = document.createElement('script');
                    script.src = "https://pay.google.com/gp/p/js/pay.js";
                    script.onload = function () {
                        _this.onGooglePayLoaded(pageOptions);
                    };
                    document.head.appendChild(script);
                },

                /**
                 * @param   {String} code
                 * @returns {String}
                 */
                formatCode: function (code) {
                    return code.charAt(0).toUpperCase() + code.slice(1).toLowerCase();
                },

                /**
                 * @param   {String} str
                 * @returns {String}
                 */
                b64EncodeUnicode: function (str) {
                    return btoa(
                        encodeURIComponent(str).replace(
                            /%([0-9A-F]{2})/g, function (match, p1) {
                                return String.fromCharCode('0x' + p1);
                            }
                        )
                    );
                },

                /**
                 * Return options based on container
                 *
                 * @param   {String} containerId
                 * @param   {String} isMiniCart
                 * @returns {Object}
                 */
                getOptions: function (containerId, isMiniCart) {
                    if (containerId === "googlePayCartContainer") {
                        if (isMiniCart) {
                            return _this.miniCartOptions;
                        } else {
                            return _this.cartPageOptions;
                        }
                    } else if (_this.options.containerId === "googlePayCheckoutContainer") {
                        return _this.checkoutPageOptions;
                    } else if (_this.options.containerId === "googlePayShippingContainer") {
                        return _this.shippingPageOptions;
                    }
                    return null;
                },

                /**
                 * Gets gateway credentials based on the gateway provided
                 *
                 * @returns {Object}
                 */
                getGatewayParams: function () {
                    let parameters = {};
                    switch (_this.options.gatewayInfo.gateway_id) {
                    case 'braintree':
                        parameters = {
                            "gateway": _this.options.gatewayInfo.gateway_id,
                            "braintree:apiVersion": "v1",
                            "braintree:sdkVersion": "3.43.0",
                            "braintree:merchantId": _this.options.gatewayInfo.gateway_merchant_id,
                            "braintree:clientKey": _this.options.gatewayInfo.client_key
                        };
                        break;
                    case 'moneris':
                        parameters = {
                            'gateway': _this.options.gatewayInfo.gateway_id,
                            'gatewayMerchantId': _this.options.gatewayInfo.gateway_store_id
                        };
                        break;
                    case 'stripe':
                        parameters = {
                            'gateway': _this.options.gatewayInfo.gateway_id,
                            'stripe:version': "2018-11-08",
                            'stripe:publishableKey': _this.options.gatewayInfo.publishable_key,
                        };
                        break;
                    case 'vantiv':
                        let date = new Date();
                        let timestamp = date.getTime();
                        let merchantTransactionId = "gpay_".concat(_this.options.quoteId,"_",timestamp);
                        parameters = {
                            "gateway": _this.options.gatewayInfo.gateway_id,
                            "vantiv:merchantPayPageId":_this.options.gatewayInfo.pay_page_id,
                            "vantiv:merchantOrderId": _this.options.quoteId,
                            "vantiv:merchantTransactionId": merchantTransactionId,
                            "vantiv:merchantReportGroup": _this.options.gatewayInfo.report_group
                        };
                        break;
                    default:
                        parameters = {
                            'gateway': _this.options.gatewayInfo.gateway_id,
                            'gatewayMerchantId': _this.options.gatewayInfo.gateway_merchant_id
                        };
                    }

                    return parameters;
                },

                /**
                 * Loads GooglePay button and fetches payment data if GooglePay can be added
                 *
                 * @param {Object} pageOptions
                 */
                onGooglePayLoaded: function (pageOptions) {
                    const paymentsClient = _this.getGooglePaymentsClient(pageOptions);
                    paymentsClient.isReadyToPay(_this.getGoogleIsReadyToPayRequest())
                    .then(
                        function (response) {
                            if (response.result) {
                                _this.addGooglePayButton(paymentsClient, pageOptions.containerId);
                            }
                        }
                    ).catch(
                        function (err) {
                            console.error(err);
                        }
                    );
                },

                /**
                 * Adds GooglePay button
                 *
                 * @param {Object} paymentsClient
                 * @param {String} containerId
                 */
                addGooglePayButton: function (paymentsClient, containerId) {
                    let hasChild = document.getElementById(containerId).children.length;
                    if (hasChild === 0) {
                        let clickFunction = _this.onGooglePaymentButtonClicked;
                        if (containerId === "googlePayCartContainer") {
                            if (_this.options.isMiniCart) {
                                clickFunction = _this.onGPayMiniCartClicked;
                            } else {
                                clickFunction = _this.onGPayCartClicked;
                            }
                        } else if (containerId === "googlePayCheckoutContainer") {
                            clickFunction = _this.onGPayCheckoutClicked;
                        } else if (containerId === "googlePayShippingContainer") {
                            clickFunction = _this.onGPayShippingClicked;
                        }
                        const googlepayDiv = paymentsClient.createButton({onClick: clickFunction, buttonColor: _this.options.buttonColor,  buttonType: _this.options.buttonType});
                        if (containerId === "googlePayCartContainer") {
                            const button = googlepayDiv.getElementsByTagName('button');
                            button[0].style.backgroundRepeat = "no-repeat";
                            button[0].style.backgroundPosition = "center center";
                            button[0].style.backgroundOrigin = "content-box";
                            button[0].style.backgroundSize = "contain";
                            button[0].style.borderBottomWidth = "0px";
                            button[0].style.borderBottomStyle = "none";
                            button[0].style.borderLeftWidth = "0px";
                            button[0].style.borderLeftStyle = "none";
                            button[0].style.borderTopWidth = "0px";
                            button[0].style.borderTopStyle = "none";
                            button[0].style.borderRightWidth = "0px";
                            button[0].style.borderRightStyle = "none";
                            button[0].style.marginTop = "15px";
                            button[0].style.width = "100%";
                        }
                        document.getElementById(containerId).appendChild(googlepayDiv);
                    }
                },

                /**
                 * Gets isReadyToPay request
                 *
                 * @returns {Object}
                 */
                getGoogleIsReadyToPayRequest: function () {
                    return Object.assign(
                        {},
                        _this.baseRequest,
                        {
                            allowedPaymentMethods: [_this.baseCardPaymentMethod]
                        }
                    );
                },

                /**
                 * Gets payment data request
                 *
                 * @param   {Boolean} shippingAddressRequired
                 * @returns {Object}
                 */
                getGooglePaymentDataRequest: function (shippingAddressRequired) {
                    const cardPaymentMethod = Object.assign(
                        {},
                        _this.baseCardPaymentMethod,
                        {
                            tokenizationSpecification: _this.tokenizationSpecification
                        }
                    );

                    const paymentDataRequest = Object.assign({}, _this.baseRequest);
                    paymentDataRequest.allowedPaymentMethods = [cardPaymentMethod];
                    paymentDataRequest.transactionInfo = _this.getGoogleTransactionInfo(shippingAddressRequired);
                    paymentDataRequest.shippingAddressRequired = shippingAddressRequired;
                    paymentDataRequest.shippingOptionRequired =  shippingAddressRequired;
                    paymentDataRequest.emailRequired = true;
                    paymentDataRequest.merchantInfo = {
                        merchantId: _this.options.merchantId,
                        merchantName: _this.options.merchantName,
                        softwareInfo: {
                            id: 'com.unboundcommerce.magentoplugin',
                            version: '1.0.0'
                        }
                    };
                    if (!paymentDataRequest.shippingAddressRequired) {
                        paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];
                    } else {
                        paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION", "SHIPPING_ADDRESS", "SHIPPING_OPTION"];
                        paymentDataRequest.shippingAddressParameters = {
                            phoneNumberRequired: _this.options.isTelephoneRequired
                        }
                    }
                    return paymentDataRequest;
                },

                /**
                 * Gets PaymentsClient based on current environment
                 *
                 * @param   {Object} pageOptions
                 * @returns {Object}
                 */
                getGooglePaymentsClient: function (pageOptions) {
                    if (pageOptions.paymentsClient === null) {
                        if (pageOptions.shippingAddressRequired) {
                            pageOptions.paymentsClient = new google.payments.api.PaymentsClient(
                                {
                                    environment: _this.environment,
                                    paymentDataCallbacks: {
                                        onPaymentAuthorized: _this.onPaymentAuthorized,
                                        onPaymentDataChanged: _this.onPaymentDataChanged
                                    }
                                }
                            );
                        } else {
                            pageOptions.paymentsClient = new google.payments.api.PaymentsClient(
                                {
                                    environment: _this.environment,
                                    paymentDataCallbacks: {
                                        onPaymentAuthorized: _this.onPaymentAuthorized
                                    }
                                }
                            );
                        }
                    }

                    return pageOptions.paymentsClient;
                },

                /**
                 * Gets transaction information
                 *
                 * @param   {Boolean} shippingAddressRequired
                 * @returns {Object}
                 */
                getGoogleTransactionInfo: function (shippingAddressRequired) {
                    let transactionInfo = _this.options.transactionInfo;
                    transactionInfo.totalPriceStatus = 'FINAL';
                    transactionInfo.currencyCode = _this.options.currencyCode;
                    if (!shippingAddressRequired && !_this.options.isQuoteVirtual) {
                        require(
                            ['Magento_Checkout/js/model/totals'], function (totals) {
                                let checkoutTotals = totals.totals();
                                if (checkoutTotals.total_segments !== null && checkoutTotals.total_segments.length > 0) {
                                    transactionInfo.displayItems = [];
                                    checkoutTotals.total_segments.forEach(
                                        function (totalSegment) {
                                            if (totalSegment.code === 'grand_total') {
                                                transactionInfo.totalPriceLabel = totalSegment.title;
                                                transactionInfo.totalPrice = totalSegment.value.toFixed(2);
                                            } else if (totalSegment.value !== null) {
                                                let item = {};
                                                if (totalSegment.code === 'subtotal') {
                                                    item.type = 'SUBTOTAL';
                                                } else {
                                                    item.type = 'LINE_ITEM';
                                                }
                                                item.label = totalSegment.title;
                                                item.price = totalSegment.value.toFixed(2);
                                                transactionInfo.displayItems.push(item);
                                            }
                                        }
                                    );
                                }
                            }
                        );
                    }

                    return transactionInfo;
                },

                /**
                 * Validate checkout agreements
                 *
                 * @returns {Boolean}
                 */
                validate: function (agreementsPath) {

                    let isValid = true;

                    if ($(agreementsPath).length === 0) {
                        return true;
                    }

                    $(agreementsPath).each(
                        function (index, element) {
                            if (!$.validator.validateSingleElement(
                                element, {
                                    errorElement: 'div',
                                    hideError: false
                                }
                            )
                            ) {
                                isValid = false;
                            }
                        }
                    );

                    return isValid;
                },

                /**
                 * Handle mini-cart google pay button click event
                 *
                 * @param {Object} event
                 */
                onGPayMiniCartClicked: function (event) {
                    let pageOptions = _this.miniCartOptions;

                    if (!_this.validate(pageOptions.agreementsPath)) {
                        alert("Please agree to the Terms & Conditions at the bottom of the minicart");
                        return false;
                    } else {
                        _this.onGooglePaymentButtonClicked(pageOptions);
                    }
                },

                /**
                 * Handle cart google pay button click event
                 *
                 * @param {Object} event
                 */
                onGPayCartClicked: function (event) {
                    let pageOptions = _this.cartPageOptions;

                    if (!_this.validate(pageOptions.agreementsPath)) {
                        return false;
                    } else {
                        _this.onGooglePaymentButtonClicked(pageOptions);
                    }
                },

                /**
                 * Handle review & payments step google pay button click event
                 *
                 * @param {Object} event
                 */
                onGPayCheckoutClicked: function (event) {
                    if (event) {
                        event.preventDefault();
                        if (!additionalValidators.validate()) {
                            return false;
                        }
                    }
                    _this.onGooglePaymentButtonClicked(_this.checkoutPageOptions);
                },

                /**
                 * Handle shipping step google pay button click event
                 *
                 * @param {Object} event
                 */
                onGPayShippingClicked: function (event) {
                    let pageOptions = _this.shippingPageOptions;
                    if (!_this.validate(pageOptions.agreementsPath)) {
                        return false;
                    } else {
                        _this.onGooglePaymentButtonClicked(pageOptions);
                    }
                },

                /**
                 * Loads google pay lightbox
                 *
                 * @param {Object} pageOptions
                 */
                onGooglePaymentButtonClicked: function (pageOptions) {
                    if (_this.options.redirectToCart) {
                        $.mage.redirect(urlBuilder.build('customer/account/login'));
                    } else {
                        const paymentDataRequest = _this.getGooglePaymentDataRequest(pageOptions.shippingAddressRequired);
                        paymentDataRequest.transactionInfo = _this.getGoogleTransactionInfo(pageOptions.shippingAddressRequired);

                        const paymentsClient = _this.getGooglePaymentsClient(pageOptions);
                        paymentsClient.loadPaymentData(paymentDataRequest)
                        .catch(
                            function (err) {
                                // show error in developer console for debugging
                                console.error(err);
                                alert("Payment canceled");
                            }
                        );
                    }
                },

                /**
                 * Processes GooglePay payment data
                 *
                 * @param {Object} paymentData
                 */
                processPayment: function (paymentData) {
                    return new Promise(
                        function (resolve, reject) {
                            setTimeout(
                                function () {
                                    if (_this.options.gatewayInfo.gateway_id === 'bluesnap') {
                                        paymentData.jsonEncodedString = JSON.stringify(paymentData);
                                    }
                                    if (paymentData.hasOwnProperty('shippingAddress')) {
                                        if (paymentData.hasOwnProperty('shippingOptionData')) {
                                            paymentData.shippingAddress.shippingMethod = paymentData.shippingOptionData.id;
                                            delete paymentData.shippingOptionData;
                                        } else {
                                            paymentData.shippingAddress.shippingMethod = _this.selectedShippingOption;
                                        }
                                    }

                                    let payload = {
                                        'paymentData' : paymentData
                                    };

                                    var transactionUrl;
                                    if (_this.options.isUserLoggedIn) { //Api for logged in customer
                                        transactionUrl = 'rest/default/V1/googlepay/mine/place-order';
                                    } else { // Api for guest customer
                                        transactionUrl = 'rest/default/V1/googlepay/guest/' + _this.options.maskedQuoteId + '/place-order';
                                    }
                                    return storage.post(
                                        urlBuilder.build(transactionUrl),
                                        JSON.stringify(payload),
                                        false
                                    ).done(
                                        function (result) {
                                            resolve(result[0]);
                                        }
                                    ).fail(
                                        function (response) {
                                            if (response.responseText != null && response.responseText.indexOf("[{\"transactionState\":\"SUCCESS\"}]") !== -1) {
                                                resolve(
                                                    {
                                                        transactionState: 'SUCCESS',
                                                    }
                                                );
                                            } else {
                                                console.error(response);
                                                resolve(
                                                    {
                                                        transactionState: 'ERROR',
                                                        error: {
                                                            intent: 'PAYMENT_AUTHORIZATION',
                                                            message: 'Unable to process payment with provided payment credentials',
                                                            reason: 'PAYMENT_DATA_INVALID'
                                                        }
                                                    }
                                                );
                                            }
                                        }
                                    );
                                }, 3000
                            );
                        }
                    );
                },

                /**
                 * Processes GooglePay intermediate payment data
                 *
                 * @param {Object} intermediatePaymentData
                 */
                processIntermmediatePayment: function (intermediatePaymentData) {
                    return new Promise(
                        function (resolve, reject) {
                            setTimeout(
                                function () {
                                    let payload = {
                                        'intermediatePaymentData' : {
                                            'callbackTrigger' : intermediatePaymentData.callbackTrigger,
                                            'totalsInformation' : {}
                                        }
                                    };

                                    if (intermediatePaymentData.hasOwnProperty('shippingAddress')) {
                                        payload.intermediatePaymentData.totalsInformation.address = {
                                            'countryId' : intermediatePaymentData.shippingAddress.countryCode,
                                            'regionCode': intermediatePaymentData.shippingAddress.administrativeArea,
                                            'city': intermediatePaymentData.shippingAddress.locality,
                                            'postcode': intermediatePaymentData.shippingAddress.postalCode
                                        };
                                        _this.selectedAddress = payload.intermediatePaymentData.totalsInformation.address;
                                    } else {
                                        payload.intermediatePaymentData.totalsInformation.address = _this.selectedAddress;
                                    }

                                    if (intermediatePaymentData.callbackTrigger === "SHIPPING_OPTION") {
                                        let shippingOptionId = intermediatePaymentData.shippingOptionData.id;
                                        let index = shippingOptionId.search("_");
                                        _this.selectedShippingOption = shippingOptionId;
                                        payload.intermediatePaymentData.totalsInformation.shipping_carrier_code = shippingOptionId.substring(0, index);
                                        payload.intermediatePaymentData.totalsInformation.shipping_method_code = shippingOptionId.substring(index + 1);
                                    }

                                    var transactionUrl;
                                    if (_this.options.isUserLoggedIn) { //Api for logged in customer
                                        transactionUrl = 'rest/default/V1/googlepay/mine/transaction-information';
                                    } else { // Api for guest customer
                                        transactionUrl = 'rest/default/V1/googlepay/guest/' + _this.options.maskedQuoteId + '/transaction-information';
                                    }

                                    return storage.post(
                                        urlBuilder.build(transactionUrl),
                                        JSON.stringify(payload),
                                        false
                                    ).done(
                                        function (result) {
                                            if (intermediatePaymentData.callbackTrigger !== "SHIPPING_OPTION" && result[0].hasOwnProperty('newShippingOptionParameters')) {
                                                _this.selectedShippingOption = result[0].newShippingOptionParameters.defaultSelectedOptionId;
                                            }
                                            resolve(result[0]);
                                        }
                                    ).fail(
                                        function (response) {
                                            console.error(response);
                                            let errorIntent = 'SHIPPING_ADDRESS';
                                            if (intermediatePaymentData.callbackTrigger === "SHIPPING_OPTION") {
                                                errorIntent = 'SHIPPING_OPTION';
                                            }
                                            resolve(
                                                {
                                                    transactionState: 'ERROR',
                                                    error: {
                                                        intent: 'OTHER_ERROR',
                                                        message: 'Unable to update shipping information',
                                                        reason: errorIntent
                                                    }
                                                }
                                            );
                                        }
                                    );
                                }, 3000
                            );
                        }
                    );
                },

                /**
                 * Processes payment authorization request
                 *
                 * @param {Object} paymentData
                 */
                onPaymentAuthorized: function (paymentData) {
                    return new Promise(
                        function (resolve, reject) {
                            // handle the response
                            _this.processPayment(paymentData)
                            .then(
                                function (response) {
                                    resolve(response);
                                    if (response.transactionState === 'SUCCESS') {
                                        var sections = ['cart'];
                                        customerData.invalidate(sections);
                                        customerData.reload(sections, true);
                                        $.mage.redirect(urlBuilder.build("checkout/onepage/success"));
                                    }
                                }
                            )
                            .catch(
                                function (error) {
                                    console.error(error);
                                    reject(error);
                                }
                            );
                        }
                    );
                },

                /**
                 * Processes payment data update request
                 *
                 * @param {Object} intermediatePaymentData
                 */
                onPaymentDataChanged:   function (intermediatePaymentData) {
                    return new Promise(
                        function (resolve, reject) {
                            // handle the response
                            _this.processIntermmediatePayment(intermediatePaymentData)
                            .then(
                                function (response) {
                                    resolve(response);
                                }
                            )
                            .catch(
                                function (error) {
                                    console.error(error);
                                    reject(error);
                                }
                            );
                        }
                    );
                }

            }
        );

        return $.unboundCommerce.googlepayButton;
    }
);
