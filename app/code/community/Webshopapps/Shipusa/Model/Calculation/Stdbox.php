<?php
/* UsaShipping
 *
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */
class Webshopapps_Shipusa_Model_Calculation_Stdbox extends Webshopapps_Shipusa_Model_Calculation_Abstract
{
		/**
	 * No dimensions set, put in own box
	 * Enter description here ...
	 * @param unknown_type $noDimArr
	 */
	public function getFinishedBoxes(&$finishedBoxes, $boxDetails) {

		if (count($boxDetails)<1) {
			return;
		}
		if (Mage::helper('shipusa')->isDebug()) {
    		Mage::helper('wsalogger/log')->postDebug('usashipping','Sending as standard box',$boxDetails);
    	}
		$totalStdWeight		= 0;
    	$totalStdPrice		= 0;
		$stdHandlingFee 	= 0;

		foreach ($boxDetails as $boxDetail) {
			$totalStdWeight+=$boxDetail['weight'];
		    $totalStdPrice+=$boxDetail['price'];
		    if (Mage::helper('shipusa')->isHandlingProdInstalled()) {
		    	$stdHandlingFee +=  Mage::helper('shipusa')->getHandlingProductModel()->getIndividualFee(
			    					$boxDetail['shipping_price'],$boxDetail['shipping_addon'],$boxDetail['shipping_percent'],$boxDetail['qty']);
		    }
		}

    	if ($totalStdWeight>0) {
    		$finishedBoxes[] = array (
    			'height' 		=> 0,
    			'width' 		=> 0,
    			'length' 		=> 0,
    			'qty'			=> 0,
    			'weight'		=> $totalStdWeight,
    			'price'			=> $totalStdPrice,
    			'handling_fee'	=> $stdHandlingFee,
       		);
    	}
	}


}