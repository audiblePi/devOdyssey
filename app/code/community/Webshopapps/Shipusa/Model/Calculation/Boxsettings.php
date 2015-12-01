<?php
/* UsaShipping
 *
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */
class Webshopapps_Shipusa_Model_Calculation_Boxsettings extends Webshopapps_Shipusa_Model_Calculation_Singlebox
{

	protected static $_defaultLength;
	protected static $_defaultWidth;
	protected static $_defaultHeight;

    public static function populateBoxSettings(&$params,$getDimSettings = false) {
    	$params['max_qty']=1;
        $params['max_weight']=-1;
        $params['packing_weight']=0;

        self::_populateDefaults();
    	if ($getDimSettings && empty($params['ship_box_id'])) {
	        $params['length']=self::$_defaultLength;
    		$params['width']=self::$_defaultWidth;
	        $params['height']=self::$_defaultHeight;
    	} else {
    		$boxDetails = Mage::getModel('boxmenu/boxmenu')->load($params['ship_box_id']);
		 	$params['max_qty']=$boxDetails->getMultiplier();
    		$params['max_weight'] = $boxDetails->getMaxWeight();
		 	if (!empty($params['ship_box_id']) && !Mage::helper('shipusa')->isVolumePackingAlgorithm()) {
		 	    // if exact packing/largest algorithm and box id then override local settings.
		 		// Cant do this for volume logic as wont work

		    	$params['length']=Mage::helper('shipusa')->getWeightCeil($boxDetails->getLength());
		    	$params['width']=Mage::helper('shipusa')->getWeightCeil($boxDetails->getWidth());
		    	$params['height']=Mage::helper('shipusa')->getWeightCeil($boxDetails->getHeight());
		    	$params['packing_weight']=$boxDetails->getPackingWeight();
		    	if ($getDimSettings ) {
				 	if (empty($params['length']) || $params['length']<=0) {
						$params['length']=self::$_defaultLength;
						$params['width']=self::$_defaultWidth;
						$params['height']=self::$_defaultHeight;
				 	}
		    	}
		 	}
	    }

    	if (!is_numeric($params['max_qty']) || $params['max_qty']<1) {
    		$params['max_qty']=-1;
    	}
    	if (!is_numeric($params['max_weight']) || $params['max_weight']<1) {
    		$params['max_weight']=-1;
    	}

    }

    private static function _populateDefaults() {
		if (self::$_defaultLength==NULL ) {
			$defaultBoxSize = Mage::getStoreConfig('shipping/shipusa/default_box_size');
	    	$boxDetails = Mage::getModel('boxmenu/boxmenu')->load($defaultBoxSize);

	    	self::$_defaultLength=Mage::helper('shipusa')->getWeightCeil($boxDetails->getLength());
	    	self::$_defaultWidth=Mage::helper('shipusa')->getWeightCeil($boxDetails->getWidth());
	    	self::$_defaultHeight=Mage::helper('shipusa')->getWeightCeil($boxDetails->getHeight());

		 	if (empty(self::$_defaultLength) || self::$_defaultLength<=0) {
				self::$_defaultLength=0;
				self::$_defaultWidth=0;
				self::$_defaultHeight=0;
		 	}
	    	if (Mage::helper('shipusa')->isDebug()) {
	    		Mage::helper('wsalogger/log')->postDebug('usashipping','defaults','length:'.self::$_defaultLength.', width:'.self::$_defaultWidth.', height:'.self::$_defaultHeight);
	    	}
		}
	}



}