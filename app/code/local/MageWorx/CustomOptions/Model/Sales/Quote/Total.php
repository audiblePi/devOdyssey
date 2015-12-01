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

class MageWorx_CustomOptions_Model_Sales_Quote_Total extends Mage_Tax_Model_Sales_Total_Quote_Subtotal
{

    public function __construct() {
        parent::__construct();
        $this->setCode('customoptions');
    }
    
    public function collect(Mage_Sales_Model_Quote_Address $address) {
        $helper = Mage::helper('customoptions');
        if (!$helper->isEnabled() || !$helper->isSkuPriceLinkingEnabled()) return $this;
        if ($address->getSubtotal()==0) return $this;
        
        $this->_address = $address;
        $this->_store = $address->getQuote()->getStore();
        $this->_config = Mage::getSingleton('tax/config');
        $this->_helper = Mage::helper('tax');    
        
        if (method_exists($this, '_getAddressItems')) {
            $items = $this->_getAddressItems($address);
        } else {
            $items  = $address->getAllItems();
        }
        
        if (!$items) return $this;
        
        $quote = $address->getQuote();
        
        $address->setSubtotalInclTax(0);
        $address->setBaseSubtotalInclTax(0);
        $address->setTotalAmount('subtotal', 0);
        $address->setBaseTotalAmount('subtotal', 0);
        
        $totalDiffTax = 0;
        foreach ($items as $item) {
            $diffTax = 0;
            $product = $item->getProduct();
            // if bad magento))
            if (is_null($product->getHasOptions())) $product->load($product->getId());
            
            if (($product->getTypeId()=='simple' || $product->getTypeId()=='configurable' || $product->getTypeId()=='virtual' || $product->getTypeId()=='downloadable') && $product->getHasOptions()) {
                $store = $product->getStore();
                $post = $helper->getInfoBuyRequest($product);
                if (isset($post['options'])) $options = $post['options']; else $options = array();

                $qty = $item->getQty();

                if ($options) {
                    $priceIncludesTax = Mage::helper('tax')->priceIncludesTax($store);
                    
                    foreach ($options as $optionId => $option) {
                        $optionModel = $product->getOptionById($optionId);
                        if (!$optionModel) continue;
                        $optionModel->setProduct($product);

                        $optionType = $optionModel->getType();
                        
                        if ($optionModel->getGroupByType($optionType)!=Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) {
                            if (!$optionModel->getIsSkuPrice()) continue;

                            $taxClassId = $optionModel->getTaxClassId();
                            if (is_null($taxClassId) || $product->getTaxClassId()==$taxClassId || $priceIncludesTax) continue;

                            $optionTotalQty = ($optionModel->getCustomoptionsIsOnetime()?1:$qty);
                            $price = $helper->getOptionPriceByQty($optionModel, $optionTotalQty, $product);

                            $tax1 = $helper->getTaxPrice($price, $quote, $product->getTaxClassId(), $address);
                            $tax2 = $helper->getTaxPrice($price, $quote, $taxClassId, $address);
                            $diffTax += $tax2 - $tax1;
                        } else {
                            if (!is_array($option)) $option = array($option);
                            foreach ($option as $optionTypeId) {
                                if (!$optionTypeId) continue;
                                $valueModel = $optionModel->getValueById($optionTypeId);

                                if (!$valueModel || !$valueModel->getIsSkuPrice()) continue;
                                $taxClassId = $valueModel->getTaxClassId();
                                if (is_null($taxClassId) || $product->getTaxClassId()==$taxClassId || $priceIncludesTax) continue;

                                // get total option qty
                                switch ($optionType) {
                                    case 'checkbox':
                                    case 'multiswatch':
                                    case 'hidden':
                                        if (isset($post['options_'.$optionId.'_'.$optionTypeId.'_qty'])) $optionQty = intval($post['options_'.$optionId.'_'.$optionTypeId.'_qty']); else $optionQty = 1;
                                        break;
                                    case 'drop_down':
                                    case 'radio':
                                    case 'swatch':    
                                        if (isset($post['options_'.$optionId.'_qty'])) $optionQty = intval($post['options_'.$optionId.'_qty']); else $optionQty = 1;
                                        break;
                                    case 'multiple':
                                        $optionQty = 1;
                                        break;
                                }
                                $optionTotalQty = ($optionModel->getCustomoptionsIsOnetime()?$optionQty:$optionQty*$qty);
                                $price = $helper->getOptionPriceByQty($valueModel, $optionTotalQty, $product);

                                $tax1 = $helper->getTaxPrice($price, $quote, $product->getTaxClassId(), $address);
                                $tax2 = $helper->getTaxPrice($price, $quote, $taxClassId, $address);

                                $diffTax += $tax2 - $tax1;
                            }   
                        }
                    }
                    if ($diffTax) {
                        // convert basePrice - to price
                        $storeDiffTax = $store->convertPrice($diffTax, false, false);

                        $item->setBasePriceInclTax($item->getBasePriceInclTax() + ($diffTax / $qty)); // $diffTax/produt qty
                        $item->setPriceInclTax($item->getPriceInclTax() + ($storeDiffTax / $qty)); // $diffTax/produt qty

                        $item->setBaseRowTotalInclTax($item->getBaseRowTotalInclTax() + $diffTax);
                        $item->setRowTotalInclTax($item->getRowTotalInclTax() + $storeDiffTax);
                        
                        $item->setBaseTaxAmount($item->getBaseTaxAmount() + $diffTax);
                        $item->setTaxAmount($item->getTaxAmount() + $storeDiffTax);
                        
                        $totalDiffTax += $diffTax;
                    }
                }
            }
            if (!$item->getParentItemId()) $this->_addSubtotalAmount($address, $item);
        }
        
        if ($totalDiffTax) {
            $totalStoreDiffTax = $store->convertPrice($totalDiffTax, false, false);
            
            $address->setBaseTaxAmount($address->getBaseTaxAmount() + $totalDiffTax);
            $address->setTaxAmount($address->getTaxAmount() + $totalStoreDiffTax);  

            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $totalDiffTax);
            $address->setGrandTotal($address->getGrandTotal() + $totalStoreDiffTax);
        }
        
        return $this;
    }
}