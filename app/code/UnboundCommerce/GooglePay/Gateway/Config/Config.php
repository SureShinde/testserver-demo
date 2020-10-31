<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use UnboundCommerce\GooglePay\Model\Adminhtml\Source\GatewayName;

/**
 * Payment Gateway Config
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    const CODE = 'googlepay';

    const KEY_ACTIVE = 'active';

    const KEY_ENVIRONMENT = 'environment';

    const KEY_TITLE = 'title';

    const KEY_CC_TYPES = 'cc_types';

    const KEY_MERCHANT_ID = 'merchant_id';

    const KEY_MERCHANT_NAME = 'merchant_name';

    const KEY_GATEWAY = 'gateway_id';

    const KEY_BUTTON_COLOR = 'button_color';

    const KEY_BUTTON_TYPE = 'button_type';

    const KEY_PRODUCT_BUTTON_COLOR = 'product_button_color';

    const KEY_PRODUCT_BUTTON_TYPE = 'product_button_type';

    const KEY_SHOW_IN_MINICART = 'show_in_mini_cart';

    const KEY_SHOW_AGREEMENTS_IN_ADDONS = 'show_agreements_in_addons';

    const KEY_SHOW_COUPON_IN_ADDONS = 'show_coupon_in_addons';

    const KEY_SHOW_IN_PDP = 'show_in_pdp';

    const KEY_DEBUG = 'debug';

    const CC_TYPES_MAPPER = ['AE' => 'AMEX', 'DI' => 'DISCOVER', 'IC' => 'INTERAC', 'JCB' => 'JCB', 'MC' => 'MASTERCARD', 'VI' => 'VISA'];

    const REQUIRED_GATEWAY_CREDENTIALS = ['gateway_id', 'environment'];

    const ADYEN_CREDENTIALS = ['gateway_merchant_id', 'ENC.api_key', 'live_endpoint_url_prefix', 'three_d_secure_type', 'three_d_secure_value'];

    const BLUESNAP_CREDENTIALS = ['gateway_merchant_id', 'username', 'ENC.password'];

    const BRAINTREE_CREDENTIALS = ['gateway_merchant_id', 'public_key', 'ENC.private_key'];

    const CYBERSOURCE_CREDENTIALS = ['gateway_merchant_id', 'api_key', 'ENC.secret_key'];

    const FIRSTDATA_CREDENTIALS = ['gateway_merchant_id', 'api_key', 'ENC.api_secret', 'ENC.token'];

    const MONERIS_CREDENTIALS = ['gateway_store_id', 'web_merchant_key', 'ENC.api_token', 'dynamic_descriptor'];

    const STRIPE_CREDENTIALS = ['publishable_key', 'ENC.secret_key'];

    const VANTIV_CREDENTIALS = ['gateway_merchant_id', 'username', 'ENC.password', 'report_group'];

    const WORLDPAY_CREDENTIALS = ['gateway_merchant_id', 'username', 'ENC.password'];

    const ADYEN_WEBHOOK_CREDENTIALS = ['webhook_username', 'ENC.webhook_password'];

    const DEFAULT_FRONTEND_CREDENTIALS = ['gateway_merchant_id'];

    const BRAINTREE_FRONTEND_CREDENTIALS = ['gateway_merchant_id', 'client_key'];

    const MONERIS_FRONTEND_CREDENTIALS = ['gateway_store_id'];

    const STRIPE_FRONTEND_CREDENTIALS = ['publishable_key'];

    const VANTIV_FRONTEND_CREDENTIALS = ['pay_page_id', 'report_group'];

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * Config constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface   $encryptor
     * @param string|null          $methodCode
     */

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        $methodCode = null
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * Check whether GooglePay has been enabled
     *
     * @param  int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool) $this->getValue(self::KEY_ACTIVE, $storeId);
    }

    /**
     * Get available card types
     *
     * @param  int|null $storeId
     * @return array
     */
    public function getAvailableCardTypes($storeId = null)
    {
        $value= $this->getValue(self::KEY_CC_TYPES, $storeId);
        $ccTypes = !empty($value) ? explode(',', $value) : [];
        $availableCardTypes = [];
        foreach (self::CC_TYPES_MAPPER as $key => $value) {
            if (in_array($key, $ccTypes)) {
                array_push($availableCardTypes, $value);
            }
        }
        return $availableCardTypes;
    }

    /**
     * Get merchant id
     *
     * @param  int|null $storeId
     * @return string
     */
    public function getMerchantId($storeId = null)
    {
        $merchantId = $this->getValue(self::KEY_MERCHANT_ID, $storeId);

        return $merchantId;
    }

    /**
     * Get merchant name
     *
     * @param  int|null $storeId
     * @return string
     */
    public function getMerchantName($storeId = null)
    {
        $merchantName = $this->getValue(self::KEY_MERCHANT_NAME, $storeId);

        return $merchantName;
    }

    /**
     * Check whether email is a required field
     *
     * @param  int|null $storeId
     * @return bool
     */
    public function isEmailRequired($storeId = null)
    {
        return true;
    }

    /**
     * Check whether shipping address is a required field
     *
     * @param  int|null $storeId
     * @return bool
     */
    public function isShippingAddressRequired($storeId = null)
    {
        return true;
    }

    /**
     * Check whether billing address is a required field
     *
     * @param  int|null $storeId
     * @return bool
     */
    public function isBillingAddressRequired($storeId = null)
    {
        return true;
    }

    /**
     * Get button color
     *
     * @param  int|null $storeId
     * @return string
     */
    public function getButtonColor($storeId = null)
    {
        $buttonColor = $this->getValue(self::KEY_BUTTON_COLOR, $storeId);

        return $buttonColor;
    }

    /**
     * Get button type
     *
     * @param  int|null $storeId
     * @return string
     */
    public function getButtonType($storeId = null)
    {
        $buttonType = $this->getValue(self::KEY_BUTTON_TYPE, $storeId);

        return $buttonType;
    }

    /**
     * Get button color for product page
     *
     * @param  int|null $storeId
     * @return string
     */
    public function getProductButtonColor($storeId = null)
    {
        $buttonColor = $this->getValue(self::KEY_PRODUCT_BUTTON_COLOR, $storeId);

        return $buttonColor;
    }

    /**
     * Get button type for product page
     *
     * @param  int|null $storeId
     * @return string
     */
    public function getProductButtonType($storeId = null)
    {
        $buttonType = $this->getValue(self::KEY_PRODUCT_BUTTON_TYPE, $storeId);

        return $buttonType;
    }

    /**
     * Check whether button should be displayed in mini cart
     *
     * @param  int|null $storeId
     * @return bool
     */
    public function showInMiniCart($storeId = null)
    {
        $displayInMiniCart = $this->getValue(self::KEY_SHOW_IN_MINICART, $storeId);

        return $displayInMiniCart;
    }

    /**
     * Check whether agreements should be displayed in mini cart addons
     *
     * @param  int|null $storeId
     * @return bool
     */
    public function showAgreementsInAddons($storeId = null)
    {
        $displayAgreements = $this->getValue(self::KEY_SHOW_AGREEMENTS_IN_ADDONS, $storeId);

        return $displayAgreements;
    }


    /**
     * Check whether button should be displayed in mini cart addons
     *
     * @param  int|null $storeId
     * @return bool
     */
    public function showCouponInAddons($storeId = null)
    {
        $displayCoupon = $this->getValue(self::KEY_SHOW_COUPON_IN_ADDONS, $storeId);

        return $displayCoupon;
    }
    /**
     * Check whether button should be displayed in product page
     *
     * @param  int|null $storeId
     * @return bool
     */
    public function showInPdp($storeId = null)
    {
        $displayInPdp = $this->getValue(self::KEY_SHOW_IN_PDP, $storeId);

        return $displayInPdp;
    }

    /**
     * Check whether debug logging has been enabled
     *
     * @return bool
     */
    public function isDebugLoggingEnabled()
    {
        $isDebugLoggingEnabled = $this->getValue(self::KEY_DEBUG);

        return $isDebugLoggingEnabled;
    }

    /**
     * Check whether debug logging has been enabled
     *
     * @return string
     */
    public function getEnvironment()
    {
        $environment = $this->getValue(self::KEY_ENVIRONMENT);

        return $environment;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getValue(self::KEY_TITLE);
    }

    /**
     * Get gateway id
     *
     * @return string
     */
    public function getGateway()
    {
        $gateway = $this->getValue(self::KEY_GATEWAY);

        return $gateway;
    }

    /**
     * Get information specific to given payment gateway id
     *
     * @param  string   $gatewayId
     * @param  string   $key
     * @param  int|null $storeId
     * @return array
     */
    public function getGatewayData($gatewayId, $key, $storeId = null)
    {
        $array = explode(".", $key, 2);
        $isEncrypted = false;
        if (count($array) > 1 && $array[0] == "ENC") {
            $key = $array[1];
            $isEncrypted = true;
        }
        $path = 'payment/' . self::CODE . "_" . $gatewayId . "/" . $key;
        $val = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        if ($isEncrypted) {
            $val = $this->encryptor->decrypt($val);
        }
        return ['key' => $key, 'value' => $val];
    }

    /**
     * Get Adyen webhook credentials
     *
     * @return array
     */
    public function getAdyenWebhookCredentials()
    {
        $res = [];
        foreach (self::ADYEN_WEBHOOK_CREDENTIALS as $key) {
            $data = $this->getGatewayData(GatewayName::PROCESSOR_ADYEN, $key);
            $res[$data['key']] = $data['value'];
        }
        return $res;
    }

    /**
     * Get payment gateway credentials for frontend usage
     *
     * @return array
     */
    public function getGatewayFrontendCredentials()
    {
        $res = [];
        foreach (self::REQUIRED_GATEWAY_CREDENTIALS as $key) {
            $res[$key] = $this->getValue($key);
        }
        if (isset($res[self::KEY_GATEWAY])) {
            $gatewayId = $res[self::KEY_GATEWAY];
        } else {
            return $res;
        }
        switch ($gatewayId) {
        case GatewayName::PROCESSOR_BRAINTREE:
            $gatewayKeys = self::BRAINTREE_FRONTEND_CREDENTIALS;
            break;
        case GatewayName::PROCESSOR_MONERIS:
            $gatewayKeys = self::MONERIS_FRONTEND_CREDENTIALS;
            break;
        case GatewayName::PROCESSOR_STRIPE:
            $gatewayKeys = self::STRIPE_FRONTEND_CREDENTIALS;
            break;
        case GatewayName::PROCESSOR_VANTIV:
            $gatewayKeys = self::VANTIV_FRONTEND_CREDENTIALS;
            break;
        default:
            $gatewayKeys = self::DEFAULT_FRONTEND_CREDENTIALS;
        }

        foreach ($gatewayKeys as $key) {
            $data = $this->getGatewayData($gatewayId, $key);
            $res[$data['key']] = $data['value'];
        }

        return $res;
    }

    /**
     * Get payment gateway keys
     *
     * @param  string $gatewayId
     * @return array
     */
    public function getGatewayKeys($gatewayId)
    {
        $gatewayKeys = [];
        switch ($gatewayId) {
        case GatewayName::PROCESSOR_ADYEN:
            $gatewayKeys = self::ADYEN_CREDENTIALS;
            break;
        case GatewayName::PROCESSOR_BLUESNAP:
            $gatewayKeys = self::BLUESNAP_CREDENTIALS;
            break;
        case GatewayName::PROCESSOR_BRAINTREE:
            $gatewayKeys = self::BRAINTREE_CREDENTIALS;
            break;
        case GatewayName::PROCESSOR_CYBERSOURCE:
            $gatewayKeys = self::CYBERSOURCE_CREDENTIALS;
            break;
        case GatewayName::PROCESSOR_PAYEEZY:
            $gatewayKeys = self::FIRSTDATA_CREDENTIALS;
            break;
        case GatewayName::PROCESSOR_MONERIS:
            $gatewayKeys = self::MONERIS_CREDENTIALS;
            break;
        case GatewayName::PROCESSOR_STRIPE:
            $gatewayKeys = self::STRIPE_CREDENTIALS;
            break;
        case GatewayName::PROCESSOR_VANTIV:
            $gatewayKeys = self::VANTIV_CREDENTIALS;
            break;
        //            case GatewayName::PROCESSOR_WORLDPAY:
        //                $gatewayKeys = self::WORLDPAY_CREDENTIALS;
        //                break;
        }
        return $gatewayKeys;
    }

    /**
     * Get payment gateway credentials
     *
     * @return array
     */
    public function getMerchantGatewayCredentials()
    {
        $res = [];
        foreach (self::REQUIRED_GATEWAY_CREDENTIALS as $key) {
            $resKey = "gatewayCredentials." . $key;
            $res[$resKey] = $this->getValue($key);
        }

        if (isset($res["gatewayCredentials." . self::KEY_GATEWAY])) {
            $gatewayId = $res["gatewayCredentials." . self::KEY_GATEWAY];
            $gatewayKeys = $this->getGatewayKeys($gatewayId);
            foreach ($gatewayKeys as $key) {
                $data = $this->getGatewayData($gatewayId, $key);
                $resKey = "gatewayCredentials." . $data['key'];
                $res[$resKey] = $data['value'];
            }
        }

        return $res;
    }
}
