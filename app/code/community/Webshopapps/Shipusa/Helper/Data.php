<?php
/* Dimensional Shipping
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */

/****
 * Helper Methods
 **/
class Webshopapps_Shipusa_Helper_Data extends Mage_Core_Helper_Abstract
{

	protected static $_debug;
	protected static $_handlingProductInstalled;
	protected static $_handlingProdModel = NULL;
	protected static $_shipAllSeparate;
	protected static $_wholeWeightRounding;
    protected static $_isExactPackingAlgorithm;
    protected static $_isVolumePackingAlgorithm;
    protected static $_itemCount = 0;

    protected static $_quotesCache = array();


    public static function isDebug() {
		if (self::$_debug==NULL) {
			self::$_debug = Mage::helper('wsalogger')->isDebug('Webshopapps_Shipusa');
		}
		return self::$_debug;
	}

    /**
     * Returns cache key for some request to carrier quotes service
     *
     * @param $items
     * @param $uspsFlatBoxes
     * @internal param array|string $requestParams
     * @return string
     */
    protected static function _getQuotesCacheKey($items,$uspsFlatBoxes)
    {
        $implodedItems = $uspsFlatBoxes.',';
        foreach ($items as $item) {
            $implodedItems .=$item->getSku().',';
        }
        return crc32($implodedItems);
    }

    /**
     * Returns cached response or null
     *
     * @param string|array $items
     * @param              $uspsFlatBoxes
     * @return null|string
     */
    protected static function _getCachedQuotes($items,$uspsFlatBoxes)
    {
        $key = self::_getQuotesCacheKey($items,$uspsFlatBoxes);
        return isset(self::$_quotesCache[$key]) ? self::$_quotesCache[$key] : null;
    }

    /**
     * Sets received carrier quotes to cache
     *
     * @param string|array $items
     * @param string       $boxes
     * @param              $uspsFlatBoxes
     * @return void
     */
    protected static function _setCachedQuotes($items, $boxes,$uspsFlatBoxes)
    {
        $key = self::_getQuotesCacheKey($items,$uspsFlatBoxes);
        self::$_quotesCache[$key] = $boxes;
        return;
    }



    /**
     * Calculates the std packages and returns as a static
     * This is done so is not recalulated for each session
     *
     * @param $items
     * @param $ignoreFreeItems
     * @return mixed
     */
    public static function getStdBoxes($items,$ignoreFreeItems) {

        return self::getBoxes($items,$ignoreFreeItems,false);

    }

    /**
     * Calculates the flat packages and returns as a static
     * This is done so is not recalulated for each session
     *
     * @param $items
     * @param $ignoreFreeItems
     * @return mixed
     */
    public static function getFlatBoxes($items,$ignoreFreeItems) {
        return self::getBoxes($items,$ignoreFreeItems,true);
    }

    protected static function getBoxes ($items,$ignoreFreeItems,$uspsFlatBoxes = false) {
        $boxes = self::_getCachedQuotes($items,$uspsFlatBoxes);
        if ($boxes === null || $ignoreFreeItems) {
            $boxes = Mage::getSingleton('shipusa/dimcalculate')->getBoxes($items,$ignoreFreeItems,$uspsFlatBoxes);
            self::_setCachedQuotes($items, $boxes,$uspsFlatBoxes);
        }
        return $boxes;
    }

	public static function shipAllSeparate() {

		if (self::$_shipAllSeparate==NULL) {
			self::$_shipAllSeparate = Mage::getStoreConfig('shipping/shipusa/ship_separate');
		}
		return self::$_shipAllSeparate;

	}

	public static function isExactPackingAlgorithm() {

		if (self::$_isExactPackingAlgorithm==NULL) {
			self::$_isExactPackingAlgorithm =
                (Mage::getStoreConfig('shipping/shipusa/packing_algorithm')=='exact_packing') ? true : false;
		}
		return self::$_isExactPackingAlgorithm;

	}

