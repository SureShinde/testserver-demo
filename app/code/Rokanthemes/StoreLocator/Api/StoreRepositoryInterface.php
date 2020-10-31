<?php
/**
 * Copyright © 2019 Rokanthemes. All rights reserved.
 */

namespace Rokanthemes\StoreLocator\Api;

use \Rokanthemes\StoreLocator\Api\Data\StoreInterface;


interface StoreRepositoryInterface
{

    public function get($id);
    public function save(StoreInterface $model);
    public function delete(StoreInterface $model);
    public function deleteById($id);
}
