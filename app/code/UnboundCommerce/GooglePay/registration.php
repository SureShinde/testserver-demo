<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

use Magento\Framework\Component\ComponentRegistrar;

$registrar = new ComponentRegistrar();

if ($registrar->getPath(ComponentRegistrar::MODULE, 'UnboundCommerce_GooglePay') === null) {
    ComponentRegistrar::register(ComponentRegistrar::MODULE, 'UnboundCommerce_GooglePay', __DIR__);
}
