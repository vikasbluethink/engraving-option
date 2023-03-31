<?php
namespace Valtech\TechTest\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * class AddEngravingOption
 */
class AddEngravingOption implements ObserverInterface
{
    /**
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->request = $request;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var \Magento\Quote\Model\Quote\Item $item */
        $item = $observer->getQuoteItem();
        $quote = $item->getQuote();
        $post = $this->request->getParam('engraving-text');
        $quote->setEngravingOption($post);
    }
}
