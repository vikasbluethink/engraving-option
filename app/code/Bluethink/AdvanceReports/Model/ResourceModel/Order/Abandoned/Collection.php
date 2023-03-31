<?php

namespace Bluethink\AdvanceReports\Model\ResourceModel\Order\Abandoned;

use Bluethink\AdvanceReports\Model\ResourceModel\AbstractReport\OrderCollection;

class Collection extends OrderCollection
{
    protected $_date_column_filter = "main_table.created_at";

    protected $_period_type = "";
    protected $_date_period_field = null;

    public function setPeriodDateField($period_field = [])
    {
        $this->_date_period_field = $period_field;
        return $this;
    }

    public function getPeriodDateField()
    {
        return isset($this->_date_period_field['period']) ? $this->_date_period_field['period'] : $this->_main_table_id;
    }
    /**
     * Apply stores filter to select object
     *
     * @param \Magento\Framework\DB\Select $select
     * @return Mage_Sales_Model_Resource_Report_Collection_Abstract
     */

    public function setDateColumnFilter($column_name = '')
    {
        if ($column_name) {
            $this->_date_column_filter = $column_name;
        }
        return $this;
    }

    public function getDateColumnFilter()
    {
        return $this->_date_column_filter;
    }
    /**
     * Set status filter
     *
     * @param string $orderStatus
     * @return Mage_Sales_Model_Resource_Report_Collection_Abstract
     */
    public function addDateFromFilter($from = null)
    {
        $this->_from_date_filter = $from;
        return $this;
    }

    /**
     * Set status filter
     *
     * @param string $orderStatus
     * @return Mage_Sales_Model_Resource_Report_Collection_Abstract
     */
    public function addDateToFilter($to = null)
    {
        $this->_to_date_filter = $to;
        return $this;
    }

    public function setPeriodType($period_type = "")
    {
        $this->_period_type = $period_type;
        return $this;
    }

    protected function _applyDateFilter()
    {
        $select_datefield = [];
        if ($this->_period_type) {
            switch ($this->_period_type) {
                case "year":
                    $select_datefield = [
                        'period'  => 'YEAR(' . $this->getDateColumnFilter() . ')'
                    ];
                    break;
                case "quarter":
                    $select_datefield = [
                        'period'  => 'CONCAT(QUARTER(' . $this->getDateColumnFilter() . '),"/",YEAR(' . $this->getDateColumnFilter() . '))'
                    ];
                    break;
                case "week":
                    $select_datefield = [
                        'period'  => 'CONCAT(YEAR(' . $this->getDateColumnFilter() . '),"", WEEK(' . $this->getDateColumnFilter() . '))'
                    ];
                    break;
                case "day":
                    $select_datefield = [
                        'period'  => 'DATE(' . $this->getDateColumnFilter() . ')'
                    ];
                    break;
                case "hour":
                    $select_datefield = [
                        'period'  => "DATE_FORMAT(" . $this->getDateColumnFilter() . ", '%H:00')"
                    ];
                    break;
                case "weekday":
                    $select_datefield = [
                        'period'  => 'WEEKDAY(' . $this->getDateColumnFilter() . ')'
                    ];
                    break;
                case "month":
                default:
                    $select_datefield = [
                        'period'  => 'CONCAT(MONTH(' . $this->getDateColumnFilter() . '),"/",YEAR(' . $this->getDateColumnFilter() . '))',
                        'period_sort'  => 'CONCAT(MONTH(' . $this->getDateColumnFilter() . '),"",YEAR(' . $this->getDateColumnFilter() . '))'
                    ];
                    break;
            }
        }
        if ($select_datefield) {
            $this->setPeriodDateField($select_datefield);
            $this->getSelect()->columns($select_datefield);
        }

        if ($this->_to_date_filter && $this->_from_date_filter) {

            $dateStart = $this->convertConfigTimeToUtc($this->_from_date_filter, 'Y-m-d 00:00:00');
            $endStart = $this->convertConfigTimeToUtc($this->_to_date_filter, 'Y-m-d 23:59:59');
            $dateRange = ['from' => $dateStart, 'to' => $endStart , 'datetime' => true];

            $this->addFieldToFilter($this->getDateColumnFilter(), $dateRange);
        }

        return $this;
    }

