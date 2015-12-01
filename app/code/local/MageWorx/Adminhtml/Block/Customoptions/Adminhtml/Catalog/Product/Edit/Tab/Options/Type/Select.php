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
 * @copyright  Copyright (c) 2014 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Advanced Product Options extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @author     MageWorx Dev Team
 */

class MageWorx_Adminhtml_Block_Customoptions_Adminhtml_Catalog_Product_Edit_Tab_Options_Type_Select extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Options_Type_Select 
{

    public function __construct() {
        parent::__construct();
        if (!Mage::helper('customoptions')->isEnabled()) return $this;
        $this->setTemplate('customoptions/catalog-product-edit-options-type-select.phtml');
    }
    public function getDateFormat() {
        return Mage::app()->getLocale()->getDateStrFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    }
    protected function _prepareLayout() {
        
        $helper = Mage::helper('customoptions');
        
        $this->setChild('add_select_row_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label' => Mage::helper('catalog')->__('Add New Row'),
                        'class' => 'add add-select-row',
                        'id' => 'add_select_row_button_{{option_id}}',
                    )));

        $this->setChild('delete_select_row_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label' => Mage::helper('catalog')->__('Delete Row'),
                        'class' => 'delete delete-select-row icon-btn'                            
                    )));

        $this->setChild('add_image_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label' => '{{image_button_label}}',
                        'class' => 'add',                            
                        //'id' => 'new-option-file-{{option_id}}-{{select_id}}',
                        'onclick' => 'selectOptionType.addFileRow({{option_id}}, {{select_id}})'                            
                    )));
        
        
        $this->setChild('add_special_price_row_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'class' => 'add narrow-add-button',
                        'label' => 'Add',                            
                        'title' => $helper->__('Add Special Price'),
                        'id' => 'product_option_{{id}}_select_{{select_id}}_price_new',
                        //'style' => 'width:23px;',
                        'onclick' => 'selectOptionType.addSpecialPriceRow({{option_id}}, {{select_id}}, -1, 32000,\'\',\'fixed\',\'\')'
                    )));
        
        $this->setChild('add_tier_price_row_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'class' => 'add narrow-add-button',
                        'label' => 'Add',                            
                        'title' => $helper->__('Add Tier Price'),
                        'id' => 'product_option_{{id}}_select_{{select_id}}_price_new',
                        'onclick' => 'selectOptionType.addTierPriceRow({{option_id}}, {{select_id}}, -1, \'\', 32000,\'\',\'fixed\')'
                    )));
        
        $this->setChild('option_price_customer_group', 
                $this->getLayout()->createBlock('adminhtml/html_select')
                ->setData(array('class' => 'select product-option-price-customer-group'))
                ->setOptions($helper->getCustomerGroups())
            );
        
        parent::_prepareLayout();
        $this->getChild('option_price_type')->setOptions(Mage::getSingleton('adminhtml/system_config_source_product_options_price')->toOptionArray());
        
        return $this;
    }

    public function getAddButtonHtml() {
        return $this->getChildHtml('add_select_row_button');
    }

    public function getDeleteButtonHtml() {
        return $this->getChildHtml('delete_select_row_button');
    }

    public function getAddImageButtonHtml() {
        return $this->getChildHtml('add_image_button');
    }

    public function getAddTierPriceButtonHtml() {
        return $this->getChildHtml('add_tier_price_row_button');
    }
    
    public function getAddSpecialPriceButtonHtml() {
        return $this->getChildHtml('add_special_price_row_button');
    }
    
    public function getPriceTypeSelectHtml() {
        $this->getChild('option_price_type')
                ->setData('id', 'product_option_{{id}}_select_{{select_id}}_price_type')
                ->setName('product[options][{{id}}][values][{{select_id}}][price_type]');
        return $this->getChildHtml('option_price_type');
    }
    
    
    public function getSpecialCustomerGroupsSelectHtml() {
        return $this->getChild('option_price_customer_group')
                ->setId('product_option_{{id}}_select_{{select_id}}_price_special_{{special_price_id}}_customer_group')
                ->setName('product[options][{{id}}][values][{{select_id}}][specials][{{special_price_id}}][customer_group_id]')
                ->toHtml();
    }
    public function getSpecialPriceTypeSelectHtml() {
        return $this->getChild('option_price_type')
                ->setId('product_option_{{id}}_select_{{select_id}}_price_special_{{special_price_id}}_price_type')
                ->setName('product[options][{{id}}][values][{{select_id}}][specials][{{special_price_id}}][price_type]')
                ->toHtml();
    }
    
    
    public function getTierCustomerGroupsSelectHtml() {
        return $this->getChild('option_price_customer_group')
                ->setId('product_option_{{id}}_select_{{select_id}}_price_tier_{{tier_price_id}}_customer_group')
                ->setName('product[options][{{id}}][values][{{select_id}}][tiers][{{tier_price_id}}][customer_group_id]')
                ->toHtml();
    }
    public function getTierPriceTypeSelectHtml() {
        return $this->getChild('option_price_type')
                ->setId('product_option_{{id}}_select_{{select_id}}_price_tier_{{tier_price_id}}_price_type')
                ->setName('product[options][{{id}}][values][{{select_id}}][tiers][{{tier_price_id}}][price_type]')
                ->toHtml();
    }
}