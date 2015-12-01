<?php
/* Dimensional Shipping
 *
 * In order to get this calculation working you need the ship_box attribute
 *
  SELECT @entity_type_id:=entity_type_id FROM {$this->getTable('eav_entity_type')} WHERE entity_type_code='catalog_product';

  INSERT ignore INTO {$this->getTable('eav_attribute')}
    SET entity_type_id 	= @entity_type_id,
	attribute_code 	= 'ship_box',
	backend_type	= 'int',
	frontend_input	= 'select',
    source_model   = 'boxmenu/boxmenu',
	is_required	= 0,
	frontend_label	= 'Packing Box';

  SELECT @attribute_id:=attribute_id FROM {$this->getTable('eav_attribute')} WHERE attribute_code='ship_box';

  INSERT ignore INTO {$this->getTable('catalog_eav_attribute')}
    SET attribute_id 	= @attribute_id,
    	is_visible 	= 1,
    	used_in_product_listing	= 1,
    	is_filterable_in_search	= 0;
 *
 * @category   Webshopapps
 * @package    Webshopapps_Shipusa
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 * @author     Karen Baker
 */

class Webshopapps_Shipusa_Model_Calculation_Largestbox extends Mage_Core_Model_Abstract {


    public function getFinishedBoxes(&$finishedBoxes, $boxDetails) {

        if (count($boxDetails)<1) {
            return;
        }
        if (Mage::helper('shipusa')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('usashipping','Largest box',$boxDetails);
        }

        $largestBox = $this->getLargestBox($boxDetails);

        $finishedBoxes[] = array (
            'height' 		=> Mage::helper('shipusa')->getWeightCeil($largestBox['height']),
            'width' 		=> Mage::helper('shipusa')->getWeightCeil($largestBox['width']),
            'length' 		=> Mage::helper('shipusa')->getWeightCeil($largestBox['length']),
            'weight'		=> $largestBox['total_weight'],
            'price'			=> $largestBox['total_price'], // 2 dec places?
            'handling_fee'	=> 0, // not supported for this version
        );

        if (Mage::helper('shipusa')->isDebug()) {
            Mage::helper('wsalogger/log')->postInfo('usashipping','Largest Box Package',$finishedBoxes);

        }

    }

    protected function getLargestBox($boxDetails) {

        $largestBox 		= array(
            'length'        => 0,
            'width'         => 0,
            'height'        => 0,
            'total_weight'	=> 0,
            'total_price'	=> 0
        );

        $highestVolume = 0;
        foreach ($boxDetails as $boxDetail) {

            $volume = $boxDetail['height']*$boxDetail['width']*$boxDetail['length'];
            if ($volume>$highestVolume) {
                $highestVolume=$volume;
                $largestBox['length']=$boxDetail['length'];
                $largestBox['width']=$boxDetail['width'];
                $largestBox['height']=$boxDetail['height'];
            }
            $largestBox['total_weight'] += $boxDetail['weight'];
            $largestBox['total_price']  += $boxDetail['price'];
        }

        return $largestBox;

    }



}