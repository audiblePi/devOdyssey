<?php

/*
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/
class Webshopapps_Shipusa_Model_Shipping_Carrier_Source_Packing
{
    public function toOptionArray()
    {
        $arr = $this->getCode('packing_type');
        return $arr;
    }
    
 public function getCode($type, $code='')
    {
        $codes = array(

            'packing_type'=>array(
                'exact_packing' => Mage::helper('shipping')->__('Exact Packing'),
               // 'volume_packing' => Mage::helper('shipping')->__('Average Packing'),  // deprecated
                'largest_packing' => Mage::helper('shipping')->__('Largest Box Packing'),
                'best_fit_packing' => Mage::helper('shipping')->__('Best Fit Packing'),
        ),
            'parent_group'=>array(
                'child'  			=> Mage::helper('shipping')->__('Default(Child) Shipping Group'),
                'both'  			=> Mage::helper('shipping')->__('Parent Shipping Group'),
                'configurable'  	=> Mage::helper('shipping')->__('Configurable Parent, Bundle Child'),
                'bundle'  			=> Mage::helper('shipping')->__('Configurable Child, Bundle Parent'),
        ),

        );

        if (!isset($codes[$type])) {
            throw Mage::exception('Mage_Shipping', Mage::helper('shipping')->__('Invalid Code: %s', $type));
        }

        if (''===$code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            throw Mage::exception('Mage_Shipping', Mage::helper('shipping')->__('Invalid Code %s: %s', $type, $code));
        }

        return $codes[$type][$code];
    }
}
