<?php
/**
 * Copyright © Bluethink  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bluethink\AdvanceReports\Controller\Adminhtml\Sales\SalesByPaymentType;

use Bluethink\AdvanceReports\Controller\Adminhtml\Report\Sales as ReportSales;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Reports\Model\Flag;

class Index extends ReportSales implements HttpGetActionInterface
{
    /**
     * report action
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();
//        $this->_showLastExecutionTime(Flag::REPORT_ORDER_FLAG_CODE, 'sales');

//        $this->_initAction()->_setActiveMenu(
//            'Bluethink_AdvanceReports::index_index'
//        )->_addBreadcrumb(
//            __('Sales Overview'),
//            __('Sales Overview')
//        );
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Sales By Payment Type'));

        $gridBlock = $this->_view->getLayout()->getBlock('sales.by.payment.type');
        $filterFormBlock = $this->_view->getLayout()->getBlock('sales.detailed.grid.filter.form');

        $this->_initReportAction([$gridBlock, $filterFormBlock]);

        $this->_view->renderLayout();
    }

}

