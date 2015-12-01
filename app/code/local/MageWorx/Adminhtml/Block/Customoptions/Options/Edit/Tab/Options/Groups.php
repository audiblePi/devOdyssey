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

class MageWorx_Adminhtml_Block_Customoptions_Options_Edit_Tab_Options_Groups extends Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm() {
        if (Mage::helper('customoptions')->isEnabled()) {
            $product = Mage::registry('product');
            if ($product->getTypeId() == 'bundle' && $product->getPriceType() == 0) {
                return $this;
            }
            $values = Mage::getSingleton('customoptions/group')->getStoreValues($product->getStoreId());            

            $form = new Varien_Data_Form();
            $form->addField('customoptions_groups', 'multiselect', array(
                //'label' => Mage::helper('customoptions')->__('Options Templates'),
                'title' => Mage::helper('customoptions')->__('Options Templates'),
                'name' => 'customoptions[groups][]',
                'values' => $values,
                'value' => Mage::getResourceSingleton('customoptions/relation')->getGroupIds($product->getId()),
                'style' => 'width: 280px; height: 112px;',
            ));
            $this->setForm($form);
        }    

        return parent::_prepareForm();
    }

}