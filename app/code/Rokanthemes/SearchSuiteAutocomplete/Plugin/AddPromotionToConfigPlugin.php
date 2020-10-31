<?php

namespace Rokanthemes\SearchSuiteAutocomplete\Plugin;

use \Magento\Framework\App\RequestInterface;

class AddPromotionToConfigPlugin
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var \Rokanthemes\SearchSuiteAutocomplete\Helper\Data
     */
    protected $helper;

    /**
     * AddPromotionToConfigPlugin constructor.
     *
     * @param RequestInterface $request
     * @param \Rokanthemes\SearchSuiteAutocomplete\Helper\Data $helper
     */
    public function __construct(
        RequestInterface $request,
        \Rokanthemes\SearchSuiteAutocomplete\Helper\Data $helper
    ) {
        $this->request = $request;
        $this->helper  = $helper;
    }

    /**
     * @param \Magento\Config\Block\System\Config\Edit $subject
     * @param string $result
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterToHtml(\Magento\Config\Block\System\Config\Edit $subject, $result)
    {
        if ($this->request->getFullActionName() == 'adminhtml_system_config_edit'
            && $this->request->getParam('section') == 'rokanthemes_searchsuite'
            && !$this->helper->isModuleOutputEnabled('Rokanthemes_SearchSuiteSphinx')
        ) {
            $renderer = $subject
                ->getLayout()
                ->createBlock(\Magento\Framework\View\Element\Template::class)
                ->setTemplate('Rokanthemes_SearchSuiteAutocomplete::promotion.phtml');

            return $renderer->toHtml() . $result;
        }

        return $result;
    }
}