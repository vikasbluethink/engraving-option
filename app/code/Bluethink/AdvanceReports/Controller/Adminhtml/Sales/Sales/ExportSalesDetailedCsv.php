<?php

namespace Bluethink\AdvanceReports\Controller\Adminhtml\Sales\Sales;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;

class ExportSalesDetailedCsv extends Detailed
{
    /**
     * Export report grid to CSV format
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $fileName = 'SalesDetailed.csv';
        $grid = $this->_view->getLayout()->createBlock('Bluethink\AdvanceReports\Block\Adminhtml\Sales\SalesDetailed\SalesDetailedGrid');
        $this->_initReportAction($grid);
        return $this->_fileFactory->create($fileName, $grid->getCsvFile(), DirectoryList::VAR_DIR);
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bluethink_AdvanceReports::salesdetailed');
    }
}
