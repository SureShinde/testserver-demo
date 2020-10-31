<?php
namespace Rokanthemes\Superdeals\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_objectManager;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
        $this->_objectManager= $objectManager;
        parent::__construct($context);
    }
    public function showLableSalePriceCategory($_item, $t = false) {
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
			$html = '<div class="percent-saleoff"><span><label>'.round($pec).'%</label> '.__('OFF').'</span></div>';
			if($t){
				$html = round($pec).'%';
			}
		}
		return $html;
	}
	public function getPriceDisplayCustom($html) {
		return preg_replace('/(<[^>]+) id=".*?"/i', '$1', $html);
	}
}
