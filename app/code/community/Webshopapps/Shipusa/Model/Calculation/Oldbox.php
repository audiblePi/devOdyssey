<?php
/* UsaShipping
 *
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */
class Webshopapps_Shipusa_Model_Calculation_Oldbox extends Webshopapps_Shipusa_Model_Calculation_Abstract
{
	/**
	* Given the new way of doing this could refactor this code, but probably not worth. 
	* Would look at removing in next 6 months instead
	* Should not be advising customers to use this functionality
	*/
	public function getFinishedBoxes(&$finishedBoxes, $boxDetails) {
		
		if (count($boxDetails)<1) {
			return;
		}
		
		if (Mage::helper('shipusa')->isDebug()) {
    		Mage::helper('wsalogger/log')->postDebug('usashipping','OldBox',$boxDetails);
    	}
		
		$dimItems = array();
		$multiplierItems=array();
		$counter = 100000;
		
		foreach ($boxDetails as $boxDetail) {
			if ($boxDetail['max_qty']>1  || $boxDetail['max_weight']>0 || $boxDetail['shared_max_qty']>0) {
		    	$this->processMultiplierItems($multiplierItems,$finishedBoxes,$boxDetail);		    	
		    } else  {
		    	$this->processDimItems($dimItems,$boxDetail,$counter);	
		    }
		}
		
 		$this->getSeparateBoxes($finishedBoxes,$dimItems);
    	
    	$innerMultipleItems = $this->finaliseMultiplierBoxes($finishedBoxes,$multiplierItems);
    	$this->finaliseMultiplierBoxes($finishedBoxes,$innerMultipleItems);
	}
	
	
	 /**
     * 
     * Enter description here ...
     * @param $dimItems
     * @param $separateItems
     * @param $params
     */
    private function processDimItems(&$dimItems,$params,&$counter) {
		
		if (array_key_exists($params['ship_box_id'],$dimItems)) {
			$dimItems[$params['ship_box_id']]['weight']+=$params['weight'];
			$dimItems[$params['ship_box_id']]['price']+=$params['price'];
			$dimItems[$params['ship_box_id']]['qty']+=$params['qty'];
    		$dimItems[$params['ship_box_id']]['max_qty']=$params['max_qty'];
    		$dimItems[$params['ship_box_id']]['percentage_full']+=Mage::helper('shipusa')->getPercentageFull($params['shared_max_qty'], $params['qty']);
    		if (Mage::helper('shipusa')->isHandlingProdInstalled()) {
	    		$dimItems[$params['ship_box_id']]['handling_fee'] = 
	    			$dimItems[$params['ship_box_id']]['handling_fee'] + 
	    				Mage::helper('shipusa')->getHandlingProductModel()->getIndividualFee(
	    					$params['shipping_price'],$params['shipping_addon'],$params['shipping_percent'],$params['qty']);
    		}
		} else {
			$counter++;
    		$dimItems[$counter] = array (
    			'weight' 			=> $params['weight']/$params['qty'],
	    		'price'  			=> $params['price']/$params['qty'],
    			'width'  			=> $params['width'],
    			'height' 			=> $params['height'],
        		'length' 			=> $params['length'],
        		'packing_weight' 	=> $params['packing_weight'],
    			'qty' 				=> $params['qty'],
    			'max_qty' 			=> $params['max_qty'],
	    		'max_weight' 		=> $params['max_weight'],
    			'shipping_price'	=> $params['shipping_price'],
    			'shipping_addon'	=> $params['shipping_addon'],
    			'shipping_percent'	=> $params['shipping_percent'],
	    		'handling_fee' 		=> 0,
    			'percentage_full'	=> Mage::helper('shipusa')->getPercentageFull($params['shared_max_qty'], $params['qty']),
    		); 
		}
    }
    
	
   	/**
   	 * Deprecated
   	 * @param unknown_type $params
   	 * @param unknown_type $multiplierQty
   	 * @param unknown_type $multiplerWeight
   	 * @param unknown_type $percentageFull
   	 */
   	private function newBoxRequired($params, $multiplierQty=0, $multiplerWeight=0, $percentageFull=0) {
    	$maxWeight = $params['max_weight'];
    	$newBoxRequired=false;
    	$maxQtyPerBox= $params['shared_max_qty'];
    	
    	if (($maxWeight>0 && 
    		($multiplerWeight+($params['weight']*$params['qty']) > $maxWeight)) || ($params['max_qty']> 0 && 
    		//(($multiplerWeight)+($params['weight']*$params['qty']) > $maxWeight)) || ($params['max_qty']> 0 && 
    		($params['qty']+$multiplierQty)>$params['max_qty'])) {
    		$newBoxRequired=true;
    	} else if (Mage::helper('shipusa')->percentageOverflow($params['shared_max_qty'],$params['qty'],$percentageFull)) {
    		$newBoxRequired=true;
    	}
    	
    	return $newBoxRequired;
    	
    }
    
  
	
