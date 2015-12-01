<?php

 /**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_dimensional
 * User         Joshua Stewart
 * Date         25/06/2014
 * Time         14:48
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */

class Webshopapps_Shipusa_Model_Shipping_Carrier_Usps_Source_Freemethod extends Webshopapps_Shipusa_Model_Shipping_Carrier_Usps_Source_Method
{
    /**
     * DIMSHIP-139 Added in for Magento 1.8+ support.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arr = parent::toOptionArray();
        array_unshift($arr, array('value'=>'', 'label'=>Mage::helper('shipping')->__('None')));
        return $arr;
    }
}