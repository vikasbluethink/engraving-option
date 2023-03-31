<?php
/**
 * Copyright © Bluethink, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bluethink\AdvanceReports\Block\Adminhtml\Sales;

use Bluethink\AdvanceReports\Block\Adminhtml\Sales\Grid\AbstractGrid;
use Magento\Framework\App\ObjectManager;

//use Magento\Reports\Block\Adminhtml\Grid\AbstractGrid;

class Grid extends AbstractGrid
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
        $this->setId('salesGrid');
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
        $report_type = $this->getReportType();
        $limit       = $filterData->getData("limit", null);
        if (! $limit) {
            $limit = $this->_defaultLimit;
        }
        $report_field = $filterData->getData("report_field", null);
        $report_field = $report_field ? $report_field : "main_table.created_at";
        $this->setDefaultSort("period");
        $this->setDefaultDir("DESC");
        $order = $this->getColumnOrder();
        if ("month" == $this->getPeriodType()) {
            $order = "main_table.created_at";
        }
        $currencyCode       = $this->getCurrentCurrencyCode(null);
        $storeIds           = $this->_getStoreIds();
        if ($filterData->hasData()) {
            $resourceCollection = $this->_objectManager->create($this->getResourceCollectionName())
                                                        ->setOrderRate($currencyCode)
                                                        ->getSalesOverviewCollection()
                                                        ->setPeriodType($this->getPeriodType())
                                                        ->setDateColumnFilter($this->_columnDate)
                                                        ->addDateFromFilter($filterData->getData('from', null))
                                                        ->addDateToFilter($filterData->getData('to', null))
                                                        ->addStoreFilter($storeIds)
                                                        ->setAggregatedColumns($this->_getAggregatedColumns());

            $this->_addOrderStatusFilter($resourceCollection, $filterData);
//            $this->_addCustomFilter($resourceCollection, $filterData);
            $resourceCollection->getSelect()
                ->group('period')
                ->order(new \Zend_Db_Expr($order . " " . $this->getColumnDir()));
            $resourceCollection->applyCustomFilter();

            $resourceCollection->setPageSize((int) $this->getParam($this->getVarNameLimit(), $limit));
            $resourceCollection->setCurPage((int) $this->getParam($this->getVarNamePage(), $this->_defaultPage));

            if ($this->getCountSubTotals()) {
                $this->getSubTotals();
            }

//            if (! $this->getTotals()) {
//                $totalsCollection = $this->_objectManager->create($this->getResourceCollectionName())
//                    ->setOrderRate($currencyCode)
//                    ->getSalesOverviewCollection()
//                    ->setDateColumnFilter($this->_columnDate)
//                    ->setPeriodType($this->getPeriodType())
//                    ->addDateFromFilter($filterData->getData('from', null))
//                    ->addDateToFilter($filterData->getData('to', null))
//                    ->addStoreFilter($storeIds)
//                    ->setAggregatedColumns($this->_getAggregatedColumns())
//                    ->isTotals(true);
//
//                $this->_addOrderStatusFilter($totalsCollection, $filterData);
//                $this->_addCustomFilter($totalsCollection, $filterData);
//
//                $totalsCollection->getSelect()
//                    ->group('period')
//                    ->order(new \Zend_Db_Expr($order . " " . $this->getColumnDir()));
//
//                $totalsCollection->applyCustomFilter();
//
//                foreach ($totalsCollection as $item) {
//                    $this->setTotals($item);
//                    break;
//                }
//            }
            $this->setCollection($resourceCollection);

            if (! $this->_registry->registry('report_collection')) {
                $this->_registry->register('report_collection', $resourceCollection);
//                echo $this->_registry->registry('report_collection')->getSize();
            }
        }

        $this->_prepareTotals('orders_count,total_qty_ordered,total_qty_invoiced,total_income_amount,total_revenue_amount,total_profit_amount,total_invoiced_amount,total_paid_amount,total_refunded_amount,total_tax_amount,total_tax_amount_actual,total_shipping_amount,total_shipping_amount_actual,total_discount_amount,total_discount_amount_actual,total_canceled_amount,total_subtotal_amount,total_grandtotal_amount');
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $filterData = $this->getFilterData();

        $this->addColumn('period', [
            'header'          => __('Period'),
            'index'           => 'period',
            'width'           => 100,
            'filter_data'     => $this->getFilterData(),
            'period_type'     => $this->getPeriodType(),
            'renderer'        => \Bluethink\AdvanceReports\Block\Adminhtml\Grid\Column\Renderer\Date::class,
            'totals_label'    => __('Total'),
            'html_decorators' => [ 'nobr' ],
            'filter'          => false,
        ]);

        $this->addColumn('orders_count', [
            'header' => __('Number Of Orders'),
            'index'  => 'orders_count',
            'type'   => 'number',
            'total'  => 'sum',
            'filter' => false,
        ]);

        $this->addColumn('total_qty_ordered', [
            'header' => __('Items Ordered'),
            'index'  => 'total_qty_ordered',
            'type'   => 'number',
            'total'  => 'sum',
            'filter' => false,
        ]);

        if ($this->getFilterData()->getStoreIds()) {
            $this->setStoreIds(explode(',', $this->getFilterData()->getStoreIds()));
        }

        $currencyCodeParam = $filterData->getData('currency_code') ?: null;
        $currencyCode      = $this->getCurrentCurrencyCode($currencyCodeParam);
        $rate              = $this->getRate($currencyCode) ?: 1;

        $this->addColumn('total_subtotal_amount', [
            'header'        => __('Subtotal'),
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

        $this->addColumn('total_revenue_amount', [
            'header'        => __('Revenue'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'total_revenue_amount',
            'total'         => 'sum',
            'rate'          => $rate,
        ]);
        $this->addColumn('avg_order_amount', [
            'header'        => __('Avg. Order Amount'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'avg_order_amount',
            'total'         => 'sum',
            'rate'          => $rate,
            'filter'        => false,
        ]);

//        $this->addColumn('avg_item_cost', [
//            'header'        => __('Avg. Final Price'),
//            'type'          => 'currency',
//            'currency_code' => $currencyCode,
//            'index'         => 'avg_item_cost',
//            'total'         => 'sum',
//            'rate'          => $rate,
//            'filter'        => false,
//        ]);

        $this->addExportType('*/*/exportSalesOverviewCsv', __('CSV'));
        $this->addExportType('*/*/exportSalesOverviewExcel', __('Excel XML'));
        return parent::_prepareColumns();
    }
}
