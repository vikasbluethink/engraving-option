<?php
namespace Bluethink\AdvanceReports\Block\Adminhtml\Sales\SalesByPaymentType;

use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Button\SplitButton;
//use Bluethink\AdvanceReports\Block\Adminhtml\Sales\SalesByPaymentType\Grid;

/**
 * Backend grid container block
 */
class SalesByPaymentType extends Container
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
        $this->_blockGroup = 'Bluethink_SalesByPaymentType_Reports';
        $this->_controller = 'adminhtml_advancedreport_salesByPaymentType_report';
        $this->_headerText = __('Report');
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
        return $this->getUrl('*/sales_salesbypaymenttype/index', ['_current' => true]);
    }


    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(Grid::class, 'sales.by.payment.type')
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
