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

class MageWorx_Adminhtml_Block_Customoptions_Adminhtml_Catalog_Product_Edit_Tab_Options extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Options {

    public function __construct() {
        parent::__construct();
        if (!Mage::helper('customoptions')->isEnabled()) return $this;
        $this->setTemplate('customoptions/catalog-product-edit-options.phtml');
    }

    protected function _prepareLayout() {
        $this->setChild('general_box', $this->getLayout()->createBlock('mageworx/customoptions_options_edit_tab_options_groups'));
        return parent::_prepareLayout();
    }
    
    public function getSkuPolicySelectHtml($value) {
        $select = $this->getLayout()->createBlock('adminhtml/html_select')
                ->setData(array('class' => 'select'))
                ->setName('general[sku_policy]')
                ->setOptions(Mage::getSingleton('customoptions/system_config_source_sku_policy')->toOptionArray(1))
                ->setValue($value);
        return $select->getHtml();
    }

    public function isPredefinedOptions() {
        return true;
    }

}