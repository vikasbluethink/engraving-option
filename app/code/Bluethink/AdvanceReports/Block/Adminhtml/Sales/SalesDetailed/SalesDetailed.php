<?php
namespace Bluethink\AdvanceReports\Block\Adminhtml\Sales\SalesDetailed;

//use Bluethink\AdvanceReports\Block\Adminhtml\Sales\Sales;
use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;

/**
 * Backend grid container block
 */
class SalesDetailed extends Container
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
        $this->_controller = 'adminhtml_sales_detailed';
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
        return $this->getUrl('*/*/sales_detailed', ['_current' => true]);
    }


    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout(): Container
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(SalesDetailedGrid::class, 'sales.detailed')
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
