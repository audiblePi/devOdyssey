<?php
/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @copyright  Copyright (c) 2012 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Advanced Product Options extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @author     MageWorx Dev Team
 */

class MageWorx_Adminhtml_Block_Customoptions_Options_Edit_Tab_General extends Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm() {
        parent::_prepareForm();
        $form = new Varien_Data_Form();

        $form->addField('title', 'text', array(
            'label' => Mage::helper('customoptions')->__('Title'),
            'name' => 'general[title]',
            'index' => 'title',
            'required' => true
        ));

        $form->addField('is_active', 'select', array(
            'label' => Mage::helper('customoptions')->__('Status'),
            'name' => 'general[is_active]',
            'index' => 'is_active',
            'values' => Mage::helper('customoptions')->getOptionStatusArray()
        ));

        $session = Mage::getSingleton('adminhtml/session');
        if ($data = $session->getData('customoptions_data')) {
            $form->setValues($data['general']);
        } elseif (Mage::registry('customoptions_data')) {
            $form->setValues(Mage::registry('customoptions_data')->getData());
        }
        $this->setForm($form);

        return $this;
    }

}