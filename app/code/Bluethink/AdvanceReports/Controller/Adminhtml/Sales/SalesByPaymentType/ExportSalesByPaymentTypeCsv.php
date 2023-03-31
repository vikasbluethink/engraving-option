<?php

namespace Bluethink\AdvanceReports\Controller\Adminhtml\Sales\SalesByPaymentType;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportSalesByPaymentTypeCsv extends Index
{
    /**
     * Export report grid to CSV format
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $fileName = 'SalesByPaymentTypeCsv.csv';
        $grid = $this->_view->getLayout()->createBlock('Bluethink\AdvanceReports\Block\Adminhtml\Sales\SalesByPaymentType\Grid');
        $this->_initReportAction($grid);
        return $this->_fileFactory->create($fileName, $grid->getCsvFile(), DirectoryList::VAR_DIR);
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bluethink_AdvanceReports::SalesByPaymentTypeCsv');
    }
}
