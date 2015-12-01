<?php
/* UsaShipping
 *
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */
abstract class Webshopapps_Shipusa_Model_Calculation_Abstract extends Mage_Core_Model_Abstract
{
	abstract public function getFinishedBoxes(&$finishedBoxes, $boxDetails);
	
    
    public function getSeparateBoxes(&$finishedBoxes,$separateItems) {
    	
    	foreach ($separateItems as $itemBx) {

    		$multiplier = $itemBx['max_qty'];
    		if (!is_numeric($multiplier) || $multiplier<1) {
    			$multiplier=1;
    		}
     		$maxWeight=$itemBx['max_weight'];
   			$indWeight = $itemBx['weight'];
   			$sharedMaxQty = -1; // not implemented at present as not logical
   			
    		for ($remainingQty=$itemBx['qty'];$remainingQty>0;) {
    			if ($remainingQty>=$multiplier) {
					$qtyToAdd=$multiplier;
					$remainingQty-=$multiplier;
				} else {
					$qtyToAdd=$remainingQty;
					$remainingQty=0;
				}
				
				if ($maxWeight>0 && ($indWeight*$qtyToAdd)  > $maxWeight) {
					$qtyFitInBox=$maxWeight / $indWeight;
	   				for($remainingInnerQty=$qtyToAdd;$remainingInnerQty>0;) {
	   					if ($qtyFitInBox<$remainingInnerQty) {
	   						// cant fit all in one box
	   						$innerQtyToAdd=$qtyFitInBox;
	   						$remainingInnerQty-=$innerQtyToAdd;
	   					} else {
	   						$innerQtyToAdd=$remainingInnerQty;
	   						$remainingInnerQty=0;
	   					}
	   					
		   				if ( Mage::helper('shipusa')->percentageOverflow($sharedMaxQty,$innerQtyToAdd)) {
							// work out qty to add
							$qtyLeft = Mage::helper('shipusa')->getPercentageQtyLeft($sharedMaxQty,$innerQtyToAdd);
							$remainingInnerQty+=$innerQtyToAdd-$qtyLeft;
							$innerQtyToAdd=$qtyLeft;
						}
	   					
		   				$weightToAdd=$innerQtyToAdd*$indWeight;
		   				
		   				$finishedBoxes[] = array (
								'height' 		=> $itemBx['height'],
		    					'width' 		=> $itemBx['width'],
		    					'length' 		=> $itemBx['length'],
		    					'weight'		=> $weightToAdd+$itemBx['packing_weight'],
		   						'price'			=> $itemBx['price']*$innerQtyToAdd,
		    					'qty'			=> $innerQtyToAdd,
		    					'handling_fee'	=> 	Mage::helper('shipusa')->isHandlingProdInstalled() ? 
		   							Mage::helper('shipusa')->getHandlingProductModel()->getIndividualFee(
	    							$itemBx['shipping_price'],$itemBx['shipping_addon'],$itemBx['shipping_percent'],$innerQtyToAdd) : 0,
	    					);
	   				}
					// when overlaps doesnt bother trying to fill the rest of box up with other items
   				} else {
					$weightToAdd=Mage::helper('shipusa')->getWeightCeil($indWeight*$qtyToAdd);
					$finishedBoxes[] = array (
    					'height' 		=> $itemBx['height'],
    					'width' 		=> $itemBx['width'],
    					'length' 		=> $itemBx['length'],
    					'weight'		=> $weightToAdd+$itemBx['packing_weight'],
    					'price'			=> $itemBx['price']*$qtyToAdd,
						'qty'			=> $qtyToAdd,
    					'handling_fee'	=> 	Mage::helper('shipusa')->isHandlingProdInstalled() ? 
							Mage::helper('shipusa')->getHandlingProductModel()->getIndividualFee(
    						$itemBx['shipping_price'],$itemBx['shipping_addon'],$itemBx['shipping_percent'],$qtyToAdd) : 0,
       				);
				}
    		}
    	}
    }
	
}