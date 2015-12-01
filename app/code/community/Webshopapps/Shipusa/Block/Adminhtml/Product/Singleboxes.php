<?php

/*
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/
class Webshopapps_Shipusa_Block_Adminhtml_Product_Singleboxes
    extends Mage_Adminhtml_Block_Widget
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Initialize block
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setSku($this->getRequest()->getParam('sku'));
        $this->setTemplate('webshopapps_shipusa/product/singleboxes.phtml');
        $this->setId('webshopapps_singleboxes');
        $this->setUseAjax(true);
    }

    public function getSingleBoxes()
    {
        return Mage::getModel('shipusa/singleboxes')->getCollection()
            ->addProductFilter($this->getProduct()->getSku());
    }
    
 	public function getBoxSelectHtml()
    {
        $select = $this->getLayout()->createBlock('adminhtml/html_select')
            ->setData(array(
                'id' => 'shipusa_singleboxes_$ROW_box_id',
                'class' => 'select'
            ))
            ->setName('shipusa_singleboxes[$ROW][box_id]')
            ->setOptions(Mage::getSingleton('boxmenu/boxmenu')->getAllOptions());
        return $select->getHtml();
    }

    /**
     * Check block is readonly
     *
     * @return boolean
     */
    public function isReadonly()
    {
         return $this->_getProduct()->getCompositeReadonly();
    }

    /**
     * Retrieve currently edited product object
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function getProduct()
    {
        return Mage::registry('current_product');
    }

    /**
     * Escape JavaScript string
     *
     * @param string $string
     * @return string
     */
    public function escapeJs($string)
    {
        return addcslashes($string, "'\r\n\\");
    }

    /**
     * Retrieve Tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('shipusa')->__('Individual Shipping Boxes');
    }

    /**
     * Retrieve Tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('shipusa')->__('Individual Shipping Boxes');
    }

    /**
     * Can show tab flag
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Check is a hidden tab
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}
