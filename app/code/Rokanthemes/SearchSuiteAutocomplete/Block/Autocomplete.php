<?php

namespace Rokanthemes\SearchSuiteAutocomplete\Block;

/**
 * Autocomplete class used for transport config data
 */
class Autocomplete extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Rokanthemes\SearchSuiteAutocomplete\Helper\Data
     */
    protected $helperData;
    protected $_categoryHelper;

    /**
     * Autocomplete constructor.
     *
     * @param \Rokanthemes\SearchSuiteAutocomplete\Helper\Data $helperData
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Rokanthemes\SearchSuiteAutocomplete\Helper\Data $helperData,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Helper\Category $categoryHelper,
        array $data = []
    ) {

        $this->helperData = $helperData;
        $this->_categoryHelper = $categoryHelper;
        parent::__construct($context, $data);
    }
    public function enableSearchByCat() 
    {
        return $this->_scopeConfig->getValue('rokanthemes_searchsuite/searchsuiteautocomplete_main/enabled_search_by_cat', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getCategories()
    {
        return $this->_categoryHelper->getStoreCategories(true , false, true);
    }

    /**
     * Retrieve search delay in milliseconds (500 by default)
     *
     * @return int
     */
    public function getSearchDelay()
    {
        return $this->helperData->getSearchDelay();
    }

    /**
     * Retrieve search action url
     *
     * @return string
     */
    public function getSearchUrl()
    {
        return $this->getUrl("rokanthemes_searchsuiteautocomplete/ajax/index");
    }
}
