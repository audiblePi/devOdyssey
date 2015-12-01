<?php
/* UsaShipping
 *
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */
class Webshopapps_Shipusa_Model_Calculation_Singlebox extends Webshopapps_Shipusa_Model_Calculation_Abstract
{

    const EPSILON = 0.00001;

    protected $_boxType = null;
    protected $_processingFlatBoxes = false;
    protected $_tempPackedBoxes;
    protected $_counter;


    public function getFinishedBoxes(&$finishedBoxes, $itemDetails, $processingFlatBoxes = false)
    {

        // decide whether to use singlebox tab or flat box tab
        $this->_boxType = $processingFlatBoxes ? 'flat_boxes' : 'single_boxes';
        $this->_processingFlatBoxes = $processingFlatBoxes; // used for recursion
        $this->_tempPackedBoxes = array();
        $this->_counter = -1;

        $packed = $this->processSingleBoxAlgorithm($itemDetails);

        if (!$packed) {
            Mage::getSingleton('shipusa/calculation_stdbox')->getFinishedBoxes($finishedBoxes, $itemDetails);
        }


        $this->packFinishedBoxes($finishedBoxes); // final pack of finished boxes

    }

    protected function processSingleBoxAlgorithm(&$itemDetails, $packNewBox = true, $unprocessedItemDetails = array())
    {


        if (count($unprocessedItemDetails) > 0) {
            $itemDetails = array_merge($itemDetails, $unprocessedItemDetails);
        }

        // let's see how many items as this may be easy
        switch (count($itemDetails)) {
            case 0:
                $packed = true;
                break;
            case 1:
                $packed = $this->processSingle(reset($itemDetails), $packNewBox); // reset returns 1st element
                break;
            default:
                $packed = $this->processMultiple($itemDetails, $packNewBox);
                break;
        }

        return $packed;

    }


    protected function processSingle($box, $packNewBox)
    {

        if (Mage::helper('shipusa')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('usashipping', 'Single box 1 item', $box);
        }
        $usableBoxes = array();

        // lets find the closest match
        $boxArray = $box[$this->_boxType];

        $this->calculateApplicableBox($boxArray, $box, $usableBoxes);

        if (Mage::helper('shipusa')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('usashipping', 'Usable boxes', $usableBoxes);
        }

        if (count($usableBoxes) < 1) {
            return false;
        }
        // find closest
        usort($usableBoxes, array($this, 'cmp_single'));
        $box['selected_box'] = $usableBoxes[0];


        return $this->managePackingItems(array($box), $packNewBox);
    }

    protected function calculateApplicableBox($boxArray, $box, &$usableBoxes)
    {
        $qty = $box['qty'];
        $origQty = $box['orig_qty'];
        $weight = $box['weight'];

        foreach ($boxArray as $candidateBox) {
            $maxBoxWeight = -1;
            if (($candidateBox['max_qty'] < 0 || $candidateBox['max_qty'] >= $origQty) && $origQty >= $candidateBox['min_qty']) {
                // use this box
                if ($candidateBox['box_id'] != 0) {
                    $maxBoxWeight = $candidateBox['max_shipbox_weight'];
                    if (!$box['split_product'] && $maxBoxWeight > 0 && $maxBoxWeight < ($weight / $qty)) {
                        continue;
                    }
                    $maxBoxQty = $candidateBox['max_shipbox_qty'] == 0 ? -1 : $candidateBox['max_shipbox_qty'];
                    $maxBoxQty = ($maxBoxQty > -1 && ($candidateBox['max_box'] < 1 || $maxBoxQty < $candidateBox['max_box']))
                        ? $maxBoxQty : $candidateBox['max_box'];
                } else {
                    $maxBoxQty = $candidateBox['max_box'];
                }
                $percentageFull = 0;
                $candidateBox['weighting'] = $this->getWeighting($maxBoxQty, $qty, $candidateBox['box_volume'], $weight, $maxBoxWeight, $percentageFull);
                $candidateBox['percentage_full'] = $percentageFull;
                $usableBoxes[] = $candidateBox;
            }
        }
    }

