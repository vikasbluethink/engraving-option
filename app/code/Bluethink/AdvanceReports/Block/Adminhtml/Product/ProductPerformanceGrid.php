<?php
/**
 * Copyright © Bluethink, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bluethink\AdvanceReports\Block\Adminhtml\Product;

use Bluethink\AdvanceReports\Block\Adminhtml\Sales\Grid\AbstractGrid;

class ProductPerformanceGrid extends AbstractGrid
{

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
        $this->setId('ProductPerformanceGrid');
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
        return 'Bluethink\AdvanceReports\Model\ResourceModel\Products\Collection';
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $filterData = $this->getData('filter_data');
//        print_r($filterData);
        $limit = $filterData->getData("limit", null);
        if (! $limit) {
            $limit = $this->_defaultLimit;
        }
        $report_field = $filterData->getData("report_field", null);
        $report_field = $report_field ? $report_field : "main_table.created_at";
        $this->setCulumnDate($report_field);
        $this->setDefaultSort("orders_count");
        $this->setDefaultDir("DESC");
        $currencyCode       = $this->getCurrentCurrencyCode(null);
        $storeIds           = $this->_getStoreIds();
//        echo $this->_columnDate;
        if ($filterData->hasData()) {
            $resourceCollection = $this->_objectManager->create($this->getResourceCollectionName())
                ->setOrderRate($currencyCode)
                ->getProductPerformanceCollection()
                ->setMainTableId("product_id")
//                ->setPeriodType($this->getPeriodType())
                ->setDateColumnFilter($this->_columnDate)
                ->addDateFromFilter($filterData->getData('from', null))
                ->addDateToFilter($filterData->getData('to', null))
                ->addProductNameFilter($filterData->getData('name', null))
                ->addProductSkuFilter($filterData->getData('sku', null))
                ->addStoreFilter($storeIds)
                ->setAggregatedColumns($this->_getAggregatedColumns());

//            $this->_addOrderStatusFilter($resourceCollection, $filterData);
//        $this->_addCustomFilter($resourceCollection, $filterData);
//            $resourceCollection->getSelect()
//                ->group('product_id');
//                ->order(new \Zend_Db_Expr($this->getColumnOrder() . " " . $this->getColumnDir()));
            $resourceCollection->applyCustomFilter();

            $resourceCollection->setPageSize((int)$this->getParam($this->getVarNameLimit(), $limit));
            $resourceCollection->setCurPage((int)$this->getParam($this->getVarNamePage(), $this->_defaultPage));

            $order_filter = $this->getParam($this->getVarNameFilter(), null);

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

        $this->addColumn('name', [
            'header'          => __('Product Name'),
            'index'           => 'name',
            'width'           => 100,
            'totals_label'    => __('Total'),
            'html_decorators' => [ 'nobr' ],
            'filter'          => false,
        ]);

        $this->addColumn('sku', [
            'header' => __('SKU'),
            'index'  => 'sku',
            'total'  => 'sum',
            'filter' => false,
        ]);

        $this->addColumn('total_qty_ordered', [
            'header' => __('Items Sold'),
            'index'  => 'total_qty_ordered',
            'type'   => 'number',
            'total'  => 'sum',
            'filter' => false,
        ]);

        if ($this->getFilterData()->getStoreIds()) {
            $this->setStoreIds(explode(',', $this->getFilterData()->getStoreIds()));
        }

        $currencyCodeParam = $filterData->getData('currency_code') ?: null;
//        $currencyCodeParam = null;
        $currencyCode      = $this->getCurrentCurrencyCode($currencyCodeParam);
        $rate              = $this->getRate($currencyCode) ?: 1;

        $this->addColumn('total_subtotal_amount', [
            'header'        => __('Subtotal(Incl. Tax)'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'total_subtotal_amount',
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

        $this->addColumn('total_shipping_amount', [
            'header'        => __('Shipping'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'total_shipping_amount',
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

        $this->addColumn('total_grandtotal_amount', [
            'header'        => __('Total'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'total_grandtotal_amount',
            'total'         => 'sum',
            'rate'          => $rate,
            'filter'        => false,
        ]);

        $this->addColumn('total_qty_invoiced', [
            'header'            => __('Invoiced'),
//            'align'             => 'right',
            'filter'            => false,
            'index'             => 'total_qty_invoiced',
            'type'              => 'number',
            'total'             => 'sum',
//            'visibility_filter' => ['show_actual_columns'],
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

        $this->addColumn('total_revenue_amount', [
            'header'        => __('Revenue'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'total_revenue_amount',
            'total'         => 'sum',
            'rate'          => $rate,
        ]);

//        $this->addColumn('total_cost', [
//            'header'        => __('Total Cost'),
//            'type'          => 'currency',
//            'currency_code' => $currencyCode,
//            'index'         => 'total_cost',
//            'total'         => 'sum',
//            'rate'          => $rate,
//            'filter'        => false,
//        ]);
//
//        $this->addColumn('total_profit', [
//            'header'        => __('Total Profit'),
//            'type'          => 'currency',
//            'currency_code' => $currencyCode,
//            'index'         => 'total_profit',
//            'total'         => 'sum',
//            'rate'          => $rate,
//            'filter'        => false,
//        ]);
//
//        $this->addColumn('total_margin', [
//            'header'        => __('Total Margin'),
//            'type'          => 'currency',
//            'currency_code' => $currencyCode,
//            'index'         => 'total_margin',
//            'total'         => 'sum',
//            'rate'          => $rate,
//            'filter'        => false,
//        ]);

        $this->addExportType('*/*/exportProductPerformanceCsv', __('CSV'));
        $this->addExportType('*/*/exportProductPerformanceExcel', __('Excel XML'));
        return parent::_prepareColumns();
    }
}
