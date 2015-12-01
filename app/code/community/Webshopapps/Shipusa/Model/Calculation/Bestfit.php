<?php
/* UsaShipping
 *
 * Date        1/5/14
 * Author      Karen Baker
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */

class Webshopapps_Shipusa_Model_Calculation_Bestfit {


    public function calculateBoxesForProducts(&$dimArr, &$noDimArr,$bestFitArr) {

        // given a product with an array of possible ship boxes

        // for each product calculate the dimensions
        // then work out how many can fit in the box based on the max quantity and the max weight & volume of product against the box volume
        // we don't check to see whether item can physically fit though




        foreach ($bestFitArr as $bestFitProduct) {

            if ($bestFitProduct['ship_box_tolerance'] != null){

                $tolerance = $bestFitProduct['ship_box_tolerance'];
            }else{
                $tolerance = Mage::getStoreConfig('shipping/shipusa/best_fit_tolerance');
            }

            if (Mage::helper('shipusa')->isDebug()) {
                 Mage::helper('wsalogger/log')->postDebug('usashipping','Tolerance', $tolerance);
            }

            $productDimensions = $bestFitProduct['width']*$bestFitProduct['length']*$bestFitProduct['height'];
            $boxes = array();

            if (Mage::helper('shipusa')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('usashipping','Product:', $bestFitProduct['sku']);
            }

            //foreach possible box, calculate max products
            foreach (explode(",", $bestFitProduct['possible_ship_boxes']) as $possibleBox) {

                $boxDetails = Mage::getModel('boxmenu/boxmenu')->load($possibleBox);

                //Ensure the shipping box still exists
                if(!$boxDetails->getId()) {
                    if (Mage::helper('shipusa')->isDebug()) {
                        Mage::helper('wsalogger/log')->postDebug('usashipping','Box id no longer exists, skipping id: ',
                            $possibleBox['ship_box_id']);
                    }
                    continue;
                }

                if (Mage::helper('shipusa')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('usashipping','Calculating for box: ', $possibleBox['ship_box_id']);
                    Mage::helper('wsalogger/log')->postDebug('usashipping','Box Dimensions: ', $boxDetails['length'].', '.
                        $boxDetails['width'].', '.$boxDetails['height']);
                }

                if (!$this->checkCanFitInBox($boxDetails,$bestFitProduct)) {
                    if (Mage::helper('shipusa')->isDebug()) {
                        Mage::helper('wsalogger/log')->postDebug('usashipping','Exceeds allowed dimensions, skipping id: ',
                            $possibleBox['ship_box_id']);
                    }
                    continue;
                }

                $boxDimensions = $boxDetails['width']*$boxDetails['length']*$boxDetails['height'];

                //$maxBoxQty = $boxDetails->getMultiplier();
              //  $maxBoxWeight = $boxDetails->getMaxWeight();

                //below line really doesnt do anything, max products doesnt really limit the amount of items,
                // only true box volume will do that. I will temporarily take care of it in populateDimArr()
                if ($productDimensions>0 && $boxDimensions>0) {
                    $maxProductsForBoxVolume  = ($boxDimensions-($boxDimensions*.01*
                            $tolerance))/$productDimensions;
                } else {
                    // box has no dimensions
                    $maxProductsForBoxVolume = 1000000;
                }

              //  $maxProductsForBoxQty = $maxBoxQty == -1 ? 1000000 : $maxBoxQty;
              //  $maxProductsForBoxWeight    = $maxBoxWeight == -1 ? 1000000 : $maxBoxWeight/$bestFitProduct['weight'];


                // don't need to take the lowest one of these as we can leave it to next part of algorithm to sort
                // also dont want to restrict based on box max qty/weight as that will mean when works out percentage of
                // box thats being taken up will be wrong.
                //$finalMaxQty = min(array($maxProductsForBoxVolume,$maxProductsForBoxWeight,$maxProductsForBoxQty));
                $finalMaxQty = $maxProductsForBoxVolume;

                if (Mage::helper('shipusa')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('dimensional','Final Max Qty', $finalMaxQty);
                }

                if ($finalMaxQty<1) {
                    continue;
                }

                $finalMaxQty = $finalMaxQty==1000000 ? -1 : floor($finalMaxQty);  // put it back to -1
                $boxes[] = $this->populateDimArr($boxDetails, $finalMaxQty, $tolerance);
            }

            // if can't pack into any box then put in noDim Array
            $bestFitProduct['single_boxes'] = $boxes;

            if (count($boxes)<1) {
                $noDimArr[] = $bestFitProduct;
            } else {
                $dimArr[] = $bestFitProduct;
            }
            if (Mage::helper('shipusa')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('dimensional','Calculated Boxes for Product:'. $bestFitProduct['sku'],
                    $dimArr);
            }
        }



        return;
    }

    /**
     * Checks product can actually fit in a box
     * @param $boxDetails
     * @param $bestFitProduct
     */
    protected function checkCanFitInBox($boxDetails,$bestFitProduct) {
       // $bestFitProduct['width']*$bestFitProduct['length']*$bestFitProduct['height'];
       // $boxDetails['width']*$boxDetails['length']*$boxDetails['height']


        $productDims = array($bestFitProduct['width'],$bestFitProduct['length'],$bestFitProduct['height']);
        $boxDims = array($boxDetails['width'],$boxDetails['length'],$boxDetails['height']);
        rsort($productDims);
        rsort($boxDims);


        if (min($boxDims)<=0) {
            return true; // no box dims so we don't care about box volume
        }

        for ($i=0;$i<3;$i++) {
            if ($productDims[$i]>$boxDims[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a box on the fly, using the original box details but modify the max qty that can fit in
     * @param $boxDetails
     * @param $finalMaxQty Maximum quantity that can fit in this box
     * @return array
     */
    protected function populateDimArr($boxDetails,$finalMaxQty, $tolerance)
    {

        $maxBoxQty = $boxDetails->getMultiplier() > 0 ? $boxDetails->getMultiplier() : -1;  // this is the box wide max qty, not at product level
        $box = array();
        $box['box_id']                      = $boxDetails->getId();
        $box['length']                      = $boxDetails->getLength();
        $box['width']                       = $boxDetails->getWidth();
        $box['height']                      = $boxDetails->getHeight();
        $box['max_shipbox_weight']          = $boxDetails->getMaxWeight() > 0 ? $boxDetails->getMaxWeight() : -1;
        $box['max_shipbox_qty']             = $maxBoxQty;
        $box['perc_qty_per_item']           = $maxBoxQty > 0 ? (1/$maxBoxQty)*100 : -1;
        $packingWeight                      = $boxDetails->getPackingWeight();
        $box['packing_weight']              = $packingWeight > 0 ? $packingWeight : 0;
        // manually adding in tolerance because algorithm does not look at max_ship_box_qty
        $outerBoxDim = Mage::helper('shipusa')->calculateBoxVolume($box['length'],$box['height'],$box['width']);
        $outerBoxDim = $outerBoxDim-($outerBoxDim*.01*$tolerance);
        $box['box_volume'] 	                = $outerBoxDim;
        $box['item_volume']                 = $finalMaxQty <= 0  ? -1 : $outerBoxDim/$finalMaxQty;
        $box['max_box']                     = $finalMaxQty; // maximum number of this product that can fit in the box
        $box['max_qty']                     = -1;
        $box['min_qty']                     = 0;

        return $box;
    }
}