    static function cmp_single($a, $b)
    {

        $percFullA = $a['percentage_full'];
        $percFullB = $b['percentage_full'];

        if ($percFullB == 0 || $percFullA == 0 ||
            $percFullB == $percFullA
        ) {
            // compare based on weighting alone
            $weightingA = $a['weighting'];
            $weightingB = $b['weighting'];
            return $weightingA < $weightingB ? -1 : 1;
        }
        $ceilA = ceil($percFullA);
        $ceilB = ceil($percFullB);
        if ($ceilA == $ceilB) {
            // both have same box size, find the one that most fills the box
            return $percFullA > $percFullB ? -1 : 1;
        } else {
            return $percFullA < $percFullB ? -1 : 1;
        }


        /*if ($a['weighting'] == $b['weighting']) {
        return 0;
	    }
	    return ($a['weighting'] < $b['weighting']) ? -1 : 1;*/
    }

    static function cmp_multiple($a, $b)
    {
        if (count($a) == count($b)) {
            $percFullA = 0;
            $percFullB = 0;
            foreach ($a as $x) {
                $percFullA += $x['percentage_full'];
            }
            foreach ($b as $y) {
                $percFullB += $y['percentage_full'];
            }
            if ($percFullB == 0 || $percFullA == 0 ||
                $percFullB == $percFullA
            ) {
                // compare based on weighting alone
                $weightingA = 0;
                $weightingB = 0;
                foreach ($a as $x) {
                    $weightingA += $x['weighting'];
                }
                foreach ($b as $y) {
                    $weightingB += $y['weighting'];
                }
                return $weightingA < $weightingB ? -1 : 1;
            }
            $ceilA = ceil($percFullA);
            $ceilB = ceil($percFullB);
            if ($ceilA == $ceilB) {
                // both have same box size, find the one that most fills the box
                return $percFullA > $percFullB ? -1 : 1;
            } else {
                return $percFullA < $percFullB ? -1 : 1;
            }
        }
        return (count($a) > count($b)) ? -1 : 1;
    }


    /**
     * Weighting logic to decide what box is best
     * @param int $maxBoxQty
     * @param int $qty
     * @param int $boxVolume
     * @param int $weight
     * @param int $maxWeight
     * @param int $percentageFull
     * @return float
     */
    protected function getWeighting($maxBoxQty, $qty, $boxVolume, $weight, $maxWeight, &$percentageFull = 0)
    {

        $numBoxesRequired = $maxBoxQty == -1 ? 1 : ceil($qty / $maxBoxQty);
        $perBoxWeight = $numBoxesRequired == 0 ? 0 : $weight / $numBoxesRequired;
        $indWeight = $weight / $qty;

        if ($maxWeight > 0 && $maxWeight < $perBoxWeight) {
            if ($indWeight > $maxWeight) {
                $numBoxesRequired = ceil($qty / ($maxWeight / $indWeight)) * $numBoxesRequired;
            } else {
                $numBoxesRequired = ceil($qty / ($indWeight / $maxWeight)) * $numBoxesRequired;
            }
        }
        if ($maxWeight > 0) {
            $percentageFull = $weight / $maxWeight;
        }


        if ($maxBoxQty > 0) {
            $percentageFull = $percentageFull > ($qty / $maxBoxQty) ? $percentageFull : ($qty / $maxBoxQty);
        }

        $indBoxWeight = $numBoxesRequired == 0 ? 0 : $weight / $numBoxesRequired;
        // $weighting = ($boxVolume+1)/194 > $indBoxWeight ? (($boxVolume+1)/194)*$numBoxesRequired+($numBoxesRequired*5) : $weight+($numBoxesRequired*5);
        $weighting = ($boxVolume + 1) * $numBoxesRequired + ($numBoxesRequired * 5);

        return $weighting;
    }


