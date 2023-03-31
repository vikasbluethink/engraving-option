<?php

namespace Bluethink\AdvanceReports\Block\Adminhtml\Grid\Column\Renderer;

class Category extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    protected $_categoryFactory;
    public function __construct(
        \Magento\Framework\Locale\ListsInterface $localeLists,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Backend\Block\Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_categoryFactory = $categoryFactory;
    }
    public function render(\Magento\Framework\DataObject $row)
    {
        $category_id = $row->getData($this->getColumn()->getIndex());
        $category = $this->_categoryFactory->create()->load($category_id);
        return '<strong title="' . __('Cat ID: ') . $category_id . '">' . $category->getName() . '</strong>';
    }
}