    public static function isVolumePackingAlgorithm() {

        if (self::$_isVolumePackingAlgorithm==NULL) {
            self::$_isVolumePackingAlgorithm =
                (Mage::getStoreConfig('shipping/shipusa/packing_algorithm')=='volume_packing') ? true : false;
        }
        return self::$_isVolumePackingAlgorithm;

    }

	public static function isWholeWeightRounding() {
		if (self::$_wholeWeightRounding==NULL) {
			self::$_wholeWeightRounding = Mage::getStoreConfig('shipping/shipusa/whole_weight');
		}
		return self::$_wholeWeightRounding;
	}

	public static function isHandlingProdInstalled() {
		if (self::$_handlingProductInstalled==NULL ) {
			self::$_handlingProductInstalled = Mage::helper('wsacommon')
				->isModuleEnabled('Webshopapps_Handlingproduct','shipping/handlingproduct/active') ? true : false;
			if (self::$_handlingProductInstalled) {
				self::$_handlingProdModel=Mage::getModel('handlingproduct/handlingproduct');
			}

		}
		return self::$_handlingProductInstalled;
	}

	public static function getHandlingProductModel() {
		return self::$_handlingProdModel;
	}

	public function getWeightCeil($weight) {
        if(floor($weight) == $weight) {
            return floor($weight);
        }

		if ($this->isWholeWeightRounding()) {
			return ceil(round($weight,2));
		}	else {
			return round($weight,2);
		}
	}

    /**
     * Simple function to round a value to two significant figures
     *
     * @param int $value The value to be rounded
     * @return float
     */
	public function toTwoDecimals($value=-1) {
		return round($value,2);		// changed from ceil as worried about above causing an issue
	}

  	public function percentageOverflow($maxQtyPerBox,$qty,$percentageFull=0) {
    	return $this->getPercentageFull($maxQtyPerBox,$qty,$percentageFull) > 100 ? true : false;
    }

    public function formatXML($xmlString) {

        try {
            $simpleXml = new SimpleXMLElement($xmlString);
            $dom = new DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($simpleXml->asXML());
            return $dom->saveXML();
        } catch (Exception $e) {
            return $xmlString;
        }

    }

    public function getPercentageFull($maxQtyPerBox,$qty,$percentageFull=0) {
    	 if (is_numeric($maxQtyPerBox) && $maxQtyPerBox>=1) {
    		$indItemPercentage = 100/$maxQtyPerBox;
    		$newPercentageFull = ($indItemPercentage*$qty) + $percentageFull;
    		return $newPercentageFull;
    	 } else {
    	 	return $percentageFull;
    	 }
    }

	public function getPercentageQtyLeft($maxQtyPerBox,$qty,$percentageFull=0) {
    	$indItemPercentage = 100/$maxQtyPerBox;
    	$percentageLeft = 100- $percentageFull;

    	$allowedQty = ceil($percentageLeft/$indItemPercentage);

    	return $allowedQty;
    }

    public function getBoxId($length, $width, $height) {
    	$dimensions = array($length, $width, $height);
    	sort($dimensions);
    	return $dimensions[0].'_'.$dimensions[1].'_'.$dimensions[2];
    }

    public function calculateBoxVolume($length,$height,$width) {

        $volume = $length*$height*$width;

        return $volume > 0 ? $volume : 10;
    }

    /**
     * Given the box type ID, determines the boxes weighting in comparison to other boxes
     *
     * @param $boxTypeId
     * @return int
     */
    public function getUspsBoxWeighting($boxTypeId)
    {
        switch ($boxTypeId) {
            case 1: return 5; break; //SM FLAT RATE BOX
            case 2: return 6; break; //MD FLAT RATE BOX
            case 3: return 7; break; //LG FLAT RATE BOX
          //case 4: return 1; break; Reserved
            case 5: return 3; break; //PADDED FLAT RATE ENVELOPE
            case 6: return 1; break; //SM FLAT RATE ENVELOPE
            case 7: return 4; break; //LEGAL FLAT RATE ENVELOPE
            case 8: return 2; break; //FLAT RATE ENVELOPE

            default: return 6;
        }
    }
}