    protected function processMultiple(&$itemDetails, $packNewBox)
    {

        if (Mage::helper('shipusa')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('usashipping', 'Multiple items', $itemDetails);
        }

        // find the common boxes
        $shipBoxesUsed = array();
        // lets find the closest match
        $found = false;
        foreach ($itemDetails as $key => $box) {
            // assume have single boxes for all items
            // if has no ship box id then process item as if standalone
            $qty = $box['qty'];
            $origQty = $box['orig_qty'];
            $weight = $box['weight'];
            $boxArray = $box[$this->_boxType];

            foreach ($boxArray as $candidateBox) {
                if (($candidateBox['max_qty'] == -1 || $candidateBox['max_qty'] >= $origQty) && $origQty >= $candidateBox['min_qty']) {
                    if ($candidateBox['box_id'] != 0) {
                        $maxBoxWeight = $candidateBox['max_shipbox_weight'];
                        if (!$box['split_product'] && $maxBoxWeight > 0 && $maxBoxWeight < ($box['weight'] / $qty)) {
                            continue;
                        }
                        $found = true;
                        $maxBoxQty = $candidateBox['max_shipbox_qty'] == 0 ? -1 : $candidateBox['max_shipbox_qty'];
                        $maxBoxQty = ($maxBoxQty > -1 && ($candidateBox['max_box'] < 1 || $maxBoxQty < $candidateBox['max_box']))
                            ? $maxBoxQty : $candidateBox['max_box'];
                        $percentageFull = 0;
                        $candidateBox['weighting'] = $this->getWeighting($maxBoxQty, $qty, $candidateBox['box_volume'],
                            $weight, $maxBoxWeight, $percentageFull);
                        $candidateBox['percentage_full'] = $percentageFull;
                        $shipBoxesUsed[$candidateBox['box_id']][$key] = $candidateBox;
                    }
                }
            }
            if (!$found) {
                if (Mage::helper('shipusa')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('usashipping', 'Unable to pack item with product sku:',
                        $box['sku']);
                }
                if (!$this->processSingle($itemDetails[$key], $packNewBox)) {
                    return false;
                } else {
                    unset($itemDetails[$key]);
                }
            }
        }

        if (Mage::helper('shipusa')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('usashipping', 'Possible ship boxes', $shipBoxesUsed);
        }

        switch (count($shipBoxesUsed)) {
            case 0:
                foreach ($itemDetails as $box) {
                    if (!$this->processSingle($box, $packNewBox)) {
                        return false;
                    }
                }
                return true;
                break;
            case 1:
                // all in 1 box
                $cutDownItemDetails = array();
                foreach ($shipBoxesUsed as $shipBox) {
                    foreach ($shipBox as $key => $itemBox) {
                        //$itemDetails[$key]['selected_box']=$itemBox;
                        $cutDownItemDetails[$key] = $itemDetails[$key];
                        $cutDownItemDetails[$key]['selected_box'] = $itemBox;
                        unset($itemDetails[$key]);
                    }
                }

                if (!$this->managePackingItems($cutDownItemDetails, $packNewBox, $itemDetails)) {
                    return false;
                }
                break;
            default:
                // more than 1
                // if have boxes which can hold more than 1 item then use these first
                usort($shipBoxesUsed, array($this, 'cmp_multiple'));
                foreach ($shipBoxesUsed as $shipBox) {
                    foreach ($shipBox as $key => $itemBox) {
                        //$itemDetails[$key]['selected_box']=$itemBox;
                        $cutDownItemDetails[$key] = $itemDetails[$key];
                        $cutDownItemDetails[$key]['selected_box'] = $itemBox;
                        // $cutDownItemDetails[$key]['perc_qty_per_item']=$itemBox['max_box'] > 0 ? (1/$itemBox['max_box'])*100 : -1;
                        unset($itemDetails[$key]);
                    }
                    break;
                }
                if (!$this->managePackingItems($cutDownItemDetails, $packNewBox, $itemDetails)) {
                    return false;
                }
                break;
        }

        //if (count($itemDetails)>0) {
        //	return $this->processMultiple($finishedBoxes, $itemDetails);
        //}

        return true;

    }

