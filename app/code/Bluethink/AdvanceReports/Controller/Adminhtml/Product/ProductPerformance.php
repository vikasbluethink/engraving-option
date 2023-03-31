<?php
/**
 * Copyright © Bluethink  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bluethink\AdvanceReports\Controller\Adminhtml\Product;

use Bluethink\AdvanceReports\Controller\Adminhtml\Report\Sales as ReportSales;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Reports\Model\Flag;

class ProductPerformance extends ReportSales implements HttpGetActionInterface
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
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Product Performance'));

        $gridBlock = $this->_view->getLayout()->getBlock('prdouct.performance');
        $filterFormBlock = $this->_view->getLayout()->getBlock('product.performance.grid.filter.form');

        $this->_initReportAction([$gridBlock, $filterFormBlock]);

        $this->_view->renderLayout();
    }

}

