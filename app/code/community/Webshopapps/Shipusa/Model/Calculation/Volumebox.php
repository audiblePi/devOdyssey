<?php
/* UsaShipping
 *
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 * *
 * Doesnt currently support packing_weight logic
 */
class Webshopapps_Shipusa_Model_Calculation_Volumebox extends Webshopapps_Shipusa_Model_Calculation_Abstract
{
	public function getFinishedBoxes(&$finishedBoxes, $boxDetails) {
			
		if (count($boxDetails)<1) {
			return;
		}
		if (Mage::helper('shipusa')->isDebug()) {
    		Mage::helper('wsalogger/log')->postDebug('usashipping','Volume box',$boxDetails);
    	}
    	
		$volumeTotals 		= array(
    		'total_volume'	=> 0,
    		'box_id'		=> '',
    	    'total_weight'	=> 0,
    		'total_price'	=> 0
    	);
    	
		foreach ($boxDetails as $boxDetail) {
			// get the dimensions
			if ($boxDetail['height']==0 ||
				$boxDetail['width']==0 ||
				$boxDetail['length']==0 ) {
				$volumeTotals['total_weight'] += $boxDetail['weight'];
				$volumeTotals['total_price'] += $boxDetail['price'];
				return $this->getDefaultBox($finishedBoxes,$volumeTotals);
			}	
			
			$volumeTotals['total_volume'] += $boxDetail['height'] * $boxDetail['width'] * $boxDetail['length'] * $boxDetail['qty'];
			$volumeTotals['total_weight'] += $boxDetail['weight'];
			$volumeTotals['total_price'] += $boxDetail['price'];
			
			if ($boxDetail['ship_box_id']!='') {
				$volumeTotals['box_id']=$boxDetail['ship_box_id'];
			}
		}
		
		
		if ($volumeTotals['box_id']=='' || $volumeTotals['total_volume']<=0 ) {
				return $this->getDefaultBox($finishedBoxes,$volumeTotals);
		}
		// put one into the other
		$boxDetails = Mage::getModel('boxmenu/boxmenu')->load($volumeTotals['box_id']);
		
		$boxVolume = $boxDetails->getHeight() * $boxDetails->getWidth() * $boxDetails->getLength();
		$divisor = $volumeTotals['total_volume']/$boxVolume;
		
		
		$totalBoxes = ceil($divisor);
		$boxWeight = Mage::helper('shipusa')->getWeightCeil($volumeTotals['total_weight']/$divisor);
		
		if ($boxWeight > $boxDetails['max_weight'] && $boxDetails['max_weight'] > 0) {
			// can only put in boxes of 150lb and below
			// get total boxes based on weight not volume as is higher
			$divisor = $volumeTotals['total_weight']/$boxDetails['max_weight'];
			$totalBoxes = ceil($divisor);
			$boxWeight = Mage::helper('shipusa')->getWeightCeil($boxDetails['max_weight']);
		} 
		
		$indBoxPrice = ceil($volumeTotals['total_price']/$divisor);
		
		$remainderWeight = $volumeTotals['total_weight'] - ($boxWeight * ($totalBoxes-1));
		$remainderPrice = $volumeTotals['total_price'] - ($indBoxPrice * ($totalBoxes-1));
				
		for ($i=0;$i<$totalBoxes-1;$i++) {
			$finishedBoxes[] = array (
    			'height' 		=> $boxDetails->getHeight(),
    			'width' 		=> $boxDetails->getWidth(),
    			'length' 		=> $boxDetails->getLength(),
    			'weight'		=> $boxWeight,
    			'price'			=> $indBoxPrice, // 2 dec places?
    			'handling_fee'	=> 0, // not supported for this version
       		);
		}
		// now deal with last
		$finishedBoxes[] = array (
    		'height' 		=> $boxDetails->getHeight(),
    		'width' 		=> $boxDetails->getWidth(),
    		'length' 		=> $boxDetails->getLength(),
    		'weight'		=> $remainderWeight,
    		'price'			=> $remainderPrice,
    		'handling_fee'	=> 0, // not supported for this version
       	);
		
		
    	if (Mage::helper('shipusa')->isDebug()) {
    		Mage::helper('wsalogger/log')->postInfo('usashipping','Totals Calculated',$volumeTotals);
    		
			Mage::helper('wsalogger/log')->postInfo('usashipping','Average Metrics','Box Volume: '.$boxVolume.' , Cart Volume: '.$volumeTotals['total_volume'].
				' Unfilled Box Weight: '.$remainderWeight. ' Unfilled Box Price: '.$remainderPrice);
    	}
		
	}
	
	private function getDefaultBox(&$finishedBoxes,$volumeTotals) {
		$finishedBoxes[] = array (
    			'height' 		=> 0,
    			'width' 		=> 0,
    			'length' 		=> 0,
    			'weight'		=> $volumeTotals['total_weight'],
    			'price'			=> $volumeTotals['total_price'],
    			'handling_fee'	=> 0, // not supported for this version
       	);
       	return $finishedBoxes;
	}
	
	  function cmp($a,$b) {
	    if ($a['volume'] == $b['volume']) {
	        return 0;
	    }
    	return ($a['volume']< $b['volume']) ? -1 : 1;

    }
  
	
	
}