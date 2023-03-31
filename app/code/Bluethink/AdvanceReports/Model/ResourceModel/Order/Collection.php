<?php

namespace Bluethink\AdvanceReports\Model\ResourceModel\Order;

use Bluethink\AdvanceReports\Model\ResourceModel\AbstractReport\OrderCollection;

class Collection extends OrderCollection
{

    /**
     * @param string $orderRate
     * @return \Bluethink\AdvanceReports\Model\ResourceModel\Sales\Collection
     */
    public function setOrderRate($orderRate = "")
    {
        $this->_order_rate = $orderRate;

        return $this;
    }

    public function setDateColumnFilter($column_name = '') {
        if($column_name) {
            $this->_date_column_filter = $column_name;
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateColumnFilter()
    {
        return $this->_date_column_filter;
    }

    public function addDateFromFilter($from = null)
    {
        $this->_from_date_filter = $from;
        return $this;
    }

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
            $this->getSelect()->columns($select_datefield);
        }

        // sql theo filter date
        if ($this->_to_date_filter && $this->_from_date_filter) {

            // kiem tra lai doan convert ngay thang nay !

            $dateStart = $this->_localeDate->convertConfigTimeToUtc($this->_from_date_filter, 'Y-m-d 00:00:00');
            $endStart = $this->_localeDate->convertConfigTimeToUtc($this->_to_date_filter, 'Y-m-d 23:59:59');
            $dateRange = ['from' => $dateStart, 'to' => $endStart , 'datetime' => true];

            $this->addFieldToFilter($this->getDateColumnFilter(), $dateRange);
        }

        return $this;
    }

    public function applyCustomFilter()
    {
        $this->_applyDateFilter();
        $this->_applyStoresFilter();
        $this->_applyOrderStatusFilter();
        return $this;
    }


    public function getOrderDetailed()
    {
        $hide_fields = ["avg_item_cost", "avg_order_amount"];
        $this->setMainTableId('main_table.entity_id');
        $this->_aggregateByField('main_table.entity_id', $hide_fields);
        return $this;
    }

