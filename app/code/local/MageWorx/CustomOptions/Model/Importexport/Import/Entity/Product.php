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

if (version_compare(Mage::helper('customoptions')->getMagetoVersion(), '1.8.0', '<')) {
    class MageWorx_CustomOptions_Model_Importexport_Import_Entity_Product extends MageWorx_CustomOptions_Model_Importexport_Import_Entity_Product_M1700 {}
} else {
    class MageWorx_CustomOptions_Model_Importexport_Import_Entity_Product extends MageWorx_CustomOptions_Model_Importexport_Import_Entity_Product_M1800 {}
}