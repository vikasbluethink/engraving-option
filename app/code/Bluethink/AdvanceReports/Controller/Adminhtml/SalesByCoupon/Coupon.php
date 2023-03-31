<?php
/**
 * Copyright © Bluethink  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bluethink\AdvanceReports\Controller\Adminhtml\SalesByCoupon;

use Bluethink\AdvanceReports\Controller\Adminhtml\Report\Sales as ReportSales;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Reports\Model\Flag;

class Coupon extends ReportSales implements HttpGetActionInterface
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
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Sales By Coupon Code'));

        $gridBlock = $this->_view->getLayout()->getBlock('sales.by.coupon');
        $filterFormBlock = $this->_view->getLayout()->getBlock('sales.detailed.grid.filter.form');

        $this->_initReportAction([$gridBlock, $filterFormBlock]);

        $this->_view->renderLayout();
    }

    /**
     * Report action init operations
     *
     * @param array|Varien_Object $blocks
     * @return Mage_Adminhtml_Controller_Report_Abstract
     */
    public function _initReportAction($blocks, $report_type = "")
    {
        if (!is_array($blocks)) {
            $blocks = [$blocks];
        }
        $sort_by = $this->getRequest()->getParam('sort');
        $dir = $this->getRequest()->getParam('dir');

        $requestData = $this->_objectManager->get(
            'Magento\Backend\Helper\Data'
        )->prepareFilterString(
            $this->getRequest()->getParam('filter') ?? ''
        );
        $requestData['store_ids'] = $this->getRequest()->getParam('store_ids');

        $params = new \Magento\Framework\DataObject();
        foreach ($requestData as $key => $value) {
            if (!empty($value)) {
                $params->setData($key, $value);
            }
        }

        $period_type = $this->_getPeriodType($requestData, $report_type);

        foreach ($blocks as $block) {
            if ($block) {
                $block->setReportType($report_type);
                $block->setPeriodType($period_type);
                $block->setFilterData($params);
                $block->setCulumnOrder($sort_by);
                $block->setOrderDir($dir);
            }
        }

        return $this;
    }

//    public function decodeFilter(&$value)
//    {
//        $value = trim(rawurldecode($value));
//        echo "decodeFilter";
//        print_r($value);
//    }

    protected function _getPeriodType($requestData = [])
    {
        return (isset($requestData['period_type']) && $requestData['period_type']) ? $requestData['period_type'] : "";
    }
}

