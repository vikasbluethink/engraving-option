<?php

namespace Bluethink\AdvanceReports\Block\Adminhtml\Order\Abandoned;

use Bluethink\AdvanceReports\Block\Adminhtml\Sales\Grid\AbstractGrid;

class Grid extends AbstractGrid
{
    protected $_columnDate = 'main_table.created_at';
    protected $_columnGroupBy = '';
    protected $_defaultSort = 'period';
    protected $_defaultDir = 'ASC';
    protected $_resource_grid_collection = null;
    protected $_scopeconfig;

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
        \Magento\Backend\Block\Template\Context                                 $context,
        \Magento\Backend\Helper\Data                                            $backendHelper,
        \Magento\Reports\Model\ResourceModel\Report\Collection\Factory          $resourceFactory,
        \Magento\Reports\Model\Grouped\CollectionFactory                        $collectionFactory,
        \Magento\Reports\Helper\Data                                            $reportsData,
        \Magento\Framework\ObjectManagerInterface                               $objectManager,
        \Magento\Framework\Registry                                             $registry,
        \Magento\Store\Model\System\Store                                       $systemStore,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
        \Magento\Catalog\Model\ProductFactory                                   $productFactory,
        \Magento\Framework\Module\Manager                                       $moduleManager,
        \Magento\Store\Model\WebsiteFactory                                     $websiteFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable            $productConfigurable,
        \Magento\Payment\Model\Method\Factory                                   $paymentMethodFactory,
        array                                                                   $data = []
    ) {
        $this->_objectManager = $objectManager;
        $this->_registry = $registry;
        $this->_resourceFactory = $resourceFactory;
        $this->_collectionFactory = $collectionFactory;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_reportsData = $reportsData;
        $this->_systemStore = $systemStore;
        $this->_setsFactory = $setsFactory;
        $this->_websiteFactory = $websiteFactory;
        $this->moduleManager = $moduleManager;
        $this->_productFactory = $productFactory;
        $this->_productConfigurable = $productConfigurable;
        $this->_paymentMethodFactory = $paymentMethodFactory;
        parent::__construct($context, $backendHelper, $resourceFactory, $collectionFactory, $reportsData, $objectManager, $registry, $systemStore, $setsFactory, $productFactory, $moduleManager, $websiteFactory, $productConfigurable, $paymentMethodFactory);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setPagerVisibility(true);
        $this->setId('order-abandoned-grid');
        $this->setUseAjax(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceCollectionName()
    {
        return 'Bluethink\AdvanceReports\Model\ResourceModel\Order\Abandoned\Collection';
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
        $this->setCulumnDate($report_field);
        $this->setDefaultSort("period");
        $this->setDefaultDir("ASC");

        $order = $this->getColumnOrder();
        if ("month" == $this->getPeriodType()) {
            $order = "main_table.created_at";
        }

        $storeIds           = $this->_getStoreIds();
        if ($filterData->hasData()) {
            $resourceCollection = $this->_objectManager->create($this->getResourceCollectionName())
                ->prepareCartCollection()
                ->setDateColumnFilter($this->_columnDate)
                ->setPeriodType($this->getPeriodType())
                ->addDateFromFilter($filterData->getData('from', null))
                ->addDateToFilter($filterData->getData('to', null))
                ->addStoreFilter($storeIds)
                ->setAggregatedColumns($this->_getAggregatedColumns());

//        $this->_addCustomFilter($resourceCollection, $filterData);

//                $resourceCollection->getSelect()
//                    ->order(new \Zend_Db_Expr($order . " " . $this->getColumnDir()));

            $resourceCollection->applyCustomFilter();

            $resourceCollection->setPageSize((int)$this->getParam($this->getVarNameLimit(), $limit));
            $resourceCollection->setCurPage((int)$this->getParam($this->getVarNamePage(), $this->_defaultPage));

            //Completed Carts Collection
            $resourceComletedCartCollection = $this->_objectManager->create($this->getResourceCollectionName())
                ->prepareCompletedCartCollection()
                ->setDateColumnFilter($this->_columnDate)
                ->setPeriodType($this->getPeriodType())
                ->addDateFromFilter($filterData->getData('from', null))
                ->addDateToFilter($filterData->getData('to', null))
                ->addStoreFilter($storeIds)
                ->setAggregatedColumns($this->_getAggregatedColumns());

            $resourceComletedCartCollection->applyCustomFilter();
            $completed_cart_select = $resourceComletedCartCollection->getSelect();

            //End Completed Carts Collection
            //Abandoned Carts Collection
            $resourceAbandonedCartCollection = $this->_objectManager->create($this->getResourceCollectionName())
                ->prepareAbandonedCartCollection()
                ->setDateColumnFilter($this->_columnDate)
                ->setPeriodType($this->getPeriodType())
                ->addDateFromFilter($filterData->getData('from', null))
                ->addDateToFilter($filterData->getData('to', null))
                ->addStoreFilter($storeIds)
                ->setAggregatedColumns($this->_getAggregatedColumns());
            $resourceAbandonedCartCollection->applyCustomFilter();
            $abandoned_cart_select = $resourceAbandonedCartCollection->getSelect();
            //echo $abandoned_cart_select;die();

            //End Abandoned Carts Collection
            $resourceCollection->joinCartCollection($completed_cart_select, 'cc', 'period', [ "total_completed_cart", "completed_cart_total_amount" ]);
            $resourceCollection->joinCartCollection($abandoned_cart_select, 'abc', 'period', [ "total_abandoned_cart", "abandoned_cart_total_amount" ]);

            $resourceCollection->setMainTableId($resourceCollection->getPeriodDateField());
            echo $resourceCollection->getSelect()->__toString();
            $this->setCollection($resourceCollection);

            //echo $resourceCollection->getSelect();die();
            if (! $this->_registry->registry('report_collection')) {
                $this->_registry->register('report_collection', $resourceCollection);
            }
        }
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $is_export  = isset($this->_isExport) ? $this->_isExport : 0;
        $filterData = $this->getFilterData();
        $this->addColumn('period', [
            'header'          => __('Period'),
            'index'           => 'period',
            'width'           => 100,
            'filter'          => false,
            'show_link'       => true,
            'is_export'       => $is_export,
//            'data_filter'     => [ 'date_range' => [ 'from', 'to' ], 'route' => '*/advancedreports_order/abandoneddetailed/' ],
            'filter_data'     => $this->getFilterData(),
            'period_type'     => $this->getPeriodType(),
//            'renderer'        => 'Bluethink\AdvanceReports\Block\Adminhtml\Grid\Column\Renderer\Dateperiod',
            'totals_label'    => __('Total'),
            'html_decorators' => [ 'nobr' ],
        ]);

        if ($this->getRequest()->getParam('website')) {
            $storeIds = $this->_storeManager->getWebsite($this->getRequest()->getParam('website'))->getStoreIds();
        } elseif ($this->getRequest()->getParam('group')) {
            $storeIds = $this->_storeManager->getGroup($this->getRequest()->getParam('group'))->getStoreIds();
        } elseif ($this->getRequest()->getParam('store')) {
            $storeIds = [ (int) $this->getRequest()->getParam('store') ];
        } else {
            $storeIds = [];
        }
        $this->setStoreIds($storeIds);
        $filterData = $this->getFilterData();

        $currencyCodeParam = $filterData->getData('currency_code') ?: null;
        $currencyCode      = $this->getCurrentCurrencyCode($currencyCodeParam);
        $rate              = $this->getRate($currencyCode) ?: 1;

        $this->addColumn('total_cart', [
            'header' => __('Total Carts'),
            'width'  => '80px',
            'type'   => 'number',
            'index'  => 'total_cart',
            'filter' => false,
            'total'  => 'sum',
        ]);

        $this->addColumn('total_completed_cart', [
            'header' => __('Completed Carts'),
            'width'  => '80px',
            'align'  => 'right',
            'index'  => 'total_completed_cart',
            'filter' => false,
            'type'   => 'number',
            'total'  => 'sum',
        ]);

        $this->addColumn('total_abandoned_cart', [
            'header' => __('Abandoned Carts'),
            'width'  => '80px',
            'align'  => 'right',
            'index'  => 'total_abandoned_cart',
            'filter' => false,
            'type'   => 'number',
            'total'  => 'sum',
        ]);

        $this->addColumn('abandoned_cart_total_amount', [
            'header'        => __('Abandoned Carts Total'),
            'width'         => '80px',
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'abandoned_cart_total_amount',
            'filter'        => false,
            'renderer'      => 'Magento\Reports\Block\Adminhtml\Grid\Column\Renderer\Currency',
            'rate'          => $rate,
            'total'         => 'sum',
        ]);

        $this->addColumn('abandoned_rate', [
            'header'   => __('Abandonment Rate'),
            'width'    => '80px',
            'index'    => 'abandoned_rate',
//            'renderer' => 'Bluethink\AdvanceReports\Block\Adminhtml\Grid\Column\Renderer\AbandonedRate',
            'filter'   => false,
        ]);

        $this->addExportType('*/*/exportAbandonedCsv', __('CSV'));
        $this->addExportType('*/*/exportAbandonedExcel', __('Excel XML'));

        return parent::_prepareColumns();
    }

}
