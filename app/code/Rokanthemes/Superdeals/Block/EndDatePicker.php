<?php 
namespace Rokanthemes\Superdeals\Block;

use Magento\Framework\Registry;
use Magento\Backend\Block\Template\Context;

class EndDatePicker extends \Magento\Config\Block\System\Config\Form\Field 
{
	/**
     * @var  Registry
     */
    protected $_coreRegistry;
  
    /**
     * @param Context  $context
     * @param Registry $coreRegistry
     * @param array    $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }
  
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        //get configuration element
        $html = $element->getElementHtml();
        $html .= '<script type="text/javascript">
            require(["jquery", "mage/calendar"], function ($) {
                $(document).ready(function () {
					$("#' . $element->getHtmlId() . '").calendar({
						showsTime: true,
						timeFormat: "HH:mm",
						dateFormat: "mm/dd/yy"
					});
                });
            });
            </script>';
        // return datepicker element
        return $html;
    }
}