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
 * @copyright  Copyright (c) 2013 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Advanced Product Options extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @author     MageWorx Dev Team
 */
class MageWorx_CustomOptions_Model_Catalog_Product_Type_Price extends MageWorx_CustomOptions_Model_Catalog_Product_Type_Price_Abstract {

    /**
     * Apply options price
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $qty
     * @param double $finalPrice
     * @return double
     */         
     
    protected function _applyOptionsPrice($product, $qty, $finalPrice) {
        if ($optionIds = $product->getCustomOption('option_ids')) {
            $helper = Mage::helper('customoptions');
            $basePrice = $finalPrice;
            $product->setActualPrice($basePrice);
            $finalPrice = 0;
            $post = $helper->getInfoBuyRequest($product);
            
            foreach (explode(',', $optionIds->getValue()) as $optionId) {
                if ($option = $product->getOptionById($optionId)) {
                    $option->setProduct($product);
                    $optionQty = 1;                    
                    switch ($option->getType()) {
                        case 'checkbox':
                        case 'multiswatch':
                        case 'hidden':
                            if (isset($post['options'][$optionId])) {                                                                
                                $optionValues = array();
                                $optionQtyArr = array();
                                foreach ($option->getValues() as $key=>$itemV) {                                    
                                    if (isset($post['options_'.$optionId.'_'.$itemV->getOptionTypeId().'_qty'])) $optionQty = intval($post['options_'.$optionId.'_'.$itemV->getOptionTypeId().'_qty']);
                                    $optionQtyArr[$itemV->getOptionTypeId()] = $optionQty;
                                }
                                $optionQty = $optionQtyArr;                                
                            }
                            break;
                        case 'drop_down':
                        case 'radio':
                        case 'swatch':    
                            if (isset($post['options_'.$optionId.'_qty'])) $optionQty = intval($post['options_'.$optionId.'_qty']);
                            break;
                    }

                    if ($option->getGroupByType()==Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) {
                        $quoteItemOption = $product->getCustomOption('option_' . $option->getId());
                        $group = $option->groupFactory($option->getType())->setOption($option)->setQuoteItemOption($quoteItemOption);
                        $finalPrice += $group->getOptionPrice($quoteItemOption->getValue(), $basePrice, $qty, $optionQty, $product);
                    } else {
                        $price = $helper->getOptionPriceByQty($option, $qty, $product);
                        if ($price!=0) $price = $price / $qty;
                        $finalPrice += $price;
                    }
                }
            }
            $product->setBaseCustomoptionsPrice($finalPrice); // for additional info
            if (!$helper->getProductAbsolutePrice($product) || $finalPrice==0) $finalPrice += $basePrice;
        }        
        if (method_exists($this, '_applyOptionsPriceFME')) $finalPrice = $this->_applyOptionsPriceFME($product, $qty, $finalPrice);
        return $finalPrice;
    }

}