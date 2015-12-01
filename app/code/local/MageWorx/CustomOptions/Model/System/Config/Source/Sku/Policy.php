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

class MageWorx_Customoptions_Model_System_Config_Source_Sku_Policy {
    // $mode = 0 - with no Use Config, 1 - all, 2 - with no Grouped, 3 - only Use Config
    public function toOptionArray($mode = 0) {
        $helper = Mage::helper('customoptions');
        $options = array(
            array('value' => 0, 'label' => $helper->__('Use Config')),
            array('value' => 1, 'label' => $helper->__('Standard')),
            array('value' => 2, 'label' => $helper->__('Independent')),
            array('value' => 3, 'label' => $helper->__('Grouped')),
            array('value' => 4, 'label' => $helper->__('Replacement')),
        );        
        if ($mode==0) unset($options[0]); // remove Use Config
        if ($mode==2) unset($options[count($options)-2]); // remove Grouped
        if ($mode==3) $options = array($options[0]);
        return $options;
    }

}