    protected function managePackingItems($itemDetails, $packNewBox, $unprocessedItemDetails = array())
    {

        $ableToPack = false;

        $savedItemDetails = $itemDetails;
        $itemCount = 0;

        //  usort($itemDetails,array($this, 'sort_items'));

        $fillingLeftOvers = false;
        $hasAddedItemsToBox = true;
        foreach ($itemDetails as $key => $itemDetail) {
            $itemCount++;

            $partialBox = null;

            if ($this->_isDifferentBox($itemDetail)) {
                // start a new box
                $packNewBox = true;
            }

            if (!$this->calculatePerBox($itemDetail, $ableToPack, $packNewBox, $partialBox)) {
                if ($fillingLeftOvers) {
                    continue; // cant pack in this box
                } else {
                    $hasAddedItemsToBox = false;
                    break; // cant pack the item and we arent on a partial box, need to re-assess
                }
            }
            $packNewBox = false;

            if (!$ableToPack && !$fillingLeftOvers) {
                // is not configured correctly, cant fit into available boxes. Default to weight based mechanism
                return false;
            }

            if (is_null($partialBox)) {
                // have packed this item in box
                unset($savedItemDetails[$key]);
            }


            if ($this->hasPartialItems($savedItemDetails, $key, $partialBox)) {
                // have some leftovers, lets see if anything else can fit
                $fillingLeftOvers = true;
            } else {
                // reassess boxes based on whats left
                // break;
            }
        }

        if (Mage::helper('shipusa')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('usashipping', 'Packed Boxes', $this->_tempPackedBoxes);
            Mage::helper('wsalogger/log')->postDebug('usashipping', 'Partial Box', $partialBox);
        }

        if ($hasAddedItemsToBox && (
                $this->_tempPackedBoxes[$this->_counter]['qty_left'] < 0 ||
                $this->_tempPackedBoxes[$this->_counter]['qty_left'] > 0) &&
            ($this->_tempPackedBoxes[$this->_counter]['weight_left'] == -1 ||
                $this->_tempPackedBoxes[$this->_counter]['weight_left'] > 0)
        ) {
            $createNewBox = false;
        } else {
            $createNewBox = true;
        }


        if (count($savedItemDetails) > 0 || count($unprocessedItemDetails) > 0) {
            return $this->processSingleBoxAlgorithm($savedItemDetails, $createNewBox, $unprocessedItemDetails);
        }

        return true;
    }

    protected function packFinishedBoxes(&$finishedBoxes)
    {
        foreach ($this->_tempPackedBoxes as $packedBox) {
            if (Mage::helper('shipusa')->getWeightCeil($packedBox['weight'] == 0)) {
                continue; // dont add items with a zero weight - shouldnt happen but is belt and braces approach
            }
            $finishedBoxes[] = array(
                'height' => Mage::helper('shipusa')->getWeightCeil($packedBox['height']),
                'width' => Mage::helper('shipusa')->getWeightCeil($packedBox['width']),
                'length' => Mage::helper('shipusa')->getWeightCeil($packedBox['length']),
                'weight' => Mage::helper('shipusa')->getWeightCeil($packedBox['weight'] + $packedBox['packing_weight']),
                'price' => Mage::helper('shipusa')->toTwoDecimals($packedBox['price']),
                'qty' => $packedBox['qty'],
                'handling_fee' => 0,
                'flat_box_id' => $packedBox['flat_box_id'],
                'flat_type' => $packedBox['flat_type'],
            );
        }
    }

    protected function _isDifferentBox($itemDetail)
    {
        if ($this->_counter == -1) {
            return true; // start of packing
        }
        $currentBox = $this->_tempPackedBoxes[$this->_counter];
        if ($itemDetail['selected_box']['box_id'] == 0 ||
            $currentBox['flat_box_id'] == 0 ||
            $itemDetail['selected_box']['box_id'] != $currentBox['flat_box_id']
        ) {
            return true;
        }
        return false;
    }

    /**
     * Will reassess the boxes required mid-item
     * @param $savedItemDetails
     * @param $key
     * @param $partialBox
     */
    protected function hasPartialItems(&$savedItemDetails, $key, $partialBox)
    {

        if (!is_null($partialBox) && $partialBox['qty_left'] > 0) {
            // still have part of a box empty, lets see if anything else can fit in here
            // changed savedItemDetails for this item with whats left to pack
            $savedItemDetails[$key]['qty'] = $partialBox['qty_left'];

            $savedItemDetails[$key]['weight'] = $partialBox['ind_weight'] * $partialBox['qty_left'];
            $savedItemDetails[$key]['price'] = $partialBox['ind_price'] * $partialBox['qty_left'];

            return true;
        }
        return false;
    }


