<?php

namespace Bluethink\AdvanceReports\Controller\Adminhtml\Sales;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;

class ExportSalesByCategoryExcel extends SalesByCategory
{
    /**
     * Export report grid to CSV format
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $fileName = 'SalesByCategory.xml';
        $grid = $this->_view->getLayout()->createBlock('Bluethink\AdvanceReports\Block\Adminhtml\Sales\SalesByCategory\SalesByCategoryGrid');
        $this->_initReportAction($grid);
        return $this->_fileFactory->create($fileName, $grid->getExcelFile(), DirectoryList::VAR_DIR);
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bluethink_AdvanceReports::salesbycategory');
    }
}
