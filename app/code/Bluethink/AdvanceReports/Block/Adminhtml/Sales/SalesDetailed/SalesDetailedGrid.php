<?php
/**
 * Copyright © Bluethink, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bluethink\AdvanceReports\Block\Adminhtml\Sales\SalesDetailed;

use Bluethink\AdvanceReports\Block\Adminhtml\Sales\Grid\AbstractGrid;

//use Magento\Reports\Block\Adminhtml\Grid\AbstractGrid;

class SalesDetailedGrid extends AbstractGrid
{
    protected \Magento\Sales\Model\OrderFactory $orderFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    protected $_backendHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Reports\Model\ResourceModel\Report\Collection\Factory $resourceFactory
     * @param \Magento\Reports\Model\Grouped\CollectionFactory $collectionFactory
     * @param \Magento\Reports\Helper\Data $reportsData
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productConfigurable
     * @param \Magento\Payment\Model\Method\Factory $paymentMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Reports\Model\ResourceModel\Report\Collection\Factory $resourceFactory,
        \Magento\Reports\Model\Grouped\CollectionFactory $collectionFactory,
        \Magento\Reports\Helper\Data $reportsData,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        // \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productConfigurable,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        array $data = []
    ) {
        $this->_objectManager       = $objectManager;
        $this->_registry            = $registry;
        $this->_resourceFactory     = $resourceFactory;
        $this->_collectionFactory   = $collectionFactory;
        $this->_scopeConfig         = $context->getScopeConfig();
        $this->_reportsData         = $reportsData;
        $this->_systemStore         = $systemStore;
        $this->_setsFactory         = $setsFactory;
        $this->_websiteFactory      = $websiteFactory;
        $this->moduleManager        = $moduleManager;
        $this->_productFactory      = $productFactory;
        $this->_productConfigurable = $productConfigurable;
        $this->_paymentMethodFactory = $paymentMethodFactory;
        parent::__construct($context, $backendHelper, $resourceFactory, $collectionFactory, $reportsData, $objectManager, $registry, $systemStore, $setsFactory, $productFactory, $moduleManager, $websiteFactory, $productConfigurable, $paymentMethodFactory);
    }

    /**
     * Payment method factory
     *
     * @var \Magento\Payment\Model\Method\Factory
     */
    protected $_paymentMethodFactory;

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setPagerVisibility(true);
        $this->setId('salesdetailedGrid');
        $this->setUseAjax(false);
    }

    public function getSearchButtonHtml()
    {
        return '';
    }
    public function getResetFilterButtonHtml()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceCollectionName()
    {
        return 'Bluethink\AdvanceReports\Model\ResourceModel\Sales\Collection';
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $filterData = $this->getData('filter_data');
//        $report_type = $this->getReportType();
        $limit       = $filterData->getData("limit", null);
        if (! $limit) {
            $limit = $this->_defaultLimit;
        }
        $report_field = $filterData->getData("report_field", null);
        $report_field = $report_field ? $report_field : "main_table.created_at";
        $this->setCulumnDate($report_field);
        $currencyCode       = $this->getCurrentCurrencyCode(null);
        $storeIds           = $this->_getStoreIds();
        if ($filterData->hasData()) {
            $resourceCollection = $this->_objectManager->create('Bluethink\AdvanceReports\Model\ResourceModel\Order\Collection')
                ->setOrderRate($currencyCode)
                ->getOrderDetailed()
//                ->setPeriodType($filterData->getData('period_type', null))
                ->setDateColumnFilter($this->_columnDate)
                ->addDateFromFilter($filterData->getData('from', null))
                ->addDateToFilter($filterData->getData('to', null))
                ->addStoreFilter($storeIds);

//        echo "<pre>";
//        print_r($resourceCollection->getData());
//        $resourceCollection->join(['payment' => 'sales_order_payment'], 'main_table.entity_id=parent_id', 'method');

            $this->_addOrderStatusFilter($resourceCollection, $filterData);
//            $this->_addCustomFilter($resourceCollection, $filterData);

            $resourceCollection->getSelect()
            ->order(new \Zend_Db_Expr('increment_id DESC'));

            $resourceCollection->applyCustomFilter();

//        $resourceCollection->setPageSize((int) $this->getParam($this->getVarNameLimit(), $limit));
//        $resourceCollection->setCurPage((int) $this->getParam($this->getVarNamePage(), $this->_defaultPage));

//        $order_filter = $this->getParam($this->getVarNameFilter(), null);

//        print_r($resourceCollection->getData());
            $this->setCollection($resourceCollection);
        }
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $filterData = $this->getFilterData();

        $this->addColumn('increment_id', [
            'header'          => __('Order'),
            'index'           => 'increment_id',
            'width'           => 100,
            'totals_label'    => __('Total'),
            'html_decorators' => [ 'nobr' ],
            'filter'          => false,
        ]);

        $this->addColumn('status', [
            'header' => __('Order Status'),
            'index'  => 'status',
            'type'   => 'string',
            'total'  => 'sum',
            'filter' => false,
        ]);

        $this->addColumn('name', [
            'header' => __('Product Name'),
            'index'  => 'name',
            'type'   => 'string',
            'total'  => 'sum',
            'filter' => false,
        ]);

        if ($this->getFilterData()->getStoreIds()) {
            $this->setStoreIds(explode(',', $this->getFilterData()->getStoreIds()));
        }

        $currencyCodeParam = $filterData->getData('currency_code') ?: null;
        $currencyCode      = $this->getCurrentCurrencyCode($currencyCodeParam);
        $rate              = $this->getRate($currencyCode) ?: 1;

        $this->addColumn('sku', [
            'header' => __('SKU'),
            'index'  => 'sku',
            'total'  => 'sum',
            'filter' => false,
        ]);

        $this->addColumn('customer_email', [
            'header'        => __('Customer Email'),
            'currency_code' => $currencyCode,
            'index'         => 'customer_email',
            'total'         => 'sum',
            'rate'          => $rate,
            'filter'        => false,
        ]);

//        $this->addColumn('customer_name', [
//            'header'        => __('Custome Name'),
//            'currency_code' => $currencyCode,
//            'index'         => 'customer_name',
//            'rate'          => $rate,
//            'filter'        => false,
//        ]);

//        $this->addColumn('country', [
//            'header'        => __('Country'),
//            'currency_code' => $currencyCode,
//            'index'         => 'country',
//            'rate'          => $rate,
//            'filter'        => false,
//        ]);

        $this->addColumn('total_qty_invoiced', [
            'header'            => __('Qty. Invoiced'),
//            'align'             => 'right',
            'filter'            => false,
            'index'             => 'total_qty_invoiced',
            'type'              => 'number',
            'total'             => 'sum',
//            'visibility_filter' => ['show_actual_columns'],
        ]);

        $this->addColumn('price', [
            'header'        => __('Item Price'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'price',
            'total'         => 'sum',
            'rate'          => $rate,
            'filter'        => false,
        ]);

        $this->addColumn('total_subtotal_amount', [
            'header'        => __('Subtotal'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'total_subtotal_amount',
            'total'         => 'sum',
            'rate'          => $rate,
            'filter'        => false,
        ]);

        $this->addColumn('total_discount_amount', [
            'header'        => __('Discounts'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'total_discount_amount',
            'total'         => 'sum',
            'rate'          => $rate,
            'filter'        => false,
        ]);

        $this->addColumn('total_tax_amount', [
            'header'        => __('Tax'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'total_tax_amount',
            'total'         => 'sum',
            'rate'          => $rate,
            'filter'        => false,
        ]);

        $this->addColumn('total_grandtotal_amount', [
            'header'        => __('Total'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'total_grandtotal_amount',
            'total'         => 'sum',
            'rate'          => $rate,
            'filter'        => false,
        ]);

        $this->addColumn('total_invoiced_amount', [
            'header'        => __('Invoiced'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'total_invoiced_amount',
            'total'         => 'sum',
            'rate'          => $rate,
            'filter'        => false,
        ]);

        $this->addColumn('total_refunded_amount', [
            'header'        => __('Refunded'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'total_refunded_amount',
            'total'         => 'sum',
            'rate'          => $rate,
            'filter'        => false,
        ]);

        $this->addExportType('*/*/sales_exportSalesDetailedCsv', __('CSV'));
        $this->addExportType('*/*/sales_exportSalesDetailedExcel', __('Excel XML'));
        return parent::_prepareColumns();
    }
}
