<?php
/*
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/

class Webshopapps_Boxmenu_Block_Boxmenu extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }

     public function getBoxmenu()
     {
        if (!$this->hasData('boxmenu')) {
            $this->setData('boxmenu', Mage::registry('boxmenu'));
        }
        return $this->getData('boxmenu');

    }
}