<?php
namespace Bluethink\AdvanceReports\Controller\Adminhtml\Sales;

use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Reports api controller
 *
 * phpcs:disable Magento2.Classes.AbstractApi
 * @api
 * @since 100.0.2
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
abstract class AbstractReport extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Reports::report';

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
     */
    protected $_dateFilter;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var BackendHelper
     */
    private $backendHelper;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter
     * @param TimezoneInterface $timezone
     * @param BackendHelper|null $backendHelperData
     */
    public function __construct(
        \Magento\Backend\App\Action\Context              $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Stdlib\DateTime\Filter\Date   $dateFilter,
        TimezoneInterface                                $timezone,
        BackendHelper                                    $backendHelperData = null
    )
    {
        parent::__construct($context);
        $this->_fileFactory = $fileFactory;
        $this->_dateFilter = $dateFilter;
        $this->timezone = $timezone;
        $this->backendHelper = $backendHelperData ?: $this->_objectManager->get(BackendHelper::class);
    }

    /**
     * Admin session model
     *
     * @var null|\Magento\Backend\Model\Auth\Session
     */
    protected $_adminSession = null;

    /**
     * Retrieve admin session model
     *
     * @return \Magento\Backend\Model\Auth\Session
     */
    protected function _getSession()
    {
        if ($this->_adminSession === null) {
            $this->_adminSession = $this->_objectManager->get(\Magento\Backend\Model\Auth\Session::class);
        }
        return $this->_adminSession;
    }

    /**
     * Add report breadcrumbs
     *
     * @return $this
     */
    public function _initAction()
    {
        // phpcs:ignore Magento2.Legacy.ObsoleteResponse
        $this->_view->loadLayout();
        // phpcs:ignore Magento2.Legacy.ObsoleteResponse
        $this->_addBreadcrumb(__('Reports'), __('Reports'));
        return $this;
    }

//    /**
//     * Report action init operations
//     *
//     * @param array|\Magento\Framework\DataObject $blocks
//     * @return $this
//     */
//    public function _initReportAction($blocks)
//    {
//        if (!is_array($blocks)) {
//            $blocks = [$blocks];
//        }
//
//        $params = $this->initFilterData();
//
//        foreach ($blocks as $block) {
//            if ($block) {
//                $block->setPeriodType($params->getData('period_type'));
//                $block->setFilterData($params);
//            }
//        }
//
//        return $this;
//    }


    /**
     * Report action init operations
     *
     * @param array|\Magento\Framework\DataObject $blocks
     * @return $this
     */
    public function _initReportAction($blocks, $report_type = "")
    {
        if (!is_array($blocks)) {
            $blocks = [$blocks];
        }
        $sort_by = $this->getRequest()->getParam('sort');
        $dir = $this->getRequest()->getParam('dir');

        $requestData = $this->_objectManager->get(
            'Magento\Backend\Helper\Data'
        )->prepareFilterString(
            $this->getRequest()->getParam('filter') ?? ''
        );
        $requestData['store_ids'] = $this->getRequest()->getParam('store_ids');

        $params = new \Magento\Framework\DataObject();
        foreach ($requestData as $key => $value) {
            if (!empty($value)) {
                $params->setData($key, $value);
            }
        }

        $period_type = $this->_getPeriodType($requestData, $report_type);

        foreach ($blocks as $block) {
            if ($block) {
                $block->setReportType($report_type);
                $block->setPeriodType($period_type);
                $block->setFilterData($params);
                $block->setCulumnOrder($sort_by);
                $block->setOrderDir($dir);
            }
        }

        return $this;
    }

    protected function _getPeriodType($requestData = [])
    {
        return (isset($requestData['period_type']) && $requestData['period_type']) ? $requestData['period_type'] : "";
    }

    /**
     * Add refresh statistics links
     *
     * @param string $flagCode
     * @param string $refreshCode
     * @return $this
     */
    protected function _showLastExecutionTime($flagCode, $refreshCode)
    {
        $flag = $this->_objectManager->create(\Magento\Reports\Model\Flag::class)
            ->setReportFlagCode($flagCode)
            ->loadSelf();
        $updatedAt = __('Never');
        if ($flag->hasData()) {
            $updatedAt = $this->timezone->formatDate(
                $flag->getLastUpdate(),
                \IntlDateFormatter::MEDIUM,
                true
            );
        }

        $refreshStatsLink = $this->getUrl('reports/report_statistics');
        $directRefreshLink = $this->getUrl('reports/report_statistics/refreshRecent');

        $this->messageManager->addNotice(
            __(
                'Last updated: %1. To refresh last day\'s <a href="%2">statistics</a>, ' .
                'click <a href="#2" data-post="%3">here</a>.',
                $updatedAt,
                $refreshStatsLink,
                str_replace(
                    '"',
                    '&quot;',
                    json_encode(['action' => $directRefreshLink, 'data' => ['code' => $refreshCode]])
                )
            )
        );
        return $this;
    }

    /**
     * Init filter data
     *
     * @return \Magento\Framework\DataObject
     */
    private function initFilterData(): \Magento\Framework\DataObject
    {
        $requestData = $this->backendHelper->prepareFilterString(
            $this->getRequest()->getParam('filter', ''),
        );

        $filterRules = ['from' => $this->_dateFilter, 'to' => $this->_dateFilter];
        $inputFilter = new \Zend_Filter_Input($filterRules, [], $requestData);

        $requestData = $inputFilter->getUnescaped();
        $requestData['store_ids'] = $this->getRequest()->getParam('store_ids');
        $requestData['group'] = $this->getRequest()->getParam('group');
        $requestData['website'] = $this->getRequest()->getParam('website');

        $params = new \Magento\Framework\DataObject();

        foreach ($requestData as $key => $value) {
            if (!empty($value)) {
                $params->setData($key, $value);
            }
        }
        return $params;
    }
}

