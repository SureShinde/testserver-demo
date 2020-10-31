<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model\Ui;

use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Store\Model\StoreManagerInterface;
use UnboundCommerce\GooglePay\Gateway\Config\Config;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = Config::CODE;

    /**
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @param QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor
     *
     * @param Config                $config
     * @param CheckoutSession       $checkoutSession
     * @param CustomerSession       $customerSession
     * @param QuoteIdMaskFactory    $quoteIdMaskFactory
     * @param StoreManagerInterface $storeManager
     * @param AssetRepository       $assetRepository
     * @param EavConfig             $eavConfig
     */
    public function __construct(
        Config $config,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        StoreManagerInterface $storeManager,
        AssetRepository $assetRepository,
        EavConfig $eavConfig
    ) {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->storeManager = $storeManager;
        $this->assetRepository =  $assetRepository;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Check whether telephone is required for address.
     *
     * @return bool
     */
    protected function isTelephoneRequired()
    {
        try {
            return ($this->eavConfig->getAttribute('customer_address', 'telephone')->getIsRequired()  == "1");
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get transaction information
     *
     * @return array
     */
    public function getTransactionInfo()
    {
        $quote = $this->checkoutSession->getQuote();
        if ($quote->isVirtual()) {
            $totals = $quote->getTotals();
        } else {
            $totals = $quote->getShippingAddress()->getTotals();
        }

        $data = [];
        $data['displayItems'] = [];
        foreach ($totals as $code => $total) {
            $arr = $total->toArray();
            if ($code == 'grand_total') {
                $data['totalPriceLabel'] = $arr['title'];
                $data['totalPrice'] = number_format($arr['value'], 2, '.', '');
            } elseif ($arr['value'] != null) {
                $item = [];
                if ($code == 'subtotal') {
                    $item['type'] = 'SUBTOTAL';
                } elseif ($code == 'tax') {
                    $item['type'] = 'TAX';
                } else {
                    $item['type'] = 'LINE_ITEM';
                }
                $item['label'] = $arr['title'];
                $item['price'] = number_format($arr['value'], 2, '.', '');
                $data['displayItems'][] = $item;
            }
        }

        return $data;
    }

    /**
     * Get masked quote id
     *
     * @return int|null
     * @throws \Exception
     */
    public function getMaskedQuoteId()
    {
        $quote = $this->checkoutSession->getQuote();
        if (!$quote->getCustomer()->getId()) {
            /**
 * @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask 
*/
            $quoteIdMask = $this->quoteIdMaskFactory->create();
            return $quoteIdMask->load(
                $this->checkoutSession->getQuote()->getId(),
                'quote_id'
            )->getMaskedId();
        }
        return null;
    }

    /**
     * @param  string $fileId
     * @param  array  $params
     * @return int|null
     * @throws \Exception
     */
    public function getViewFileUrl($fileId, array $params = [])
    {
        return $this->assetRepository->getUrlWithParams($fileId, $params);
    }

    /**
     * Retrieve GooglePay checkout configuration array
     *
     * @return array
     * @throws \Exception
     */
    public function getConfig()
    {
        $quote = $this->checkoutSession->getQuote();
        $storeId = $quote->getStoreId();
        $estimatedAmount = number_format($quote->getGrandTotal(), 2, '.', '');
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive($storeId),
                    'merchantName' => $this->config->getMerchantName($storeId),
                    'isBillingAddressRequired' => (int)$this->config->isBillingAddressRequired($storeId),
                    'isShippingAddressRequired' => (int)$this->config->isShippingAddressRequired($storeId),
                    'isEmailRequired' => (int)$this->config->isEmailRequired($storeId),
                    'title' => $this->config->getTitle(),
                    'googlePayMark' => $this->getViewFileUrl('UnboundCommerce_GooglePay::images/GooglePay_mark_800_gray.png'),
                    'widgetConfig' => json_encode(
                        [
                        'UnboundCommerce_GooglePay/js/googlepay-button' => [
                            'active' => $this->config->isActive($storeId),
                            'merchantId' => $this->config->getMerchantId($storeId),
                            'merchantName' => $this->config->getMerchantName($storeId),
                            'shippingAddressRequired' => $this->config->isShippingAddressRequired($storeId) == "1",
                            'billingAddressRequired' => $this->config->isBillingAddressRequired($storeId) == "1",
                            'ccTypes' => $this->config->getAvailableCardTypes($storeId),
                            'buttonColor' => $this->config->getButtonColor($storeId),
                            'buttonType' => $this->config->getButtonType($storeId),
                            'showInMiniCart' => $this->config->showInMiniCart($storeId),
                            'quoteId' => $quote->getEntityId(),
                            'maskedQuoteId' => $this->getMaskedQuoteId(),
                            'isUserLoggedIn' => $this->customerSession->isLoggedIn(),
                            'estimatedAmount' => $estimatedAmount,
                            'currencyCode' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
                            'gatewayInfo' => $this->config->getGatewayFrontendCredentials(),
                            'callback' => null,
                            'transactionInfo' => $this->getTransactionInfo(),
                            'isQuoteVirtual' => $quote->isVirtual(),
                            'isMiniCart' => false,
                            'isTelephoneRequired' => $this->isTelephoneRequired()
                        ]
                        ]
                    )
                ]
            ]
        ];
    }
}
