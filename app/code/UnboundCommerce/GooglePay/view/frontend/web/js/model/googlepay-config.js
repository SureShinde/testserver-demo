/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

define(
    ['uiRegistry','domReady!'],
    function (registry, domReady) {
        'use strict';

        var config = registry.get('GooglePayMerchantConfig') || {};

        return {
            /**
             * Get config value
             */
            getValue: function (key, defaultValue) {
                if (config.hasOwnProperty(key)) {
                    return config[key];
                } else if (defaultValue !== undefined) {
                    return defaultValue;
                }
            },

            /**
             * Checks whether GooglePay is defined
             */
            isDefined: function () {
                return registry.get('GooglePayMerchantConfig') !== undefined;
            }
        }

    }
);