    /**
     * @param string $aggregationField
     * @param array  $hide_fields
     * @param array  $show_fields
     * @return $this
     */
    protected function _aggregateByField($aggregationField = "", $hide_fields = [], $show_fields = [])
    {
        $adapter = $this->getResource()->getConnection();
        // $adapter->beginTransaction();
        try {
            $subSelect = null;
            // Columns list
            $columns = [
                'rate'                         => 'currency_rate.rate',
                'order_ids'                    => 'GROUP_CONCAT(DISTINCT main_table.entity_id SEPARATOR \',\')',
                'customer_firstname'             => new \Zend_Db_Expr('IFNULL(main_table.customer_firstname, "Guest")'),
                'customer_lastname'              => new \Zend_Db_Expr('IFNULL(main_table.customer_lastname, "Guest")'),
                'store_id'                       => 'main_table.store_id',
                'order_status'                   => 'main_table.status',
                'product_type'                   => 'oi.product_type',
                'name'                           => 'oi.name',
                'sku'                            => 'oi.sku',
                'price'                          => 'oi.price',
                'total_cost_amount'              => new \Zend_Db_Expr('IFNULL(SUM(oi.total_cost_amount / currency_rate.rate),0)'),
                'orders_count'                   => new \Zend_Db_Expr('COUNT(main_table.entity_id)'),
                'total_qty_ordered'              => new \Zend_Db_Expr('SUM(oi.total_qty_ordered)'),
                'total_qty_shipping'             => new \Zend_Db_Expr('SUM(oi.total_qty_shipping)'),
                'total_qty_refunded'             => new \Zend_Db_Expr('SUM(oi.total_qty_refunded)'),
                'total_subtotal_amount'          => new \Zend_Db_Expr('SUM(main_table.subtotal / currency_rate.rate)'),
                'total_qty_invoiced'             => new \Zend_Db_Expr('SUM(oi.total_qty_invoiced)'),
                'total_grandtotal_amount'        => new \Zend_Db_Expr('SUM(main_table.grand_total)'),
                'avg_item_cost'                  => new \Zend_Db_Expr('AVG(oi.total_item_cost)'),
                'avg_order_amount'               => new \Zend_Db_Expr(
                    sprintf(
                        'AVG((%s - %s - %s - (%s - %s - %s)) / currency_rate.rate)',
                        $adapter->getIfNullSql('main_table.total_invoiced', 0),
                        $adapter->getIfNullSql('main_table.tax_invoiced', 0),
                        $adapter->getIfNullSql('main_table.shipping_invoiced', 0),
                        $adapter->getIfNullSql('main_table.total_refunded', 0),
                        $adapter->getIfNullSql('main_table.tax_refunded', 0),
                        $adapter->getIfNullSql('main_table.shipping_refunded', 0)
                    )
                ),
                'total_invoiced_amount'          => new \Zend_Db_Expr(
                    sprintf(
                        'SUM(%s / currency_rate.rate)',
                        $adapter->getIfNullSql('main_table.total_invoiced', 0)
                    )
                ),
                'total_canceled_amount'          => new \Zend_Db_Expr(
                    sprintf(
                        'SUM(%s / currency_rate.rate)',
                        $adapter->getIfNullSql('main_table.total_canceled', 0)
                    )
                ),
                'total_paid_amount'              => new \Zend_Db_Expr(
                    sprintf(
                        'SUM(%s / currency_rate.rate)',
                        $adapter->getIfNullSql('main_table.total_paid', 0)
                    )
                ),
                'total_refunded_amount'          => new \Zend_Db_Expr(
                    sprintf(
                        'SUM(%s / currency_rate.rate)',
                        $adapter->getIfNullSql('main_table.total_refunded', 0)
                    )
                ),
                'total_tax_amount'               => new \Zend_Db_Expr(
                    sprintf(
                        'SUM((%s - %s) / currency_rate.rate)',
                        $adapter->getIfNullSql('main_table.tax_amount', 0),
                        $adapter->getIfNullSql('main_table.tax_canceled', 0)
                    )
                ),
                'total_tax_amount_actual'        => new \Zend_Db_Expr(
                    sprintf(
                        'SUM((%s -%s) / currency_rate.rate)',
                        $adapter->getIfNullSql('main_table.tax_invoiced', 0),
                        $adapter->getIfNullSql('main_table.tax_refunded', 0)
                    )
                ),
                'total_shipping_amount'          => new \Zend_Db_Expr(
                    sprintf(
                        'SUM((%s - %s) / currency_rate.rate)',
                        $adapter->getIfNullSql('main_table.shipping_amount', 0),
                        $adapter->getIfNullSql('main_table.shipping_canceled', 0)
                    )
                ),
                'total_shipping_amount_actual'   => new \Zend_Db_Expr(
                    sprintf(
                        'SUM((%s - %s) / currency_rate.rate)',
                        $adapter->getIfNullSql('main_table.shipping_invoiced', 0),
                        $adapter->getIfNullSql('main_table.shipping_refunded', 0)
                    )
                ),
                'total_discount_amount'          => new \Zend_Db_Expr(
                    sprintf(
                        'SUM((ABS(%s) - %s) / currency_rate.rate)',
                        $adapter->getIfNullSql('main_table.discount_amount', 0),
                        $adapter->getIfNullSql('main_table.discount_canceled', 0)
                    )
                ),
                'total_discount_amount_actual'   => new \Zend_Db_Expr(
                    sprintf(
                        'SUM((%s - %s) / currency_rate.rate)',
                        $adapter->getIfNullSql('main_table.discount_invoiced', 0),
                        $adapter->getIfNullSql('main_table.discount_refunded', 0)
                    )
                ),
            ];

            if ($hide_fields) {
                foreach ($hide_fields as $field) {
                    if (isset($columns[$field])) {
                        unset($columns[$field]);
                    }
                }
            }

            $selectOrderItem = $adapter->select();

            $cols1 = [
                'order_id'           => 'order_id',
                'total_parent_cost_amount'  => new \Zend_Db_Expr($adapter->getIfNullSql('SUM(base_cost)', 0)),
            ];
            $selectOrderItem1 = $adapter->select()->from($this->getTable('sales_order_item'), $cols1)->where('parent_item_id IS NOT NULL')->group('order_id');
            $qtyCanceledExpr = $adapter->getIfNullSql('qty_canceled', 0);
            $cols            = [
                'order_id'           => 'order_id',
                'product_id'         => 'product_id',
                'product_type'       => 'product_type',
                'name'               => 'name',
                'created_at'         => 'created_at',
                'sku'                => 'sku',
                'price'              => 'price',
                'total_child_cost_amount'  => new \Zend_Db_Expr('SUM(base_cost)'),
                'total_qty_ordered'  => new \Zend_Db_Expr("SUM(qty_ordered - {$qtyCanceledExpr})"),
                'total_qty_invoiced' => new \Zend_Db_Expr('SUM(qty_invoiced)'),
                'total_qty_shipping' => new \Zend_Db_Expr('SUM(qty_shipped)'),
                'total_qty_refunded' => new \Zend_Db_Expr('SUM(qty_refunded)'),
                'total_item_cost'    => new \Zend_Db_Expr('SUM(row_total)'),
                'total_parent_cost_amount' => 'sales_item2.total_parent_cost_amount',
                'total_cost_amount'            => new \Zend_Db_Expr(
                    sprintf(
                        ' (%s + %s) ',
                        $adapter->getIfNullSql('SUM(base_cost)', 0),
                        $adapter->getIfNullSql('sales_item2.total_parent_cost_amount', 0)
                    )
                )
            ];

            $selectOrderItem->from(['sales_item1' => $this->getTable('sales_order_item')], $cols)
                ->where('parent_item_id IS NULL')
                ->joinLeft(['sales_item2' => $selectOrderItem1], 'sales_item1.order_id = sales_item2.order_id', [])
                ->group('sales_item1.order_id', 'sales_item1.product_id', 'sales_item1.product_type', 'sales_item1.created_at', 'sales_item1.sku');

            $this->getSelect()->columns($columns)
                ->join(['oi' => $selectOrderItem], 'oi.order_id = main_table.entity_id', []);


            if ($aggregationField) {
                $this->getSelect()->group($aggregationField);
            }

            $this->join(
                ['currency_rate' => 'directory_currency_rate'],
                "main_table.order_currency_code=currency_rate.currency_to AND currency_rate.currency_from='{$this->_order_rate}'",
                ['rate']
            );
        } catch (Exception $e) {
            $adapter->rollBack();
            throw $e;
        }

        return $this;
    }
}
