<?php
/**
 * NOTICE OF LICENSE
 * You may not sell, distribute, sub-license, rent, lease or lend complete or portion of software to anyone.
 *
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future.
 *
 * @package   RLTSquare_BestSeller
 * @copyright Copyright (c) 2017 RLTSquare (https://www.rltsquare.com)
 * @contacts  support@rltsquare.com
 * @license  See the LICENSE.md file in module root directory
 */

namespace RLTSquare\BestSeller\Block\Product;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class GridProduct
 * @package RLTSquare\BestSeller\Block
 */
class GridProduct extends \Magento\Backend\Block\Dashboard\Tab\Products\Ordered
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productloader;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Block\Product\ListProduct
     */
    protected $listProduct;

    /**
     * @var \Magento\Catalog\Helper\Output
     */
    protected $helperCatalog;

    /**
     * @var \Magento\Reports\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productsFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManager;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * GridProduct constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $productsFactory
     * @param \Magento\Catalog\Model\ProductFactory $productloader
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Block\Product\ListProduct $listProduct
     * @param \Magento\Catalog\Helper\Output $helperCatalog
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory $collectionFactory
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $productsFactory,
        \Magento\Catalog\Model\ProductFactory $productloader,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Block\Product\ListProduct $listProduct,
        \Magento\Catalog\Helper\Output $helperCatalog,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory $collectionFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        array $data = []
    ){
        parent::__construct($context, $backendHelper, $moduleManager, $collectionFactory);
        $this->productloader = $productloader;
        $this->storeManager = $storeManager;
        $this->listProduct = $listProduct;
        $this->helperCatalog = $helperCatalog;
        $this->productsFactory = $productsFactory;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->_moduleManager = $moduleManager;
        $this->_collectionFactory = $collectionFactory;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @return $this|\Magento\Backend\Block\Dashboard\Tab\Products\Ordered
     */
    protected function _prepareCollection()
    {
        parent::_prepareCollection();
        return $this;
    }

    /**
     * Apply pagination to collection
     *
     * @return void
     */
    protected function _preparePage()
    {
        $this->getCollection()->setPageSize($this->scopeConfig->getValue('bestSeller/bestSellerGroup/display_limit', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
    }

    /**
     * @param $id
     * @return int
     */
    protected function stockStatus($id)
    {
        if($this->scopeConfig->getValue('bestSeller/bestSellerGroup/stock', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))
        {
            $stock = $this->stockRegistry->getProductStockStatus($id);
            return $stock;
        }
        else
            return true;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getLoadProduct($id)
    {
        if($this->stockStatus($id))
            return $this->productloader->create()->load($id);
        else
            return null;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBaseURLofStore(){
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * @return \Magento\Catalog\Block\Product\ListProduct
     */
    public function getListProduct(){
        return $this->listProduct;
    }

    /**
     * @return \Magento\Catalog\Helper\Output
     */
    public function getCatalogHelper(){
        return $this->helperCatalog;
    }

    /**
     * @param $productImage
     * @return mixed
     */
    public function getLazyLoadedImage($productImage){
        $imageElement = $productImage->toHtml();
        $imageElement = str_replace('src', 'data-lazy', $imageElement);
        return $imageElement;
    }

    /**
     * @return mixed
     */
    public function isEnableDisable()
    {
        $isEnableDisable = $this->scopeConfig->getValue('bestSeller/bestSellerGroup/isEnableDisable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $isEnableDisable;
    }

    /**
     * @return array
     */
    public function getVisibility()
    {
        $map = [
            '' => '',
            'Product Page' => 'catalog_product_view',
            'Category Page' => 'catalog_category_view',
            'Home Page' => 'cms_index_index'
        ];
        $visibilityValues = $this->scopeConfig->getValue('bestSeller/bestSellerGroup/visibility', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $visibility = explode(',', $visibilityValues);

        $matchValues = [];

        foreach ($visibility as $key => $value)
        {
            $matchValues[$value] = $map[$visibility[$key]];
        }
        return $matchValues;
    }

    /**
     * @return bool
     */
    public function getCurrentPagePath()
    {
        $visible = $this->getVisibility();
        $fullActionName = $this->request->getFullActionName();
        return in_array($fullActionName,$visible);
    }
}
