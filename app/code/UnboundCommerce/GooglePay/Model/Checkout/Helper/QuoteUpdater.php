<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model\Checkout\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use UnboundCommerce\GooglePay\Gateway\Config\Config;
use UnboundCommerce\GooglePay\Model\Adminhtml\Source\GatewayName;

/**
 * Class QuoteUpdater
 */
class QuoteUpdater extends AbstractHelper
{
    const PAYMENT_METHOD_DESCRIPTION = 'paymentMethodDescription';
    const PAYMENT_TOKEN = 'paymentToken';
    const PAYMENT_DATA = 'paymentData';
    const TOKENIZATION_DATA = 'tokenizationData';
    const JSON_ENCODED_PAYMENT_DATA = 'jsonEncodedPaymentData';
    const GATEWAY_ID = 'gatewayId';
    const ENVIRONMENT = 'environment';
    const EMAIL = 'email';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var AddressHelper
     */
    private $addressHelper;

    /**
     * Constructor
     *
     * @param AddressHelper           $addressHelper
     * @param Config                  $config
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        AddressHelper $addressHelper,
        Config $config,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->addressHelper = $addressHelper;
        $this->config = $config;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Execute operation
     *
     * @param  Quote                                                    $quote
     * @param  \UnboundCommerce\GooglePay\Api\Data\PaymentDataInterface $paymentData
     * @return void
     * @throws LocalizedException
     */
    public function execute($quote, $paymentData)
    {
        if (empty($paymentData) || !$quote) {
            throw new \InvalidArgumentException('"PaymentData" or "quote" has not been provided');
        }

        $payment = $quote->getPayment();
        $payment->setMethod(Config::CODE);
        $gatewayId = $this->config->getGateway();
        $paymentMethodData = $paymentData->getPaymentMethodData();
        $email = $paymentData->getEmail();
        $payment->setAdditionalInformation(self::GATEWAY_ID, $gatewayId);
        $payment->setAdditionalInformation(self::ENVIRONMENT, $this->config->getEnvironment());
        $payment->setAdditionalInformation(self::PAYMENT_METHOD_DESCRIPTION, $paymentMethodData->getDescription());
        $payment->setAdditionalInformation(self::EMAIL, $email);
        switch ($gatewayId) {
        case GatewayName::PROCESSOR_BLUESNAP:
            $payment->setAdditionalInformation(
                self::JSON_ENCODED_PAYMENT_DATA,
                $paymentData->getJsonEncodedString()
            );
            break;
        case GatewayName::PROCESSOR_MONERIS:
            $payment->setAdditionalInformation(
                self::TOKENIZATION_DATA,
                $this->getTokenizationDataAsArray($paymentData->getPaymentMethodData()->getTokenizationData())
            );
            break;
        default:
            $token = $paymentMethodData->getTokenizationData()->getToken();
            $payment->setAdditionalInformation(self::PAYMENT_TOKEN, $token);
        }

        $billingAddress = $paymentMethodData->getInfo()->getBillingAddress();
        $shippingAddress = $paymentData->getShippingAddress();
        $this->updateQuote($quote, $shippingAddress, $billingAddress, $email);
    }

    /**
     * Update quote data
     *
     * @param  Quote                                                $quote
     * @param  \UnboundCommerce\GooglePay\Api\Data\AddressInterface $shippingAddress
     * @param  \UnboundCommerce\GooglePay\Api\Data\AddressInterface $billingAddress
     * @param  string                                               $email
     * @return void
     */
    protected function updateQuote(Quote $quote, $shippingAddress, $billingAddress, $email)
    {
        $this->updateQuoteAddress($quote, $shippingAddress, $billingAddress, $email);
        $this->disabledQuoteAddressValidation($quote);

        $quote->collectTotals();
        $quote->setDataChanges(true);

        $this->quoteRepository->save($quote);
    }

