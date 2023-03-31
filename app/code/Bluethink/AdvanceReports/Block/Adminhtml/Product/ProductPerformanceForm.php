<?php
/**
 * Copyright © Bluethink, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bluethink\AdvanceReports\Block\Adminhtml\Product;

use Magento\Backend\Block\Widget\Form\Generic;

class ProductPerformanceForm extends Generic
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_adminSession;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Backend\Model\Auth\Session $adminSession,
        array $data = []
    ) {
        $this->_adminSession = $adminSession;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare the form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $actionUrl = $this->getUrl('*/*/productPerformance');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'filter_form',
                    'action' => $actionUrl,
                    'method' => 'get'
                ]
            ]
        );

        $htmlIdPrefix = 'productperformance_';
        $form->setHtmlIdPrefix($htmlIdPrefix);
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Filter')]);

        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);

        $fieldset->addField('store_ids', 'hidden', ['name' => 'store_ids']);

//        $fieldset->addField(
//            'period_type',
//            'select',
//            [
//                'name' => 'period_type',
//                'options' => ['day' => __('Day'), 'month' => __('Month'), 'year' => __('Year')],
//                'label' => __('Period'),
//                'title' => __('Period')
//            ]
//        );

        $fieldset->addField(
            'from',
            'date',
            [
                'name' => 'from',
                'date_format' => $dateFormat,
                'label' => __('From'),
                'title' => __('From'),
                'required' => true,
                'css_class' => 'admin__field-small',
                'class' => 'admin__control-text'
            ]
        );

        $fieldset->addField(
            'to',
            'date',
            [
                'name' => 'to',
                'date_format' => $dateFormat,
                'label' => __('To'),
                'title' => __('To'),
                'required' => true,
                'css_class' => 'admin__field-small',
                'class' => 'admin__control-text'
            ]
        );

        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => __('Product Name'),
                'title' => __('Product Name'),
                'css_class' => 'admin__field-small',
                'class' => 'admin__control-text'
            ]
        );

        $fieldset->addField(
            'sku',
            'text',
            [
                'name' => 'sku',
                'label' => __('SKU'),
                'title' => __('SKU'),
                'css_class' => 'admin__field-small',
                'class' => 'admin__control-text'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Method will be called after prepareForm and can be used for field values initialization
     *
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _initFormValues()
    {
        $data = $this->getFilterData()->getData();
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value[0])) {
                $data[$key] = explode(',', $value[0]);
            }
        }
        $this->getForm()->addValues($data);
        return parent::_initFormValues();
    }


    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
