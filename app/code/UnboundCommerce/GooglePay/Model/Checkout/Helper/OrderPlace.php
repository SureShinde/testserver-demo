<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model\Checkout\Helper;

use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

/**
 * Class OrderPlace
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderPlace extends AbstractHelper
{
    /**
     * @var CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Data
     */
    protected $checkoutHelper;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * Constructor
     *
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $quoteRepository
     * @param Data                    $checkoutHelper
     * @param Session                 $session
     */
    public function __construct(
        CartManagementInterface $cartManagement,
        Session $session,
        Data $checkoutHelper,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->cartManagement = $cartManagement;
        $this->checkoutHelper = $checkoutHelper;
        $this->quoteRepository = $quoteRepository;
        $this->session = $session;
    }

    /**
     * Execute operation
     *
     * @param  Quote $quote
     * @return void
     * @throws LocalizedException
     */
    public function execute(Quote $quote)
    {
        $checkoutMethod = $this->getCheckoutMethod($quote);

        if ($checkoutMethod === Onepage::METHOD_GUEST) {
            $this->prepareGuestQuote($quote);
            $this->quoteRepository->save($quote);
        }

        $this->disabledQuoteAddressValidation($quote);
        $quote->collectTotals();
        $this->cartManagement->placeOrder($quote->getId());
    }

    /**
     * Get checkout method
     *
     * @param  Quote $quote
     * @return string
     */
    private function getCheckoutMethod(Quote $quote)
    {
        if ($this->session->isLoggedIn()) {
            return Onepage::METHOD_CUSTOMER;
        }
        if (!$quote->getCheckoutMethod()) {
            if ($this->checkoutHelper->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(Onepage::METHOD_GUEST);
            } else {
                $quote->setCheckoutMethod(Onepage::METHOD_REGISTER);
            }
        }

        return $quote->getCheckoutMethod();
    }

    /**
     * Prepare quote for guest checkout order submit
     *
     * @param  Quote $quote
     * @return void
     */
    private function prepareGuestQuote(Quote $quote)
    {
        $quote->setCustomerId(null)
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
    }
}