    /**
     * Update quote address
     *
     * @param  Quote                                                $quote
     * @param  \UnboundCommerce\GooglePay\Api\Data\AddressInterface $shippingAddress
     * @param  \UnboundCommerce\GooglePay\Api\Data\AddressInterface $billingAddress
     * @param  string                                               $email
     * @return void
     */
    protected function updateQuoteAddress($quote, $shippingAddress, $billingAddress, $email)
    {
        if (!$quote->getIsVirtual() && $shippingAddress) {
            $this->updateShippingAddress($quote, $shippingAddress);
            $quoteShippingAddress = $quote->getShippingAddress();
            $quoteShippingAddress->setCollectShippingRates(true)->collectShippingRates();

            if ($shippingAddress->getShippingMethod()) {
                $quoteShippingAddress->setShippingMethod($shippingAddress->getShippingMethod());
            }

            /**
             * Unset shipping assignment to prevent from saving/applying outdated data
             *
             * @see \Magento\Quote\Model\QuoteRepository\SaveHandler::processShippingAssignment
             */
            if ($quote->getExtensionAttributes()) {
                $quote->getExtensionAttributes()->setShippingAssignments(null);
            }
        }

        $this->updateBillingAddress($quote, $billingAddress, $email);
    }

    /**
     * Update shipping address
     *
     * @param  Quote                                                $quote
     * @param  \UnboundCommerce\GooglePay\Api\Data\AddressInterface $addressData
     * @return void
     */
    protected function updateShippingAddress($quote, $addressData)
    {
        if ($addressData) {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->unsCustomerAddressId();
            $quote->removeAddress($shippingAddress->getId());
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setSaveInAddressBook(false);
            $shippingAddress->setSameAsBilling(false);
            $this->updateNameData($shippingAddress, $addressData->getName());
            $this->updateAddressData($shippingAddress, $addressData);
        }
    }

    /**
     * Update billing address
     *
     * @param  Quote                                                $quote
     * @param  \UnboundCommerce\GooglePay\Api\Data\AddressInterface $addressData
     * @param  string                                               $email
     * @return void
     */
    protected function updateBillingAddress($quote, $addressData, $email)
    {
        $billingAddress = $quote->getBillingAddress();

        $oldAddressId = $billingAddress->getId();
        $quote->removeAddress($oldAddressId);
        $billingAddress = $quote->getBillingAddress();
        $billingAddress->unsCustomerAddressId();
        $billingAddress->setEmail($email);
        if ($addressData) {
            $this->updateNameData($billingAddress, $addressData->getName());
            $this->updateAddressData($billingAddress, $addressData);
        }
        $billingAddress->setSaveInAddressBook(false);
    }

    /**
     * Update name data
     *
     * @param  Address $address
     * @param  string  $name
     * @return void
     */
    protected function updateNameData($address, $name)
    {
        $nameArray = explode(" ", $name, 3);

        $address->setFirstname($nameArray[0]);
        if (sizeof($nameArray) == 2) {
            $address->setLastname($nameArray[1]);
        } elseif (sizeof($nameArray) == 3) {
            $address->setMiddlename($nameArray[1]);
            $address->setLastname($nameArray[2]);
        }
    }

    /**
     * Sets address data
     *
     * @param  Address                                              $address
     * @param  \UnboundCommerce\GooglePay\Api\Data\AddressInterface $addressData
     * @return void
     */
    protected function updateAddressData($address, $addressData)
    {
        $street = $addressData->getAddress1();
        if ($addressData->getAddress2()) {
            $street = $street . ", " . $addressData->getAddress2();
        }
        if ($addressData->getAddress3()) {
            $street = $street . ", " . $addressData->getAddress3();
        }
        $address->setStreet([$street]);
        $address->setCity($addressData->getLocality());
        $countryCode = $addressData->getCountryCode();
        $address->setCountryId($countryCode);
        $administrativeArea = $addressData->getAdministrativeArea();
        if ($administrativeArea) {
            if (!in_array($countryCode, array('US', 'CA'))) {
                $administrativeArea = $this->addressHelper->getRegionCodeByName($administrativeArea, $countryCode);
            }
            if ($administrativeArea) {
                $address->setRegionCode($administrativeArea);
            }
        }

        $address->setPostcode($addressData->getPostalCode());
        $address->setCustomerAddressId(null);
        if ($addressData->getPhoneNumber()) {
            $address->setTelephone($addressData->getPhoneNumber());
        }
    }

    /**
     * Return tokenization data as array
     *
     * @param  \UnboundCommerce\GooglePay\Api\Data\PaymentMethodTokenizationDataInterface $tokenizationData
     * @return array
     */
    protected function getTokenizationDataAsArray($tokenizationData)
    {
        $data = [];
        $data['type'] = $tokenizationData->getType();
        $data['token'] = $tokenizationData->getToken();

        return $data;
    }
}
