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
class MageWorx_CustomOptions_Model_Catalog_Product_Option_Image {
    
    protected $_imageFile = '';
    protected $_width = 70;
    protected $_height = 70;
    
    public function init($imageFile) {
        $this->_imageFile = $imageFile;
        return $this;
    }
    
    public function resize($width, $height = null) {
        $this->_width = $width;
        $this->_height = $height;        
        return $this;
    }
    
    public function setWatermarkSize($size) {
        return $this;
    }
    
    public function __toString() {
        $imgData = Mage::helper('customoptions')->getImgData($this->_imageFile, false, false, $this->_width);
        if (!isset($imgData['url'])) return '';
        return $imgData['url'];
    }
    
    public function constrainOnly($flag) {
        $this->_constrainOnly = $flag;
        return $this;
    }
    
    public function keepAspectRatio($flag) {
        $this->_keepAspectRatio = $flag;
        return $this;
    }
    
    public function keepFrame($flag, $position = array('center', 'middle')) {
        $this->_keepFrame = $flag;
        return $this;
    }

}