    /**
     * Add subtotals
     *
     * @param array $storeIds
     * @param array $filter
     * @return Mage_Reports_Model_Resource_Quote_Collection
     */
    public function addSubtotal($storeIds = '', $filter = null)
    {
        if (is_array($storeIds)) {
            $this->getSelect()->columns([
                'subtotal' => '(main_table.base_subtotal_with_discount*main_table.base_to_global_rate)'
            ]);
            $this->_joinedFields['subtotal'] =
                '(main_table.base_subtotal_with_discount*main_table.base_to_global_rate)';
        } else {
            $this->getSelect()->columns(['subtotal' => 'main_table.base_subtotal_with_discount']);
            $this->_joinedFields['subtotal'] = 'main_table.base_subtotal_with_discount';
        }

        if ($filter && is_array($filter) && isset($filter['subtotal'])) {
            if (isset($filter['subtotal']['from'])) {
                $this->getSelect()->where(
                    $this->_joinedFields['subtotal'] . ' >= ?',
                    $filter['subtotal']['from'],
                    \Zend_Db::FLOAT_TYPE
                );
            }
            if (isset($filter['subtotal']['to'])) {
                $this->getSelect()->where(
                    $this->_joinedFields['subtotal'] . ' <= ?',
                    $filter['subtotal']['to'],
                    \Zend_Db::FLOAT_TYPE
                );
            }
        }

        return $this;
    }

    /**
     * Add customer data
     *
     * @param array|null $filter
     * @return $this
     */
    public function addCustomerData($filter = null)
    {
        $customersSelect = $this->customerResource->getConnection()->select();
        $customersSelect->from(
            ['customer' => $this->customerResource->getTable('customer_entity')],
            'entity_id'
        );
        if (isset($filter['customer_name']) && $filter['customer_name']) {
            $customerName = $this->customerResource->getConnection()
                ->getConcatSql(['customer.firstname', 'customer.lastname'], ' ');
            $customersSelect->where($customerName . ' LIKE ?', '%' . $filter['customer_name'] . '%');
        }
        if (isset($filter['email']) && $filter['email']) {
            $customersSelect->where('customer.email LIKE ?', '%' . $filter['email'] . '%');
        }
        $filteredCustomers = $this->customerResource->getConnection()->fetchCol($customersSelect);
        $this->getSelect()->where('main_table.customer_id IN (?)', $filteredCustomers);
        return $this;
    }

//    public function prepareAbandonedCartDetailCollection($storeIds, $filter = null)
//    {
//        $hide_fields = [];
//        $this->setMainTable('quote');
//        $this->addFieldToFilter(
//            'items_count',
//            ['neq' => '0']
//        )->addFieldToFilter(
//            'main_table.is_active',
//            '1'
//        )->addFieldToFilter(
//            'main_table.customer_id',
//            ['neq' => null]
//        )->addSubtotal(
//            $storeIds,
//            $filter
//        )->setOrder(
//            'updated_at'
//        );
//
//        $this->addCustomerData($filter);
//
//        if (is_array($storeIds) && !empty($storeIds)) {
//            $this->addFieldToFilter('store_id', ['in' => $storeIds]);
//        }
//
//        return $this;
//    }

//    /**
//     * Resolve customers data based on ids quote table.
//     *
//     * @return void
//     */
//    public function resolveCustomerNames()
//    {
//        $select = $this->customerResource->getConnection()->select();
//        $customerName = $this->customerResource->getConnection()->getConcatSql(['firstname', 'lastname'], ' ');
//
//        $select->from(
//            ['customer' => $this->customerResource->getTable('customer_entity')],
//            ['entity_id', 'email']
//        );
//        $select->columns(
//            ['customer_name' => $customerName]
//        );
//        $select->where(
//            'customer.entity_id IN (?)',
//            array_column(
//                $this->getData(),
//                'customer_id'
//            )
//        );
//        $customersData = $this->customerResource->getConnection()->fetchAll($select);
//
//        foreach ($this->getItems() as $item) {
//            foreach ($customersData as $customerItemData) {
//                if ($item['customer_id'] == $customerItemData['entity_id']) {
//                    $item->setData(array_merge($item->getData(), $customerItemData));
//                }
//            }
//        }
//    }
    public function prepareCartCollection()
    {
        $hide_fields = [];
        $this->setMainTable('quote');
        $this->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $this->_aggregateCartByField("period", $hide_fields);
        $this->addFieldToFilter('items_count', ['neq' => '0'])
            ->addFieldToFilter("main_table.customer_id", ['notnull'=>null]);
//        echo $this->getSelect()->__toString();
        return $this;
    }

    public function joinCartCollection(\Magento\Framework\DB\Select $selectOrder, $alias, $group_by = 'period', $fields = [])
    {
        $this->getSelect()->joinLeft([$alias => $selectOrder], $alias . '.' . $group_by . ' = ' . $this->_date_period_field['period'], $fields);
        return $this;
    }

