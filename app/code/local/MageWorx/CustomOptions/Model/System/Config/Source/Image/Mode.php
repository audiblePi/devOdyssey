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

class MageWorx_CustomOptions_Model_System_Config_Source_Image_Mode {
    public function toOptionArray() {
        $helper = Mage::helper('customoptions');
        return array(
            array('value' => 1, 'label'=>$helper->__('Beside Option')),
            array('value' => 2, 'label'=>$helper->__('Replace Product Gallery')),
            array('value' => 3, 'label'=>$helper->__('Append to Product Gallery')),
            array('value' => 4, 'label'=>$helper->__('Overlay'))
        );
    }

}