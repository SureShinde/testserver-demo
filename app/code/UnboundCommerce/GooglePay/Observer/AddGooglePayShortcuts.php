<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Observer;

use Magento\Catalog\Block\ShortcutButtons;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 *  GooglePayShortcuts Observer
 */
class AddGooglePayShortcuts implements ObserverInterface
{
    /**
     * Block class
     */
    const GPAY_SHORTCUT_BLOCK = \UnboundCommerce\GooglePay\Block\Minicart\Button::class;

    const QUOTE_SHORTCUT_BUTTONS = \Magento\Checkout\Block\QuoteShortcutButtons::class;

    /**
     * Add shortcut buttons
     *
     * @param  Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        /**
 * @var \Magento\Catalog\Block\ShortcutButtons $shortcutButtons
*/
        $shortcutButtons = $observer->getEvent()->getContainer();

        /**
 * @var \Magento\Framework\View\Element\Template $shortcut
*/
        $shortcut = $shortcutButtons->getLayout()->createBlock(self::GPAY_SHORTCUT_BLOCK);

        $shortcut->setCartName(get_class($shortcutButtons));

        $shortcut->setIsInCart(get_class($shortcutButtons) == self::QUOTE_SHORTCUT_BUTTONS);

        $shortcut->setIsInCatalogProduct($observer->getEvent()->getIsCatalogProduct());

        $shortcut->setShowOrPosition(ShortcutButtons::POSITION_BEFORE);

        $shortcut->setAttribute('before', '-');

        $shortcutButtons->addShortcut($shortcut);
    }
}
