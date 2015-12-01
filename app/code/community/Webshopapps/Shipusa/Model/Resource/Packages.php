<?php

/**
 * @category   Webshopapps
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */
class Webshopapps_Shipusa_Model_Resource_Packages extends Mage_Core_Model_Mysql4_Abstract
{
	
 /**
     * Resource initialization
     *
     */
    protected function _construct()
    {
        $this->_init('shipusa/packages', 'package_id');
    }
 
	
}