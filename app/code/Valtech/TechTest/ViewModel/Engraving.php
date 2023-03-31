<?php
namespace Valtech\TechTest\ViewModel;
class Engraving implements \Magento\Framework\View\Element\Block\ArgumentInterface
{

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;

    }

    public function getConfigValue($value = '')
    {
        return $this->scopeConfig->getValue($value, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
