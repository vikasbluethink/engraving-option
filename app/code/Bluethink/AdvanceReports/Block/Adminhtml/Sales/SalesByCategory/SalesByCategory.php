<?php
namespace Bluethink\AdvanceReports\Block\Adminhtml\Sales\SalesByCategory;

//use Bluethink\AdvanceReports\Block\Adminhtml\Sales\SalesByCategory\SalesByCategoryGrid;
use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;

/**
 * Backend grid container block
 */
class SalesByCategory extends Container
{
    /**
     * @var string
     */
    protected $_template = 'Bluethink_AdvanceReports::report/grid/container.phtml';

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_blockGroup = 'Bluethink_Reports';
        $this->_controller = 'adminhtml_salesbycategory';
        parent::_construct();

        $this->buttonList->remove('add');
        $this->addButton(
            'filter_form_submit',
            ['label' => __('Show Report'), 'onclick' => 'filterFormSubmit()', 'class' => 'primary']
        );
    }

    public function getFilterUrl()
    {
        $this->getRequest()->setParam('filter', null);
        return $this->getUrl('*/*/salesByCategory', ['_current' => true]);
    }


    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout(): Sales|Container
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(SalesByCategoryGrid::class, 'sales.by.category')
        );
        return parent::_prepareLayout();
    }


    /**
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }
}