    protected function calculatePerBox($itemDetail, &$ableToPack, $packNewBox, &$partialBox)
    {
        $box = $itemDetail['selected_box'];
        $newBox = false;

        if ($packNewBox) {
            $this->_counter++;
            $this->_tempPackedBoxes[$this->_counter] = $this->getNewPackedBox($box, $itemDetail['split_product']);
            $newBox = true;
        } elseif ($box['max_box'] != -99 && ($this->_tempPackedBoxes[$this->_counter]['qty_left'] == 0 ||
                $this->_tempPackedBoxes[$this->_counter]['weight_left'] == 0 ||
                $this->_tempPackedBoxes[$this->_counter]['volume_left'] == 0)
        ) {
            return false; // no space in the box
        } else {
            $qtyLeftForThisItem = $this->getPercQtyLeft($box);
            if ($qtyLeftForThisItem == 0) {
                return false; // move to the next item as cant fit this one
            } else {
                $this->_tempPackedBoxes[$this->_counter]['qty_left'] = $qtyLeftForThisItem;
            }
        }

        $savedQtyToPack = $itemDetail['qty'];
        $qtyToPack = $itemDetail['qty'];
        $splitProduct = $itemDetail['split_product'];
        $indWeight = $itemDetail['weight'] / $itemDetail['qty'];
        $indPrice = $itemDetail['price'] / $itemDetail['qty'];
        $ableToPack = true;

        // continue whilst can still pack
        while ($qtyToPack > 0 && $ableToPack) {
            $canPackItem = $this->packIndividualItem($indWeight,
                $indPrice, $qtyToPack, $box, $partialBox, $splitProduct);

            if (!$canPackItem) {
                if ($this->_tempPackedBoxes[$this->_counter]['weight'] == 0) {
                    $ableToPack = false;
                } else {
                    // have we packed anything is the question now?

                    if (!$newBox && $savedQtyToPack == $qtyToPack) {
                        // nothing has changed, need a new box
                        return false;
                    }

                    if (is_null($partialBox)) {
                        // re-assess box
                        $partialBox = $this->getPartialBox($indWeight, $indPrice, $qtyToPack);
                    } else {
                        break; // we know we can't do anything with this item, so need to re-assess
                    }
                }
            }
        }
        return true;
    }


    protected function calculateAndPackBox(&$qtyToPack, $maxPackQty,
                                           $indWeight, $indPrice, $box, &$partialBox, $splitProduct)
    {

        if ($splitProduct) {
            $qtyToAddNow = ($this->_tempPackedBoxes[$this->_counter]['qty_left'] > 0 && $maxPackQty > $this->_tempPackedBoxes[$this->_counter]['qty_left']) ?
                $this->_tempPackedBoxes[$this->_counter]['qty_left'] : $maxPackQty;
        } else {
            $qtyToAddNow = ($this->_tempPackedBoxes[$this->_counter]['qty_left'] > 0 && $maxPackQty > $this->_tempPackedBoxes[$this->_counter]['qty_left']) ?
                floor($this->_tempPackedBoxes[$this->_counter]['qty_left']) : floor($maxPackQty);
        }


        if ($qtyToAddNow > $qtyToPack) {
            $qtyToAddNow = $qtyToPack;
        }
        $qtyToPack -= $qtyToAddNow;
        if ($qtyToAddNow <= 0) {
            // cant fit this item in the box
            if (Mage::helper('shipusa')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('usashipping', 'Unable to finish packing', $this->_tempPackedBoxes);
            }
            return false;
        }
        $this->finalPack($indWeight, $indPrice, $qtyToAddNow, $box);
        if ($qtyToPack > 0) {
            $partialBox = $this->getPartialBox($indWeight, $indPrice, $qtyToPack); // this is wrong as isnt taking into account the qty left etc.
        } else {
            return true;
        }
    }


    /**
     * Get remaining item left over
     * @param $indWeight
     * @param $indPrice
     * @param $qtyToPack
     * @return array
     */
    protected function getPartialBox($indWeight, $indPrice, $qtyToPack)
    {

        return array(
            'ind_weight' => $indWeight,
            'ind_price' => $indPrice,
            'qty_left' => $qtyToPack,
        );
    }

