<?php
namespace Valtech\TechTest\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * class QuoteSubmitBefore
 */
class QuoteSubmitBefore implements ObserverInterface
{

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        $order = $observer->getOrder();
        $engravingOption = $quote->getData('engraving_option');
        $order->setData('engraving_option', $engravingOption);
        !empty($engravingOption) ? $order->setData('is_engraving', 1) : $order->setData('is_engraving', 0);
    }
}
