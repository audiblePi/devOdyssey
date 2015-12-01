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
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@mageworx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 * or send an email to sales@mageworx.com
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @copyright  Copyright (c) 2009 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Custom Options extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */
class MageWorx_CustomOptions_Model_Relation extends Mage_Core_Model_Abstract {

    protected function _construct() {
        parent::_construct();
        $this->_init('customoptions/relation');
    }

    public function changeOptionsStatus(Varien_Object $group) {
        $optionIds = $this->getResource()->getOptionIds($group->getId());
        if ($optionIds) {
            foreach ($optionIds as $id) {
                $this->getResource()->setOptionsStatus($id, $group->getIsActive());
            }
        }
    }

    public function changeHasOptions(array $groups) {
        if ($groups) {
            $productOptionModel = Mage::getModel('catalog/product_option');
            foreach ($groups as $groupId => $group) {
                $productIds = $this->getResource()->getProductIds($groupId);
                if ($productIds) {
                    foreach ($productIds as $productId) {
                        $productOptionModel->updateProductFlags($productId, $group->getAbsolutePrice(), $group->getAbsoluteWeight(), $group->getSkuPolicy());
                    }
                }
            }
        }
    }

}