    public function prepareCompletedCartCollection()
    {
        $hide_fields = [];
        $this->setMainTable('quote');
        $this->setMainTableId("period");
        $this->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $this->_aggregateCompletedCartByField("period", $hide_fields);
        $this->addFieldToFilter("main_table.is_active", 0)
            ->addFieldToFilter("main_table.customer_id", ['notnull'=>null]);
        return $this;
    }

    public function prepareAbandonedCartCollection()
    {
        $hide_fields = [];
        $this->setMainTable('quote');
        $this->setMainTableId("period");
        $this->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $this->_aggregateAbandonedCartByField("period", $hide_fields);
        $this->addFieldToFilter('items_count', ['neq' => '0'])
            ->addFieldToFilter("main_table.is_active", 1)
            ->addFieldToFilter("main_table.customer_id", ['notnull'=>null]);
        return $this;
    }

    public function applyCustomFilter()
    {
        $this->_applyDateFilter();
        $this->_applyStoresFilter();
        $this->_applyOrderStatusFilter();
        return $this;
    }

    /**
     * Aggregate Orders data by custom field
     *
     * @throws Exception
     * @param string $aggregationField
     * @param mixed $from
     * @param mixed $to
     * @return Mage_Sales_Model_Resource_Report_Order_Createdat
     */
    protected function _aggregateCartByField($aggregationField = "", $hide_fields = [], $show_fields = [])
    {
        $adapter = $this->getResource()->getConnection();
        try {
            $subSelect = null;
            // Columns list
            $columns = [
                // convert dates from UTC to current admin timezone
                'total_cart'                       => new \Zend_Db_Expr('IFNULL(COUNT(main_table.entity_id),0)'),
                'cart_total_amount'                => new \Zend_Db_Expr('IFNULL(SUM(main_table.subtotal),0)')
            ];

            if ($hide_fields) {
                foreach ($hide_fields as $field) {
                    if (isset($columns[$field])) {
                        unset($columns[$field]);
                    }
                }
            }

            $this->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
            $this->getSelect()->columns($columns);

            if ($aggregationField) {
                $this->getSelect()->group($aggregationField);
            }
        } catch (Exception $e) {
            $adapter->rollBack();
            throw $e;
        }

        return $this;
    }

    /**
     * Aggregate Orders data by custom field
     *
     * @throws Exception
     * @param string $aggregationField
     * @param mixed $from
     * @param mixed $to
     * @return Mage_Sales_Model_Resource_Report_Order_Createdat
     */
    protected function _aggregateCompletedCartByField($aggregationField = "", $hide_fields = [], $show_fields = [])
    {
        $adapter = $this->getResource()->getConnection();

        try {
            $subSelect = null;
            // Columns list
            $columns = [
                // convert dates from UTC to current admin timezone
                'total_completed_cart'                       => new \Zend_Db_Expr('IFNULL(COUNT(main_table.entity_id),0)'),
                'completed_cart_total_amount'                => new \Zend_Db_Expr('IFNULL(SUM(main_table.subtotal),0)')
            ];

            if ($hide_fields) {
                foreach ($hide_fields as $field) {
                    if (isset($columns[$field])) {
                        unset($columns[$field]);
                    }
                }
            }

            $this->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
            $this->getSelect()->columns($columns);

            if ($aggregationField) {
                $this->getSelect()->group($aggregationField);
            }
        } catch (Exception $e) {
            $adapter->rollBack();
            throw $e;
        }

        return $this;
    }

    /**
     * Aggregate Orders data by custom field
     *
     * @throws Exception
     * @param string $aggregationField
     * @param mixed $from
     * @param mixed $to
     * @return Mage_Sales_Model_Resource_Report_Order_Createdat
     */
    protected function _aggregateAbandonedCartByField($aggregationField = "", $hide_fields = [], $show_fields = [])
    {
        $adapter = $this->getResource()->getConnection();

        try {
            $subSelect = null;
            // Columns list
            $columns = [
                // convert dates from UTC to current admin timezone
                'total_abandoned_cart'                       => new \Zend_Db_Expr('IFNULL(COUNT(main_table.entity_id),0)'),
                'abandoned_cart_total_amount'                => new \Zend_Db_Expr('IFNULL(SUM(main_table.subtotal),0)')
            ];

            if ($hide_fields) {
                foreach ($hide_fields as $field) {
                    if (isset($columns[$field])) {
                        unset($columns[$field]);
                    }
                }
            }

            $this->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
            $this->getSelect()->columns($columns);

            if ($aggregationField) {
                $this->getSelect()->group($aggregationField);
            }
        } catch (Exception $e) {
            $adapter->rollBack();
            throw $e;
        }

        return $this;
    }
}
