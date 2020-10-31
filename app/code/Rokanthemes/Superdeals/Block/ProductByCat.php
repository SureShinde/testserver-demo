<?php 
namespace Rokanthemes\Superdeals\Block;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\DataObject\IdentityInterface;

class ProductByCat extends \Magento\Catalog\Block\Product\AbstractProduct 
{
	/**
     * Default toolbar block name
     *
     * @var string
     */
    protected $_defaultToolbarBlock = 'Magento\Catalog\Block\Product\ProductList\Toolbar';

    /**
     * Product Collection
     *
     * @var AbstractCollection
     */
    protected $_productCollection;

    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_catalogLayer;

    /**
     * @var \Magento\Framework\Data\Helper\PostHelper
     */
    protected $_postDataHelper;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $urlHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;
    protected $productCollectionFactory;
    protected $storeManager;
    protected $catalogConfig;
    protected $productVisibility;
    protected $scopeConfig;

    /**
     * @param Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Catalog\Model\Product\Visibility $productVisibility,
        array $data = []
    ) {
        $this->_catalogLayer = $layerResolver->get();
        $this->_postDataHelper = $postDataHelper;
        $this->categoryRepository = $categoryRepository;
        $this->urlHelper = $urlHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $context->getStoreManager();
        $this->catalogConfig = $context->getCatalogConfig();
        $this->productVisibility = $productVisibility;
        parent::__construct(
            $context,
            $data
        );
    }
	public function getConfig($value=''){

	   $config =  $this->_scopeConfig->getValue('superdeals/configuration/'.$value, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	   return $config; 
	 
	}
	public function getCategory() {
		return $this->categoryRepository->get($this->getConfig('set_category'));
	}
	public function getProducts() {
		$storeId    = $this->storeManager->getStore()->getId();
		$products = $this->productCollectionFactory->create()->setStoreId($storeId);
		$products->joinField(
            'position',
            'catalog_category_product',
            'position',
            'product_id=entity_id',
            'category_id=' . (int)$this->getConfig('set_category')
        );
		$products
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite($this->getConfig('set_category'))
            ->setVisibility($this->productVisibility->getVisibleInCatalogIds());
        $products->setPageSize($this->getConfig('limit_products'))->setCurPage(1);
		$this->_eventManager->dispatch(
            'catalog_block_product_list_collection',
            ['collection' => $products]
        );
		return $products;
	}
	public function getStoreUrlBlock() {
		$_objectManager = \Magento\Framework\App\ObjectManager::getInstance(); //instance of\Magento\Framework\App\ObjectManager
		$storeManager = $_objectManager->get('Magento\Store\Model\StoreManagerInterface'); 
		return $storeManager->getStore();
	}
	public function getCurrentTime() {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$objDate = $objectManager->create('Magento\Framework\Stdlib\DateTime\DateTime');
		return $objDate->gmtDate('Y-m-d H:i');
	}
	public function softTrim($text, $count, $wrapText='...'){
		if(strlen($text)>$count){
			preg_match('/^.{0,' . $count . '}(?:.*?)\b/siu', $text, $matches);
			$text = $matches[0];
		}else{
			$wrapText = '';
		}
		return $text . $wrapText;
	}
	public function showLableSalePrice($_item, $t = false) {
		if($_item->getTypeId() != 'simple'){
			return '';
		}
		$price = $_item->getPrice();
		$price_final = $_item->getPriceInfo()->getPrice('final_price')->getValue();
		$html = '';
		if($price && $price_final && $price_final < $price){
			$price = (float)$price;
			$price_final = (float)$price_final;
			$sale = $price - $price_final;
			$pec = ($sale / $price) * 100;
			$html = '<label>-'.round($pec).'%</label>';
			if($t){
				$html = round($pec).'%';
			}
		}
		return $html;
	}
	public function getBaseUrlMediaCustom()
    {
		$om = \Magento\Framework\App\ObjectManager::getInstance();
		$storeManager = $om->get('Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }
}