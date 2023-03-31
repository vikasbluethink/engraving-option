<?php
namespace Bluethink\AdvanceReports\Block\Adminhtml\Order\Abandoned;

use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;

//use Bluethink\AdvanceReports\Block\Adminhtml\Sales\SalesByPaymentType\Grid;

/**
 * Backend grid container block
 */
class Index extends Container
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
        $this->_blockGroup = 'Bluethink_Order_Abandoned_Cart_Reports';
        $this->_controller = 'adminhtml_advancedreport_abandoned_cart_report';
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
        return $this->getUrl('*/order_abandoned/index', ['_current' => true]);
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(Grid::class, 'order.abandoned.cart')
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