    protected function getNewPackedBox($box, $splitProduct)
    {
        if (!array_key_exists('box_type', $box)) {
            $boxType = 4;
        } else {
            $boxType = $box['box_type'];
        }
        return array(
            'length' => $box['length'],
            'width' => $box['width'],
            'height' => $box['height'],
            'weight' => 0,
            'price' => 0,
            'qty' => 0,
            'qty_left' => $this->getQtyLeft(0, $box),
            'perc_qty_per_item' => $box['perc_qty_per_item'],
            'weight_left' => $box['max_shipbox_weight'] == 0 ? -1 : $box['max_shipbox_weight'],
            'volume_left' => $box['box_volume'] == 0 ? -1 : $box['box_volume'],
            'split_product' => $splitProduct,
            'handling_fee' => 0,
            'packing_weight' => $box['packing_weight'] > 0 ? $box['packing_weight'] : 0,
            'flat_box_id' => $box['box_id'],
            'flat_type' => $boxType,
        );
    }


    /**
     * Work out the qty left in the box
     * @param $currQtyLeft
     * @param $box
     * @return int
     */
    protected function getQtyLeft($currQtyLeft, $box)
    {

        if ($currQtyLeft != 0 && $box['max_shipbox_qty'] > 0) {
            $maxBoxQty = $currQtyLeft;
        } else {
            $maxBoxQty = $box['max_shipbox_qty'] == 0 ? -1 : $box['max_shipbox_qty'];
        }

        return ($maxBoxQty > -1 && ($box['max_box'] < 1 || $maxBoxQty < $box['max_box']))
            ? $maxBoxQty : $box['max_box'];
    }

    protected function getPercQtyLeft($box)
    {
        $qtyLeft = $this->_tempPackedBoxes[$this->_counter]['qty_left'];
        if ($qtyLeft == 0) {
            return $this->getQtyLeft(
                0,
                $box);
        }
        // this is a partial box, so we now need to work out what is left in the context of the box


        $percBoxLeft = $this->_tempPackedBoxes[$this->_counter]['perc_qty_left'];
        $itemPercQty = $box['perc_qty_per_item'];

        if ($itemPercQty < 0) {
            return $this->getQtyLeft(
                $this->_tempPackedBoxes[$this->_counter]['qty_left'],
                $box);
        }

        $maxBoxQty = floor($percBoxLeft / $itemPercQty);

        $qtyLeft = $maxBoxQty < $qtyLeft ? $maxBoxQty : $qtyLeft;

        if ($qtyLeft < 1) {
            return 0;
        }


        return $this->getQtyLeft(
            $qtyLeft,
            $box);

    }


