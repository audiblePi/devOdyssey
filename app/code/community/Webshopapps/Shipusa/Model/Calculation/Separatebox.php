<?php
/* UsaShipping
 *
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */


class Webshopapps_Shipusa_Model_Calculation_Separatebox extends Webshopapps_Shipusa_Model_Calculation_Abstract
{
	/**
	 * Products specified to ship separately
	 */
	public function getFinishedBoxes(&$finishedBoxes, $boxDetails) {

		if (count($boxDetails)<1) {
			return;
		}
		if (Mage::helper('shipusa')->isDebug()) {
    		Mage::helper('wsalogger/log')->postDebug('usashipping','Separatebox',$boxDetails);
    	}

		$separateItems		= array();
		foreach ($boxDetails as $boxDetail) {
			if (!empty($boxDetail['single_boxes'])) {
				$this->populateSingleBoxes($finishedBoxes,$boxDetail);
	        } else if ($boxDetail['ship_algorithm']!='') {
        		$this->populateManyBoxes($finishedBoxes,$boxDetail);  // deprecated
	        } else {
	        	$this->processSeparateItems($separateItems,$boxDetail);
	        }
		}

    	$this->getSeparateBoxes($finishedBoxes,$separateItems);

	}



   	/**
	 * Only used when box_algorithm field populated
	 * Doesnt implement handling fee logic to tie in with Handling Product
	 * Deprecated, no longer supported in new version
	 * @param unknown_type $shipAlgorithm
	 * @param unknown_type $qty
	 * Do Not advised customers of functionality - Replaced by Multiple Boxes tab
	 */
    private function populateManyBoxes(&$finishedBoxes,$params) {

    	$shipAlgorithm = $params['ship_algorithm'];
    	$shipSeparate = $params['ship_separate'] || Mage::helper('shipusa')->shipAllSeparate() ? true : false;
    	$boxes = explode(',',$shipAlgorithm);

    	foreach ($boxes as $box) {
    		$dimensions = explode ('x',$box);

    		if (count($dimensions)<4) {
    			continue;
    		}

    		if ($shipSeparate) {
    			for ($i=0;$i<$params['qty'];$i++) {
			    	$finishedBoxes[] = array (
			    		'weight' 	=> trim($dimensions[0]),
    					'price'		=> 50, //TODO
			    		'length' 	=> $dimensions[1],
			    		'width' 	=> $dimensions[2],
			    		'height'	=> $dimensions[3],
			    	    'qty'		=> 1,
						'handling_fee'  	=> 0,
			    	);
    			}
    		} else {
    			$finishedBoxes[] = array (
			    		'weight' 	=> trim($dimensions[0]),
    					'price'		=> 50, //TODO
			    		'length' 	=> $dimensions[1],
			    		'width' 	=> $dimensions[2],
			    		'height'	=> $dimensions[3],
			    	    'qty'		=> $params['qty'],
						'handling_fee'  	=> 0,
			    	);
    		}
    	}

    	if (Mage::helper('shipusa')->isDebug()) {
    		Mage::helper('wsalogger/log')->postDebug('usashipping','populateManyBoxes',$finishedBoxes);
    	}

    	return true;
    }

    private function populateSingleBoxes(&$finishedBoxes,$boxDetail) {


        if ($boxDetail['qty']<1) {
            if (Mage::helper('shipusa')->isDebug()) {
                Mage::helper('wsalogger/log')->postCritical('usashipping','populateSingleBoxes: Qty Set to zero',$boxDetail);
            }
            Mage::getSingleton('shipusa/calculation_stdbox')->getFinishedBoxes($finishedBoxes,array($boxDetail));
            return;

        }
        if (Mage::helper('shipusa')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('usashipping','populateSingleBoxes',$boxDetail);
        }

        $modifiedBoxDetail = $boxDetail;
        if (is_numeric($boxDetail['num_boxes']) && $boxDetail['num_boxes']<2) {
            $numBoxes = 1;
        } else {
            $numBoxes = $boxDetail['num_boxes'];
        }
        $modifiedBoxDetail['qty']=1/$numBoxes;
        $modifiedBoxDetail['orig_qty']=1/$numBoxes;
        $modifiedBoxDetail['price']=$modifiedBoxDetail['price']/$boxDetail['qty']/$numBoxes;
        $modifiedBoxDetail['weight']=$modifiedBoxDetail['weight']/$boxDetail['qty']/$numBoxes;
        for ($i=0;$i<$boxDetail['qty'];$i++) {
            for ($j=0;$j<$numBoxes;$j++) {
                Mage::getSingleton('shipusa/calculation_singlebox')->getFinishedBoxes($finishedBoxes,  array($modifiedBoxDetail));
            }
        }

    }

		/***
	 * BOX Population Logic
	 */
	private function processSeparateItems(&$separateItems,$boxDetails) {
    	$indPackageWeight = ($boxDetails['weight']/$boxDetails['qty'])/$boxDetails['num_boxes'];
    	$indPackageValue  = ($boxDetails['price']/$boxDetails['qty'])/$boxDetails['num_boxes'];
    	for($i=0;$i<$boxDetails['num_boxes'];$i++) {
	    		$separateItems[] = array (
    				'weight' 			=> $indPackageWeight,
	    			'price' 			=> $indPackageValue,
    				'width'  			=> $boxDetails['width'],
    				'height' 			=> $boxDetails['height'],
        			'length' 			=> $boxDetails['length'],
	    		    'packing_weight' 	=> $boxDetails['packing_weight'],
        			'qty' 				=> $boxDetails['qty'],
	    			'max_qty' 			=> $boxDetails['max_qty'],
	    			'max_weight' 		=> $boxDetails['max_weight'],
	    			'shipping_price'	=> $boxDetails['shipping_price'],
	    			'shipping_addon'	=> $boxDetails['shipping_addon'],
	    			'shipping_percent'	=> $boxDetails['shipping_percent'],
		    		'handling_fee'  	=> 0,
	    		);
    	}
    }
}
