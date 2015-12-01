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

class MageWorx_CustomOptions_Model_Mysql4_Group_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct() {
        $this->_init('customoptions/group');
    }

    public function addStatusFilter() {
	$this->getSelect()->where('main_table.is_active = ?', MageWorx_CustomOptions_Helper_Data::STATUS_VISIBLE);
        return $this;
    }
    
    public function addSortOrder() {
        $this->getSelect()->order('title');
        return $this;
    }
    
    public function addProductsCount() {
        $this->getSelect()->joinLeft(array('relation' => $this->getTable('customoptions/relation')), 'relation.group_id = main_table.group_id', array('products'=>'COUNT(DISTINCT relation.product_id)'))
            ->group('main_table.group_id');
        return $this;
    }
    
    public function setShellRequest() {              
        if ($this->getSelect()!==null) {            
            $sql = $this->getSelect()->assemble();
            $this->getSelect()->reset()->from(array('main_table' => new Zend_Db_Expr('('.$sql.')')), '*');
            //echo $this->getSelect()->assemble(); exit;
        }                        
        return $this;
    }

//    public function addStoreFilter($store, $withAdmin = true)
//    {
//        if ($store instanceof Mage_Core_Model_Store) {
//            $store = array($store->getId());
//        }
//        //$this->getSelect()->where('main_table.store_id IN (?)', ($withAdmin ? array(0, $store) : $store));
//        return $this;
//    }
}