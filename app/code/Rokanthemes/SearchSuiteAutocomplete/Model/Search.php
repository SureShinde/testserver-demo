<?php

namespace Rokanthemes\SearchSuiteAutocomplete\Model;

use \Rokanthemes\SearchSuiteAutocomplete\Helper\Data as HelperData;
use \Rokanthemes\SearchSuiteAutocomplete\Model\SearchFactory;

/**
 * Search class returns needed search data
 */
class Search
{
    /**
     * @var \Rokanthemes\SearchSuiteAutocomplete\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Rokanthemes\SearchSuiteAutocomplete\Model\SearchFactory
     */
    protected $searchFactory;

    /**
     * Search constructor.
     *
     * @param HelperData $helperData
     * @param \Rokanthemes\SearchSuiteAutocomplete\Model\SearchFactory $searchFactory
     */
    public function __construct(
        HelperData $helperData,
        SearchFactory $searchFactory
    ) {
        $this->helperData    = $helperData;
        $this->searchFactory = $searchFactory;
    }

    /**
     * Retrieve suggested, product data
     *
     * @return array
     */
    public function getData()
    {
        $data               = [];
        $autocompleteFields = $this->helperData->getAutocompleteFieldsAsArray();

        foreach ($autocompleteFields as $field) {
            $data[] = $this->searchFactory->create($field)->getResponseData();
        }

        return $data;
    }
}