//
//use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
//
//abstract class AbstractReport extends \Magento\Backend\App\Action
//{
//    protected $_data = [];
//    /**
//     * @var \Magento\Framework\App\Response\Http\FileFactory
//     */
//    protected $_fileFactory;
//
//    /**
//     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
//     */
//    protected $_dateFilter;
//
//    /**
//     * @var TimezoneInterface
//     */
//    protected $timezone;
//    protected $_registry = null;
//
//    public function __construct(
//        \Magento\Backend\App\Action\Context $context,
//        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
//        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
//        \Magento\Framework\Registry $registry,
//        TimezoneInterface $timezone
//    ) {
//        parent::__construct($context);
//        $this->_fileFactory = $fileFactory;
//        $this->_dateFilter = $dateFilter;
//        $this->_registry = $registry;
//        $this->timezone = $timezone;
//    }
//
//    /**
//     * Admin session model
//     *
//     * @var null|\Magento\Backend\Model\Auth\Session
//     */
//    protected $_adminSession = null;
//
//    /**
//     * Retrieve admin session model
//     *
//     * @return \Magento\Backend\Model\Auth\Session
//     */
//    protected function _getSession()
//    {
//        if ($this->_adminSession === null) {
//            $this->_adminSession = $this->_objectManager->get('Magento\Backend\Model\Auth\Session');
//        }
//        return $this->_adminSession;
//    }
//
//    /**
//     * Add report breadcrumbs
//     *
//     * @return $this
//     */
//    public function _initAction()
//    {
//        $this->_view->loadLayout();
//        $this->_addBreadcrumb(__('Reports'), __('Reports'));
//        return $this;
//    }
//
//    /**
//     * Report action init operations
//     *
//     * @param array|\Magento\Framework\DataObject $blocks
//     * @return $this
//     */
//    public function _initReportAction($blocks)
//    {
//        if (!is_array($blocks)) {
//            $blocks = [$blocks];
//        }
//
//        $requestData = $this->_objectManager->get(
//            'Magento\Backend\Helper\Data'
//        )->prepareFilterString(
//            $this->getRequest()->getParam('filter')
//        );
//        $inputFilter = new \Zend_Filter_Input(
//            ['from' => $this->_dateFilter, 'to' => $this->_dateFilter],
//            [],
//            $requestData
//        );
//        $requestData = $inputFilter->getUnescaped();
//        $requestData['store_ids'] = $this->getRequest()->getParam('store_ids');
//        $params = new \Magento\Framework\DataObject();
//
//        foreach ($requestData as $key => $value) {
//            if (!empty($value)) {
//                $params->setData($key, $value);
//            }
//        }
//
//        foreach ($blocks as $block) {
//            if ($block) {
//                $block->setPeriodType($params->getData('period_type'));
//                $block->setIsExporting(1);
//                $block->setFilterData($params);
//            }
//        }
//
//        return $this;
//    }
//
//    /**
//     * Add refresh statistics links
//     *
//     * @param string $flagCode
//     * @param string $refreshCode
//     * @return $this
//     */
//    protected function _showLastExecutionTime($flagCode, $refreshCode)
//    {
//        $flag = $this->_objectManager->create('Magento\Reports\Model\Flag')->setReportFlagCode($flagCode)->loadSelf();
//        $updatedAt = 'undefined';
//        if ($flag->hasData()) {
//            $updatedAt =  $this->timezone->formatDate(
//                $flag->getLastUpdate(),
//                \IntlDateFormatter::MEDIUM,
//                true
//            );
//        }
//
//        $refreshStatsLink = $this->getUrl('reports/report_statistics');
//        $directRefreshLink = $this->getUrl('reports/report_statistics/refreshRecent', ['code' => $refreshCode]);
//
//        $this->messageManager->addNotice(
//            __(
//                'Last updated: %1. To refresh last day\'s <a href="%2">statistics</a>, ' .
//                'click <a href="%3">here</a>.',
//                $updatedAt,
//                $refreshStatsLink,
//                $directRefreshLink
//            )
//        );
//        return $this;
//    }
//
//    public function setData($var_name, $var_value = "")
//    {
//        $this->_data[$var_name] = $var_value;
//        return $this;
//    }
//    public function getData($var_name)
//    {
//        return isset($this->_data[$var_name]) ? $this->_data[$var_name] : null;
//    }
//}
