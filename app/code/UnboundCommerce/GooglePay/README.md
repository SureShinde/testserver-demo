# UnboundCommerce GooglePay Module for Magento2
GooglePay module helps you to add "Google Pay" to your existing payments processing stack to offer simpler, more secure checkout in your website(s).

## Supported Payment Service Providers
1. Adyen
2. BlueSnap
3. Braintree
4. First Data (fka Payeezy)
5. Moneris
6. Stripe
7. Vantiv


## Requirements
This module supports Magento version 2.3 and higher.
The "require" field in [composer.json](composer.json) file lists the packages required by this module.

## Installation
Our extension can be found in Magento Marketplace :
[https://marketplace.magento.com/extensions.html](https://marketplace.magento.com/extensions.html)
Please look at [Magento's documentation](https://docs.magento.com/marketplace/user_guide/buyers/install-extension.html) for more details on how to install an extension.

## Configuration
Before you enable and configure the module, make sure you have set up a sandbox/live merchant account on Google Pay and any one of the supported payment processors listed above.
1. Log in to your Magento admin panel.
2. On the Admin sidebar, go to **Stores > Configuration**.
3. On the Configuration page that loads, go to **Sales > Payment Methods**.
4. Click the **Configure** button next to the **Google Pay** logo.

    #### Google Pay Credentials
    1. Enter your **Google Pay Merchant Account Id** and **Merchant Name** under this section.

    #### Payment Gateway Credentials
    1. Select your payment processor from the **Gateway** dropdown menu.  
    2. Select the environment in which you wish to process transactions from the **Environment** dropdown menu.
    3. Add your **payment processor credentials** in the input fields that appear below the environment dropdown menu.

    #### Google Pay Settings
    1. Enable or disable Google Pay module using the **Enable Google Pay** option.  
    2. Use **Payment Action** to select the action that has to be triggered when a user places an order using Google Pay.
    It can be either an **Authorize** or **Authorize and Capture (Invoice)**.
    3. Select the supported card networks from **Allowed Card Networks**.
    4. Set **Enable Logging at Debug Level** to **Yes** in order to record the entire request and responses that are being sent to and received from the payment processors in a log file.
       Note: We recommend this to be turned off since it may impact performance and use up disk space on your server(s).

    #### Google Pay Advanced Settings
    1. **Button Color** allows you to select the color of the Google Pay button.
    2. **Button Type** allows you to modify the appearance of the Google Pay button.
        i) **Long** displays **"Buy with Google Pay"** in the Google Pay button which is the default option. A translated button label may appear if a language specified in the viewer's browser matches an available language.  
        ii) **Short** displays Google Pay button **without the "Buy with"** text.  
    3. **Show Google Pay Button in Minicart** option allows you to display Google Pay button in the mini cart.
    4. **Show Checkout Agreements in Minicart Addons** option allows you to display checkout agreements block in the mini cart.
    5. **Show Coupon Block in Minicart Addons** option allows you to display coupon code block in the mini cart.
    Google Pay button will be displayed on the cart page, checkout(Shipping, Review & Payments) pages by default if the module has been enabled.

## Cron
Ensure cron is running on your server to automatically update pending transactions and notifications received from payment processors.

The cron runs once in every 15 minutes for Adyen, Braintree and Stripe to get the latest status of pending transactions.
Other payment processors do not require cron as transactions through these processors do not have a pending state and process transactions at real time.
Transactions that are in pending state for more than 3 days are assumed to be declined and are closed.

Please review [Magento's documentation](http://devdocs.magento.com/guides/v2.0/config-guide/cli/config-cli-subcommands-cron.html) for more details on how to configure and run cron.

## License
[Open Source License](LICENSE.txt)
[Academic Free License](LICENSE_AFL.txt)
