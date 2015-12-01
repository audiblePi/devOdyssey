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

if ((string)Mage::getConfig()->getModuleConfig('Innoexts_AdvancedPricing')->active == 'true'){
    class MageWorx_CustomOptions_Model_Mysql4_Product_Indexer_Price_Default_Abstract extends Innoexts_AdvancedPricing_Model_Mysql4_Catalog_Product_Indexer_Price_Default {}
} elseif ((string)Mage::getConfig()->getModuleConfig('Innoexts_StorePricing')->active == 'true') { 
    class MageWorx_CustomOptions_Model_Mysql4_Product_Indexer_Price_Default_Abstract extends Innoexts_StorePricing_Model_Mysql4_Catalog_Product_Indexer_Price_Default {}
} else {
    class MageWorx_CustomOptions_Model_Mysql4_Product_Indexer_Price_Default_Abstract extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Indexer_Price_Default {}
}