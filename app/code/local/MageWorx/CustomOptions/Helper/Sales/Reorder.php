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

class MageWorx_CustomOptions_Helper_Sales_Reorder extends Mage_Sales_Helper_Reorder {
    
    public function canReorder(Mage_Sales_Model_Order $order) {
        
        $helper = Mage::helper('customoptions');
        
        if (!$helper->isEnabled() || !$helper->isOptionSkuPolicyEnabled()) return parent::canReorder($order);
        
        if (!$this->isAllow()) return false;
        
        
        // copy from $order->canReorder():
        if ($order->canUnhold() || $order->isPaymentReview() || !$order->getCustomerId()) {
            return false;
        }
        
        $products = array();
        foreach ($order->getItemsCollection() as $item) {
            $products[] = $item->getProductId();
        }
        
        if (!empty($products)) {
            foreach ($products as $productId) {
                if ($productId==0) continue;
                $product = Mage::getModel('catalog/product')
                    ->setStoreId($order->getStoreId())
                    ->load($productId);
                if (!$product->getId() || !$product->isSalable()) {
                    return false;
                }
            }
        }
        
        if ($order->getActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_REORDER) === false) {
            return false;
        }

        return true;
    }
    
}