    protected function packIndividualItem($indWeight, $indPrice, &$qtyToPack, $box, &$partialBox, $splitProduct)
    {

        if ($qtyToPack <= 0) {
            return true;
        }

        $qtyOk = false;
        $volOk = false;
        $weightOk = false;

        if ($this->_tempPackedBoxes[$this->_counter]['qty_left'] < 0 || $this->_tempPackedBoxes[$this->_counter]['qty_left'] >= $qtyToPack) {
            $qtyOk = true;
        }
        if ($box['item_volume'] <= 0 || abs($this->_tempPackedBoxes[$this->_counter]['volume_left'] - $qtyToPack * $box['item_volume']) < self::EPSILON) {
            $volOk = true;
        }
        if ($this->_tempPackedBoxes[$this->_counter]['weight_left'] == -1 || abs($this->_tempPackedBoxes[$this->_counter]['weight_left'] - $indWeight * $qtyToPack) < self::EPSILON) {
            $weightOk = true;
        }

        if ($qtyOk) {
            if ($volOk) {
                if ($weightOk) {
                    // dont have to worry about weights - much easier, pack it all
                    $this->finalPack($indWeight, $indPrice, $qtyToPack, $box);
                    $qtyToPack = 0;
                } else {
                    return $this->calculateAndPackWeightBox($qtyToPack, $indWeight, $indPrice, $box, $partialBox, $splitProduct);
                }
            } else {
                if ($weightOk) {
                    // weight isnt an issue, just volume, very simple
                    $maxPackVolQty = floor(
                        (round(($this->_tempPackedBoxes[$this->_counter]['volume_left'] / $box['item_volume']) + self::EPSILON, 2)));
                    return $this->calculateAndPackBox($qtyToPack, $maxPackVolQty, $indWeight, $indPrice, $box, $partialBox, $splitProduct);
                } else {
                    return $this->calculateAndPackWeightBox($qtyToPack, $indWeight, $indPrice, $box, $partialBox, $splitProduct);

                }
            }
        } else {
            if ($volOk) {
                if ($weightOk) {
                    return $this->calculateAndPackBox($qtyToPack, $this->_tempPackedBoxes[$this->_counter]['qty_left'], $indWeight, $indPrice, $box, $partialBox, $splitProduct);
                } else {
                    return $this->calculateAndPackWeightBox($qtyToPack, $indWeight, $indPrice, $box, $partialBox, $splitProduct);
                }
            } else {
                if ($weightOk) {
                    // qty and vol an issue

                    //$maxPackVolQty = floor(($this->_tempPackedBoxes[$this->_counter]['volume_left'] / $box['item_volume'])+self::EPSILON);
                    $maxPackVolQty = floor(
                        (round(($this->_tempPackedBoxes[$this->_counter]['volume_left'] / $box['item_volume']) + self::EPSILON, 2)));
                    return $this->calculateAndPackBox($qtyToPack, $maxPackVolQty, $indWeight, $indPrice, $box, $partialBox, $splitProduct);
                } else {
                    return $this->calculateAndPackWeightBox($qtyToPack, $indWeight, $indPrice, $box, $partialBox, $splitProduct);
                }
            }
        }

        return true;
    }

    protected function calculateAndPackWeightBox(&$qtyToPack, $indWeight, $indPrice, $box, &$partialBox, $splitProduct)
    {
        // have a max weight which pushes over the box
        $maxPackQty = floor(
            (round(($this->_tempPackedBoxes[$this->_counter]['weight_left'] / $indWeight) + self::EPSILON, 2)));
        if ($maxPackQty == 0) {
            // individual item is heaving than max weight of box
            $maxPackQty = ($this->_tempPackedBoxes[$this->_counter]['weight_left'] / $indWeight) + self::EPSILON;
        }
        if ($box['item_volume'] > 0) {
            $maxPackVolQty = floor(
                (round(($this->_tempPackedBoxes[$this->_counter]['volume_left'] / $box['item_volume']) + self::EPSILON, 2)));
            $maxPackQty = $maxPackVolQty > $maxPackQty ? $maxPackQty : $maxPackVolQty;
        }
        return $this->calculateAndPackBox($qtyToPack, $maxPackQty, $indWeight, $indPrice, $box, $partialBox, $splitProduct);

    }

    protected function finalPack($indWeight, $indPrice, $qty, $box)
    {
        $this->_tempPackedBoxes[$this->_counter]['qty'] += $qty;
        $this->_tempPackedBoxes[$this->_counter]['weight'] += $indWeight * $qty;
        $this->_tempPackedBoxes[$this->_counter]['price'] += $indPrice * $qty;
        $this->_tempPackedBoxes[$this->_counter]['perc_qty_left'] = 0;
        if ($this->_tempPackedBoxes[$this->_counter]['qty_left'] > 0) {
            $this->_tempPackedBoxes[$this->_counter]['qty_left'] -= $qty;
            $this->_tempPackedBoxes[$this->_counter]['perc_qty_left'] = 100 - $this->_tempPackedBoxes[$this->_counter]['perc_qty_per_item'] * $qty;
        }
        if ($this->_tempPackedBoxes[$this->_counter]['weight_left'] > 0) {
            $this->_tempPackedBoxes[$this->_counter]['weight_left'] -= $indWeight * $qty;
        }
        if ($this->_tempPackedBoxes[$this->_counter]['volume_left'] > 0) {
            $this->_tempPackedBoxes[$this->_counter]['volume_left'] -= $qty * $box['item_volume'];
        }
    }


}