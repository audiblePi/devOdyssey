<?php
/*
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/
class Webshopapps_Boxmenu_Model_Mysql4_Boxmenu extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        // Note that the boxmenu_id refers to the key field in your database table.
        $this->_init('boxmenu/boxmenu', 'boxmenu_id');
    }



}