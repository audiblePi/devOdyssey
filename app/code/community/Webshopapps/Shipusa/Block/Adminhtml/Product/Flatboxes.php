<?php

/*
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/
class Webshopapps_Shipusa_Block_Adminhtml_Product_Flatboxes
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
        $this->setTemplate('webshopapps_shipusa/product/flatboxes.phtml');
        $this->setId('webshopapps_flatboxes');
        $this->setUseAjax(true);
    }

    public function getFlatBoxes()
    {
        return Mage::getModel('shipusa/flatboxes')->getCollection()
            ->addProductFilter($this->getProduct()->getSku());
    }

    public function getBoxSelectHtml()
    {
        $select = $this->getLayout()->createBlock('adminhtml/html_select')
            ->setData(array(
                'id' => 'shipusa_flatboxes_$ROW_box_id',
                'class' => 'select'
            ))
            ->setName('shipusa_flatboxes[$ROW][box_id]')
            ->setOptions(Mage::getModel('boxmenu/boxmenu')->getAllUSPSOptions());
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
        return Mage::helper('shipusa')->__('USPS Flat Rate Boxes');
    }

    /**
     * Retrieve Tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('shipusa')->__('USPS Flat Rate Boxes');
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
