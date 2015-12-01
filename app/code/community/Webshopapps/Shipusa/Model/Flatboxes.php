<?php

/*
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/

class Webshopapps_Shipusa_Model_Flatboxes extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('shipusa/flatboxes');
        $this->setIdFieldName('flatboxes_id');
    }
}