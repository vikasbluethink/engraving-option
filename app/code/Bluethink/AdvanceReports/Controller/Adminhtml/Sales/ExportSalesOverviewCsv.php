<?php

namespace Bluethink\AdvanceReports\Controller\Adminhtml\Sales;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportSalesOverviewCsv extends \Bluethink\AdvanceReports\Controller\Adminhtml\Report\Sales
{
    /**
     * Export bestsellers report grid to CSV format
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $fileName = 'SalesOverview.csv';
        $grid = $this->_view->getLayout()->createBlock('Bluethink\AdvanceReports\Block\Adminhtml\Sales\Grid');
        $this->_initReportAction($grid);
        return $this->_fileFactory->create($fileName, $grid->getCsvFile(), DirectoryList::VAR_DIR);
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bluethink_AdvanceReports::salesoverview');
    }
}
