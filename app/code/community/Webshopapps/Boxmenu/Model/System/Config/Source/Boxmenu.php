<?php

/*
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/

class Webshopapps_Boxmenu_Model_System_Config_Source_Boxmenu
{
    public function toOptionArray()
    {
        $arr = Mage::getModel('boxmenu/boxmenu')->toOptionArray();
        array_unshift($arr, array('value'=>'', 'label'=>Mage::helper('shipping')->__('None')));
        return $arr;
    }
}
