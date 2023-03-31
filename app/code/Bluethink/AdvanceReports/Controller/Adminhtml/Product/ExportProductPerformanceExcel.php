<?php

namespace Bluethink\AdvanceReports\Controller\Adminhtml\Product;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportProductPerformanceExcel extends \Bluethink\AdvanceReports\Controller\Adminhtml\Product\ProductPerformance
{
    /**
     * Export report grid to CSV format
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $fileName = 'ProductPerformance.xml';
        $grid = $this->_view->getLayout()->createBlock('Bluethink\AdvanceReports\Block\Adminhtml\Product\ProductPerformanceGrid');
        $this->_initReportAction($grid);
        return $this->_fileFactory->create($fileName, $grid->getExcelFile(), DirectoryList::VAR_DIR);

    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bluethink_AdvanceReports::productperformance');
    }
}
