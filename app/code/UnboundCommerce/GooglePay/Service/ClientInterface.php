<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service;

/**
 * @api
 */
interface ClientInterface
{
    /**
     * Process authorization request
     *
     * @param  array $data
     * @return array
     */
    public function authorize($data);

    /**
     * Process capture request
     *
     * @param  array $data
     * @return array
     */
    public function capture($data);

    /**
     * Process sale request
     *
     * @param  array $data
     * @return array
     */
    public function sale($data);

    /**
     * Process refund request
     *
     * @param  array $data
     * @return array
     */
    public function refund($data);

    /**
     * Process void request
     *
     * @param  array $data
     * @return array
     */
    public function void($data);
}
