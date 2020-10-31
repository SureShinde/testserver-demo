<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Api\Data;

/**
 * Interface CardInfoInterface
 *
 * @api
 */
interface CardInfoInterface
{
    const CARD_DETAILS= 'card_details';
    const CARD_NETWORK = 'card_network';
    const BILLING_ADDRESS = 'billing_address';

    /**
     * Return details about the card
     *
     * @return string|null
     */
    public function getCardDetails();

    /**
     * Set details about the card
     *
     * @param  string $cardDetails
     * @return $this
     */
    public function setCardDetails($cardDetails);

    /**
     * Return payment card network of the selected payment
     *
     * @return string|null
     */
    public function getCardNetwork();

    /**
     * Set payment card network of the selected payment
     *
     * @param  string $cardNetwork
     * @return $this
     */
    public function setCardNetwork($cardNetwork);

    /**
     * Return billing address associated with the card
     *
     * @return \UnboundCommerce\GooglePay\Api\Data\AddressInterface
     */
    public function getBillingAddress();

    /**
     * Set billing address associated with the card
     *
     * @param  \UnboundCommerce\GooglePay\Api\Data\AddressInterface
     * @return $this
     */
    public function setBillingAddress($billingAddress);
}