	 /**
     * 
     * Has been deprecated
     * @param $multiplierItems
     * @param $packagedMultipleBoxes
     * @param $params
     */
    private function processMultiplierItems(&$multiplierItems,&$packagedMultipleBoxes,$params) {
    	$saveItemDetails = array (
    		'params' 			=> $params,
    		'alt_box_id'		=> $params['alt_box_id'],
    	);	    	
		if (array_key_exists($params['ship_box_id'],$multiplierItems) && array_key_exists('qty',$multiplierItems[$params['ship_box_id']])) {
			if ($this->newBoxRequired(	$params, 
										$multiplierItems[$params['ship_box_id']]['qty'],
										$multiplierItems[$params['ship_box_id']]['weight'],
										$multiplierItems[$params['ship_box_id']]['percentage_full'])
									  ) {
		    		$multiplierItems[$params['ship_box_id']] = 
		    			$this->getSplitBox($packagedMultipleBoxes,$params,$multiplierItems[$params['ship_box_id']]);
		    		//reset box
					if ($multiplierItems[$params['ship_box_id']]['qty']==0) {
			    		$multiplierItems[$params['ship_box_id']]=array();	
			    	}			    	
				}
    		else {
	    		$multiplierItems[$params['ship_box_id']]['weight']+=$params['weight'];
	    		$multiplierItems[$params['ship_box_id']]['price']+=$params['price'];
	    		$multiplierItems[$params['ship_box_id']]['qty']+=$params['qty'];
	    		$multiplierItems[$params['ship_box_id']]['save_item_details'][]=$saveItemDetails;
	    		$multiplierItems[$params['ship_box_id']]['percentage_full']+=Mage::helper('shipusa')->getPercentageFull($params['shared_max_qty'], $params['qty']);
	    		if (Mage::helper('shipusa')->isHandlingProdInstalled()) {
		    		$multiplierItems[$params['ship_box_id']]['handling_fee'] = 
		    			$multiplierItems[$params['ship_box_id']]['handling_fee'] + 
		    				Mage::helper('shipusa')->getHandlingProductModel()->getIndividualFee(
		    					$params['shipping_price'],$params['shipping_addon'],$params['shipping_percent'],$params['qty']);
    			}
    		}
    	} else {
    		if ($this->newBoxRequired($params)) {
    			$multiplierItems[$params['ship_box_id']] = array (
		    		'weight' 			=> 0,
		    		'price' 			=> 0, 
    				'width'  			=> $params['width'],
    				'height' 			=> $params['height'],
        			'length' 			=> $params['length'],
        			'packing_weight' 	=> $params['packing_weight'],
    				'max_qty' 			=> $params['max_qty'],
		    		'qty' 				=> 0,
		    		'max_weight' 		=> $params['max_weight'],
		    		'handling_fee'  	=> 0,
    				'percentage_full'	=> 0,
		    	);
		    	$multiplierItems[$params['ship_box_id']] = $this->getSplitBox($packagedMultipleBoxes,$params,$multiplierItems[$params['ship_box_id']]);
		    	if ($multiplierItems[$params['ship_box_id']]['qty']==0) {
		    		$multiplierItems[$params['ship_box_id']]=array();	
		    	}
		    			
    		} else {
		    	$multiplierItems[$params['ship_box_id']] = array (
		    		'weight' 			=> $params['weight'],
		    		'price'  			=> $params['price'],
    				'height' 			=> $params['height'],
        			'length' 			=> $params['length'],
        			'packing_weight' 	=> $params['packing_weight'],
		    		'width' 			=> $params['width'],
		    		'max_qty' 			=> $params['max_qty'],
		    		'qty' 				=> $params['qty'],
		    		'max_weight' 		=> $params['max_weight'],
		    		'handling_fee'  	=> Mage::helper('shipusa')->isHandlingProdInstalled() ? 
    										Mage::helper('shipusa')->getHandlingProductModel()->getIndividualFee($shippingPrice,$shippingAddOn,$shippingPercent,$params['qty']) : 0,
    				'percentage_full'	=> Mage::helper('shipusa')->getPercentageFull($params['shared_max_qty'], $params['qty']),
    				'save_item_details'	=> array($saveItemDetails),
		    	);
    		}
    	}
    }
	
    
 
