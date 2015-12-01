<?php
/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @copyright  Copyright (c) 2014 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Advanced Product Options extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @author     MageWorx Dev Team
 */

class MageWorx_CustomOptions_Model_Catalog_Product_Option_Type_Select extends Mage_Catalog_Model_Product_Option_Type_Select {
    
    public function getOptionPrice($valueIds, $basePrice, $qty = 1, $optionQtyArr = 1, $product = null) {
        if (is_null($product)) return parent::getOptionPrice($valueIds, $basePrice);
        
        $option = $this->getOption();
        $helper = Mage::helper('customoptions');
        $result = 0;

        if (!$this->_isSingleSelection()) {
            $valueIds = explode(',', $valueIds);
            foreach($valueIds as $valueId) {
                if ($value = $option->getValueById($valueId)) {                    
                    $optionQty = (!is_array($optionQtyArr)?$optionQtyArr:$optionQtyArr[$valueId]);
                    if ($option->getCustomoptionsIsOnetime()) $optionTotalQty =  $optionQty; else $optionTotalQty = $optionQty * $qty;
                    
                    // calculate option price
                    $price = $helper->getOptionPriceByQty($value, $optionTotalQty, $product);
                    if ($price!=0) $price = $price / $qty;
                    $result += $price;
                } else {
                    if ($this->getListener()) {
                        $this->getListener()
                                ->setHasError(true)
                                ->setMessage(
                                    Mage::helper('catalog')->__('Some of the products below do not have all the required options. Please remove them and add again with all the required options.')
                                );
                        break;
                    }
                }
            }
        } elseif ($this->_isSingleSelection()) {
            $optionQty = $optionQtyArr;
            if ($value = $option->getValueById($valueIds)) {
                if ($option->getCustomoptionsIsOnetime()) $optionTotalQty =  $optionQty; else $optionTotalQty = $optionQty * $qty;
                // calculate option price
                $price = $helper->getOptionPriceByQty($value, $optionTotalQty, $product);
                if ($price!=0) $price = $price / $qty;
                $result += $price;
                
            } else {
                if ($this->getListener()) {
                    $this->getListener()
                            ->setHasError(true)
                            ->setMessage(
                                Mage::helper('catalog')->__('Some of the products below do not have all the required options. Please remove them and add again with all the required options.')
                            );
                }
            }
        }

        return $result;
    }
    
    protected function _isSingleSelection() {
        $_single = array(
            Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN,
            Mage_Catalog_Model_Product_Option::OPTION_TYPE_RADIO,
            MageWorx_CustomOptions_Model_Catalog_Product_Option::OPTION_TYPE_SWATCH
        );
        return in_array($this->getOption()->getType(), $_single);
    }

}