    /**
     * Has been deprecated
     * @param $packagedMultipleBoxes
     * @param $params
     * @param $multiplierItem
     */
   	private function getSplitBox(&$packagedMultipleBoxes,$params,$multiplierItem) {
   	
   		$maxQty=$multiplierItem['max_qty'];
   		$maxWeight=$multiplierItem['max_weight'];
   		$percentageFull = $multiplierItem['percentage_full'];
   		$sharedMaxQty = $params['shared_max_qty'];
   		$indWeight = $params['weight']/$params['qty'];
   		$indPrice = $params['price']/$params['qty'];
   		$newMultiplierItem = $multiplierItem;

   		for($remainingQty=$params['qty'];$remainingQty>0;) {
   			if ($maxQty!=-1 && $remainingQty>=$maxQty-$newMultiplierItem['qty']) {
				$qtyToAdd=$maxQty-$newMultiplierItem['qty'];
				$remainingQty-=$maxQty-$newMultiplierItem['qty'];
			} else {
				$qtyToAdd=$remainingQty;
				$remainingQty=0;
			}
			
			if ( Mage::helper('shipusa')->percentageOverflow($sharedMaxQty,$qtyToAdd,$newMultiplierItem['percentage_full'])) {
				// work out qty to add
				$qtyLeft = Mage::helper('shipusa')->getPercentageQtyLeft($sharedMaxQty,$qtyToAdd,$newMultiplierItem['percentage_full']);
				$remainingQty+=$qtyToAdd-$qtyLeft;
				$qtyToAdd=$qtyLeft;
			}
	
			if ($maxWeight>0 && (($indWeight*$qtyToAdd) + $newMultiplierItem['weight']) > $maxWeight) {
				$finishedBox= array (
    					'height' 				=> $params['height'],
    					'width' 				=> $params['width'],
    					'length' 				=> $params['length'],
						'weight'				=> $newMultiplierItem['weight']+ $params['packing_weight'],
						'price'					=> Mage::helper('shipusa')->toTwoDecimals($newMultiplierItem['price']),
						'qty'					=> $newMultiplierItem['qty'],
			    		'handling_fee' 	 		=> $newMultiplierItem['handling_fee'],
					);
				$newMultiplierItem['weight']=0;
				$newMultiplierItem['price']=0;
				$newMultiplierItem['qty']=0;
				$newMultiplierItem['handling_fee']=0;
				
				$qtyFitInBox=($maxWeight-$finishedBox['weight']) / $indWeight;
   				for($remainingInnerQty=$qtyToAdd;$remainingInnerQty>0;) {
   					$innerQtyToAdd=0;
   					if ($qtyFitInBox>0 ) {
	   					if ($qtyFitInBox<$remainingInnerQty) {
	   						// cant fit all in one box
	   						$innerQtyToAdd=$qtyFitInBox;
	   						$remainingInnerQty-=$innerQtyToAdd;
	   					} else {
	   						$innerQtyToAdd=$remainingInnerQty;
	   						$remainingInnerQty=0;
	   					}
	   					
	   					if ( Mage::helper('shipusa')->percentageOverflow($sharedMaxQty,$innerQtyToAdd,$newMultiplierItem['percentage_full'])) {
							// work out qty to add
							$qtyLeft = Mage::helper('shipusa')->getPercentageQtyLeft($sharedMaxQty,$innerQtyToAdd,$newMultiplierItem['percentage_full']);
							$remainingInnerQty+=$innerQtyToAdd-$qtyLeft;
							$innerQtyToAdd=$qtyLeft;
	   					}
   					}
   					
					if ($innerQtyToAdd > 0) {
		   				$weightToAdd=$innerQtyToAdd*$indWeight;
						$finishedBox['weight']+=$weightToAdd;
						$finishedBox['price']+=Mage::helper('shipusa')->toTwoDecimals(($innerQtyToAdd*$indPrice));
						$finishedBox['qty']+=$innerQtyToAdd;
						$finishedBox['handling_fee']+=  Mage::helper('shipusa')->isHandlingProdInstalled() ? Mage::helper('shipusa')->getHandlingProductModel()->getIndividualFee(
	    							$params['shipping_price'],$params['shipping_addon'],$params['shipping_percent'],$innerQtyToAdd) : 0;
					}
					
					if ($innerQtyToAdd > 0 || $qtyFitInBox==0) {
						$packagedMultipleBoxes[] =	$finishedBox;
						$finishedBox['weight']=0;
						$finishedBox['price']=0;
						$finishedBox['qty']=0;
						$finishedBox['handling_fee']=0;
						// when overlaps doesnt bother trying to fill the rest of box up with other items
						$qtyFitInBox=$maxWeight / $indWeight;
					}
   				}
			} else {
				$weightToAdd=Mage::helper('shipusa')->getWeightCeil($indWeight*$qtyToAdd);
				$priceToAdd=ceil($indPrice*$qtyToAdd);
				$packagedMultipleBoxes[] = array (
    					'height' 				=> $params['height'],
    					'width' 				=> $params['width'],
    					'length' 				=> $params['length'],
						'weight'				=> $newMultiplierItem['weight']+$weightToAdd,
						'price'					=> Mage::helper('shipusa')->toTwoDecimals($newMultiplierItem['price']+$priceToAdd),
						'qty'					=> $newMultiplierItem['qty']+$qtyToAdd,
			    		'handling_fee'  		=> Mage::helper('shipusa')->isHandlingProdInstalled() ? $newMultiplierItem['handling_fee']+
							Mage::helper('shipusa')->getHandlingProductModel()->getIndividualFee(
    							$params['shipping_price'],$params['shipping_addon'],$params['shipping_percent'],$qtyToAdd) : 0,
				);
				if ($newMultiplierItem['weight']>0){
						$newMultiplierItem['weight']=0;
						$newMultiplierItem['qty']=0;
						$newMultiplierItem['handling_fee']=0;
						$newMultiplierItem['percentage_full']=0;
				}
			}
		}
		
		$lastItem = end($packagedMultipleBoxes);
		if (($sharedMaxQty>0 && Mage::helper('shipusa')->getPercentageFull($sharedMaxQty,$lastItem['qty'],0)<100) ||
			(($maxWeight<0 || $lastItem['weight']<$maxWeight) && ($maxQty<0 ||$lastItem['qty']<$maxQty))) {
				// found space
				$newMultiplierItem['weight'] 			= $lastItem['weight'];
				$newMultiplierItem['price'] 			= $lastItem['price'];
				$newMultiplierItem['qty']				= $lastItem['qty'];
				$newMultiplierItem['handling_fee']		= $lastItem['handling_fee'];
				$newMultiplierItem['percentage_full']	= Mage::helper('shipusa')->getPercentageFull($sharedMaxQty, $lastItem['qty']);
				$newParams 								= $params;
				$newParams['weight']					= $lastItem['weight'];
				$newParams['price']						= $lastItem['price'];
				$newParams['qty']						= $lastItem['qty'];
				$newMultiplierItem['save_item_details'] = array(array (
		    		'params' 			=> $newParams,
		    		'alt_box_id'		=> $params['alt_box_id'],
		    	));	    	
				array_pop($packagedMultipleBoxes);
				Mage::helper('wsalogger/log')->postDebug('usashipping','Holding Split Box for further filling', $newMultiplierItem,Mage::helper('shipusa')->isDebug());
		} else { 
			$newMultiplierItem['qty']				= 0;
		}
		
   		return $newMultiplierItem;
   	}
   	
   	
    /**
     * Any boxes that aren't filled up go here
     * Deprecated
     * @param unknown_type $multiplierItems
     */
    private function finaliseMultiplierBoxes(&$finishedBoxes,$multiplierItems) {
    	if (Mage::helper('shipusa')->isDebug()) {
    		Mage::helper('wsalogger/log')->postDebug('usashipping','multiplier boxes',$multiplierItems);
    	}
    	$innerMultiplierItems=array();
    	
    	// let's grab the saved item details
   		 foreach ($multiplierItems as $itemBx) {
    		if (empty($itemBx)) {
    			continue;
    		}
    		$alternateFound = false;
    		
    		// first see if we will add this one in.
    		if (array_key_exists('save_item_details',$itemBx)) {
    			$alternateFound = true;
    			foreach ($itemBx['save_item_details'] as $savedDetails) {
	    			$altBoxedId = $savedDetails['alt_box_id'];
    				if ($altBoxedId=='' || $altBoxedId=='X')	{
    					$alternateFound = false;
    					break;
    				}
    			}
    		}
    		if ($alternateFound) {    				
    			foreach ($itemBx['save_item_details'] as $savedDetails) {
	    			// let's push together on alternative boxes
	    			$savedDetails['params']['ship_box_id']=$savedDetails['alt_box_id'];
	    			$savedDetails['params']['alt_box_id']='X'; // so doesn't loop
	    			Webshopapps_Shipusa_Model_Calculation_Boxsettings::populateBoxSettings($savedDetails['params'],false);  
	    			$testPackagedBoxes=array();
	    			$savedMultiplierItems=$innerMultiplierItems;
	    			$this->processMultiplierItems($innerMultiplierItems,$testPackagedBoxes, $savedDetails['params']);
	    			if (count($testPackagedBoxes)>0  && !array_key_exists($altBoxedId,$savedMultiplierItems)) {
	    				$alternateFound = false;
	    				if (array_key_exists($altBoxedId,$innerMultiplierItems)) {
	    					$innerMultiplierItems[$altBoxedId]=array();
	    				}
	    			} else {
	    				foreach ($testPackagedBoxes as $finishedBox) {
	    					$finishedBoxes[]=$finishedBox;
	    				}
	    			}
    			}
    		} 
   		 	
    		if (!$alternateFound) {
    			$finishedBoxes[] = array (
    				'height' 		=> $itemBx['height'],
    				'width' 		=> $itemBx['width'],
    				'length' 		=> $itemBx['length'],
    				'weight'		=> Mage::helper('shipusa')->getWeightCeil($itemBx['weight']+ $itemBx['packing_weight']),
    				'price'			=> Mage::helper('shipusa')->toTwoDecimals($itemBx['price']),
    				'qty'			=> $itemBx['qty'],
    				'handling_fee'	=> $itemBx['handling_fee']
				);
    		}
   		 }
   		 return $innerMultiplierItems;
    }
        


    
    
  
	
	
}
