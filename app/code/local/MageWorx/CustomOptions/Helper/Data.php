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

class MageWorx_CustomOptions_Helper_Data extends Mage_Core_Helper_Abstract {
    const STATUS_VISIBLE = 1;
    const STATUS_HIDDEN = 2;

    const XML_PATH_CUSTOMOPTIONS_ENABLE_QNTY_INPUT = 'mageworx_catalog/customoptions/enable_qnty_input';
    const XML_PATH_CUSTOMOPTIONS_DISPLAY_QTY_FOR_OPTIONS = 'mageworx_catalog/customoptions/display_qty_for_options';
    
    public function isEnabled() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/enabled');
    }
    
    public function isDependentEnabled() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/dependent_enabled');
    }
    
    public function hideDependentOption() {
        if (!$this->isDependentEnabled()) return false;
        return Mage::getStoreConfig('mageworx_catalog/customoptions/hide_dependent_option');
    }
       
    public function isWeightEnabled() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/weight_enabled');
    }
    
    public function isCostEnabled() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/cost_enabled');
    }
    
    public function isPricePrefixEnabled() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/price_prefix_enabled');
    }
    
    public function isSpecialPriceEnabled() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/special_price_enabled');
    }
    
    public function isSpecialPriceDateEnabled() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/special_price_date_enabled');
    }
    
    public function isTierPriceEnabled() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/tier_price_enabled');
    }
    
    public function isSkuPriceLinkingEnabled() {
        return in_array('1', explode(',', Mage::getStoreConfig('mageworx_catalog/customoptions/assigned_product_attributes')));
    }
    public function isSkuNameLinkingEnabled() {
        return in_array('2', explode(',', Mage::getStoreConfig('mageworx_catalog/customoptions/assigned_product_attributes')));
    }
    public function isSkuCostLinkingEnabled() {
        if (!$this->isCostEnabled()) return false;
        return in_array('3', explode(',', Mage::getStoreConfig('mageworx_catalog/customoptions/assigned_product_attributes')));
    }
    public function isSkuWeightLinkingEnabled() {
        if (!$this->isWeightEnabled()) return false;
        return in_array('4', explode(',', Mage::getStoreConfig('mageworx_catalog/customoptions/assigned_product_attributes')));
    }
    public function isSkuQtyLinkingEnabled() {
        if (!$this->isInventoryEnabled()) return false;
        return in_array('5', explode(',', Mage::getStoreConfig('mageworx_catalog/customoptions/assigned_product_attributes')));
    }

    public function isOptionDescriptionEnabled() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/option_description_enabled');
    }    
    public function getDescriptionEditMode() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/description_edit_mode');
    }
    public function getDescriptionAppearance() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/description_appearance');
    }
    
    public function isOptionSkuPolicyEnabled() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/option_sku_policy_enabled');
    }
    
    public function getOptionSkuPolicyDefault() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/option_sku_policy_default');
    }
    
    public function isOptionSkuPolicyApplyToCart() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/option_sku_policy_apply')==2;
    }
    
    public function isInventoryEnabled() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/inventory_enabled');
    }
    
    public function isSetProductOutOfStock() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/set_product_out_of_stock');
    }
    
    public function isQntyInputEnabled() {        
        return Mage::getStoreConfig(self::XML_PATH_CUSTOMOPTIONS_ENABLE_QNTY_INPUT);
    }
    
    public function getDefaultOptionQtyLabel() {        
        return Mage::getStoreConfig('mageworx_catalog/customoptions/default_option_qty_label');
    }

    public function canDisplayQtyForOptions() {
        return Mage::getStoreConfig(self::XML_PATH_CUSTOMOPTIONS_DISPLAY_QTY_FOR_OPTIONS);
    }
    
    public function canShowQtyPerOptionInCart() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/show_qty_per_option_in_cart');
    }
    
    // 0 - disable, 1 - Hide, 2 - Backorders
    public function getOutOfStockOptions() {
        return Mage::getStoreConfig('mageworx_catalog/customoptions/hide_out_of_stock_options');
    }
    
    public function getImagesThumbnailsSize() {        
        return intval(Mage::getStoreConfig('mageworx_catalog/customoptions/images_thumbnails_size'));
    }    
    public function isImageModeEnabled() {
        return Mage::getStoreConfigFlag('mageworx_catalog/customoptions/enable_image_mode');  
    }
    public function isImagesAboveOptions() {        
        return Mage::getStoreConfigFlag('mageworx_catalog/customoptions/images_above_options');
    }
    
    public function isDefaultTextEnabled() {
        return Mage::getStoreConfigFlag('mageworx_catalog/customoptions/enable_default_text');  
    }
    
    public function isSpecifyingCssClassEnabled() {
        return Mage::getStoreConfigFlag('mageworx_catalog/customoptions/enable_specifying_css_class');  
    }
    
    public function isCustomerGroupsEnabled() {
        return Mage::getStoreConfigFlag('mageworx_catalog/customoptions/enable_customer_groups');  
    }

    public function getOptionStatusArray() {
        return array(
            self::STATUS_VISIBLE => $this->__('Active'),
            self::STATUS_HIDDEN => $this->__('Disabled'),
        );
    }

    public function getFilter($data) {
        $result = array();
        $filter = new Zend_Filter();
        $filter->addFilter(new Zend_Filter_StringTrim());

        if ($data) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $result[$key] = $this->getFilter($value);
                } else {
                    $result[$key] = $filter->filter($value);
                }
            }
        }
        return $result;
    }

    public function getFiles($path) {
        return @glob($path . "*.*");
    }

    public function isCustomOptionsFile($groupId, $optionId, $valueId = false) {
        return $this->getFiles($this->getCustomOptionsPath($groupId, $optionId, $valueId));
    }

    public function getCustomOptionsPath($groupId, $optionId = false, $valueId = false) {
        return Mage::getBaseDir('media') . DS . 'customoptions' . DS . ($groupId ? $groupId : 'options') . DS . ($optionId ? $optionId . DS : '') . ($valueId ? $valueId . DS : '');
    }    

    public function deleteOptionFile($groupId, $optionId, $valueId = false, $fileName = '') {
        $dir = $this->getCustomOptionsPath($groupId, $optionId, $valueId);
        if ($fileName) {
            if (file_exists($dir . $fileName)) {
                unlink($dir . $fileName);
                $isEmpty = true;
                if (is_dir($dir)) {
                    $objects = scandir($dir);
                    foreach ($objects as $object) {
                        if ($object=='.' || $object == '..') continue;
                        if (filetype($dir . DS . $object) == "dir") {
                            if (file_exists($dir . $object . DS . $fileName)) unlink($dir . $object . DS . $fileName);
                            continue;
                        }
                        $isEmpty = false;
                    }
                }
                // if empty - remove folder
                if ($isEmpty) $this->deleteFolder($dir);
                return true;
            } else {
                return false;
            }
        } else {
            $this->deleteFolder($dir);
        }
    }

    public function getFileName($filePath) {
        $name = '';
        $name = substr(strrchr($filePath, '/'), 1);
        if (!$name) {
            $name = substr(strrchr($filePath, '\\'), 1);
        }
        return $name;
    }

    public function getCheckImgPath($imagePath, $thumbnailsSize = 0) {
        $ext = strtolower(substr($imagePath, -4));
        if ($ext=='.jpg' || $ext=='.gif' || $ext=='.png' || $ext=='jpeg') {
            $path = explode(DS, $imagePath);
            $fileName = array_pop($path);
            $imagePath = implode(DS, $path);
            $path = Mage::getBaseDir('media') . DS . 'customoptions' . DS . $imagePath . DS;
            $filePath = $path . $fileName;
            if (!file_exists($filePath)) return '';            
        } else {        
            if (substr($imagePath, -1, 1)!=DS) $imagePath .= DS;
            $path = Mage::getBaseDir('media') . DS . 'customoptions' . DS . $imagePath;

            $file = @glob($path . "*.*");
            if (!$file) return '';
            $filePath = $file[0];
            $fileName = str_replace($path, '', $filePath);
        }
        
        if ($thumbnailsSize>0) {
            $smallPath = $path . $thumbnailsSize . 'x' . DS;
            $smallFilePath = $smallPath . $fileName;
            if (!file_exists($smallFilePath)) $this->getSmallImageFile($filePath, $smallPath, $fileName, $thumbnailsSize);
        }        
        return array($imagePath, $fileName);
    }
    
    public function getImgData($imagePath, $optionId = false, $optionTypeId = false, $thumbnailsSize = 0) {
        if (!$imagePath) return array();
        if (substr($imagePath, 0, 1)=='#') { // color
            return array(
                'color' => $imagePath,
                'source' => 2
                );
        } else { // file
            if (!$thumbnailsSize) $thumbnailsSize = $this->getImagesThumbnailsSize();
            $result = $this->getCheckImgPath($imagePath, $thumbnailsSize);
            if (!$result) return array();
            list($imagePath, $fileName) = $result;
            
            $urlImagePath = trim(str_replace(DS, '/', $imagePath), ' /');
            $bigImgUrl = Mage::getBaseUrl('media') . 'customoptions/'. $urlImagePath. '/' . $fileName;

            if ($thumbnailsSize>0) {
                $imgUrl = Mage::getBaseUrl('media') . 'customoptions/'. $urlImagePath. '/' . $thumbnailsSize . 'x/' . $fileName;
            } else {
                $imgUrl = $bigImgUrl;
            }
            
            if (Mage::app()->getStore()->isAdmin() && Mage::app()->getRequest()->getControllerName()!='sales_order_create') {
                $imgData = array(
                    'label' => $this->__('Delete Image'),
                    'big_img_url' => $bigImgUrl,
                    'url' => $imgUrl,
                    'id' => $optionId,
                    'select_id' => $optionTypeId,
                    'file_name' => $fileName,
                    'source' => 1
                ); 
            } else {
                $imgData = array(
                    'big_img_url' => $bigImgUrl,                
                    'url' => $imgUrl,
                    'source' => 1
                );
            }
            return $imgData;
        }
    }
    
    public function getImgHtml($imgData) {
        if (!$imgData) return '';
        
        if ($imgData['source']==1) { // file
            $template = 'customoptions/option_image.phtml';
            if (Mage::app()->getRequest()->getControllerName()=='sales_order_create') $template = 'customoptions/composite/option_image.phtml';
        } else { // color
            $template = 'customoptions/option_color.phtml';
            if (Mage::app()->getRequest()->getControllerName()=='sales_order_create') $template = 'customoptions/composite/option_color.phtml';
        }
        
        return Mage::app()->getLayout()
                ->createBlock('core/template')
                ->setTemplate($template)
                ->addData(array('item' => new Varien_Object($imgData)))
                ->toHtml();
        
    }
    
    public function getSmallImageFile($fileOrig, $smallPath, $newFileName, $thumbnailsSize) {
        try {
            $image = new Varien_Image($fileOrig);
            $origHeight = $image->getOriginalHeight();
            $origWidth = $image->getOriginalWidth();

            // settings
            $image->keepAspectRatio(true);
            $image->keepFrame(true);
            $image->keepTransparency(true);
            $image->constrainOnly(false);
            $image->backgroundColor(array(255, 255, 255));
            $image->quality(90);


            $width = null;
            $height = null;
            if (Mage::app()->getStore()->isAdmin()) {
                if ($origHeight > $origWidth) {
                    $height = $thumbnailsSize;
                } else {
                    $width = $thumbnailsSize;
                }
            } else {
                $configWidth = $thumbnailsSize;
                $configHeight = $thumbnailsSize;

                if ($origHeight > $origWidth) {
                    $height = $configHeight;
                } else {
                    $width = $configWidth;
                }
            }

            $image->resize($width, $height);

            $image->constrainOnly(true);
            $image->keepAspectRatio(true);
            $image->keepFrame(false);
            //$image->display();
            $image->save($smallPath, $newFileName);
        } catch (Exception $e) {
        }
    }

    public function getOptionImgView($option) {
        $block = Mage::app()->getLayout()
                ->createBlock('core/template')
                ->setTemplate('customoptions/option_image.phtml')
                ->addData(array('items' => $option))
                ->toHtml();

        return $block;
    }

    public function copyFolder($path, $dest) {
        if (is_dir($path)) {
            @mkdir($dest);
            $objects = scandir($path);
            if (sizeof($objects) > 0) {
                foreach ($objects as $file) {
                    if ($file == "." || $file == "..")
                        continue;
                    // go on
                    if (is_dir($path . DS . $file)) {
                        $this->copyFolder($path . DS . $file, $dest . DS . $file);
                    } else {
                        copy($path . DS . $file, $dest . DS . $file);
                    }
                }
            }
            return true;
        } elseif (is_file($path)) {
            return copy($path, $dest);
        } else {
            return false;
        }
    }

    public function deleteFolder($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . DS . $object) == "dir") {
                        $this->deleteFolder($dir . DS . $object);
                    } else {
                        unlink($dir . DS . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
    
    public function getMaxOptionId() {
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $select = $connection->select()->from($tablePrefix . 'catalog_product_option', 'MAX(`option_id`)');        
        return intval($connection->fetchOne($select));
    }
    
    public function currencyByStore($price, $store, $format = true, $includeContainer = true) {
        if (version_compare($this->getMagetoVersion(), '1.5.0', '>=')) {
            return Mage::helper('core')->currencyByStore($price, $store, $format, $includeContainer);
        } else {
            return Mage::helper('core')->currency($price, $format, $includeContainer);
        }
    }
    
    public function _formatPrice($product, $store, $price, $flag = true) {
        if ($price==0) return '';
        $taxHelper = Mage::helper('tax');

        $sign = '+';
        if ($price < 0) {
            $sign = '-';
            $price = 0 - $price;
        }

        $priceStr = $sign;
        $_priceInclTax = Mage::helper('tax')->getPrice($product, $price, true);
        $_priceExclTax = Mage::helper('tax')->getPrice($product, $price);
        
        
        if ($taxHelper->displayPriceIncludingTax()) {
            $priceStr .= $this->currencyByStore($_priceInclTax, $store, true, $flag);
        } elseif ($taxHelper->displayPriceExcludingTax()) {
            $priceStr .= $this->currencyByStore($_priceExclTax, $store, true, $flag);
        } elseif ($taxHelper->displayBothPrices()) {
            $priceStr .= $this->currencyByStore($_priceExclTax, $store, true, $flag);
            if ($_priceInclTax != $_priceExclTax) {
                $priceStr .= ' ('.$sign. $this->currencyByStore($_priceInclTax, $store, true, $flag).' '.$this->__('Incl. Tax').')';
            }
        }
        
        if ($flag) {
            $priceStr = '<span class="price-notice">'.$priceStr.'</span>';
        }
        return $priceStr;
    }
    
    public function getFormatedOptionPrice($product, $option, $value = null) {
        
        if ($value) $model = $value; else $model = $option;
        
        if (!$model || (!Mage::app()->getStore()->isAdmin() && $model->getIsMsrp())) return '';
        
        $store = $product->getStore();
        $isAbsolutePrice = $this->getProductAbsolutePrice($product);
        
        // for apply other TaxClassId
        if ($this->isSkuPriceLinkingEnabled() && $model->getSku() && $model->getTaxClassId()>0) {
            $product = Mage::getModel('catalog/product')->setStoreId($store->getId())->loadByAttribute('sku', $model->getSku());
        }
        
        if ($option->getType()==Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN || $option->getType()==Mage_Catalog_Model_Product_Option::OPTION_TYPE_MULTIPLE) $isFormat = false; else $isFormat = true;
        
        if ($this->isSpecialPriceEnabled() && $model->getSpecialPrice() > 0) {
            $special = true;
            $priceStr = $this->_formatPrice($product, $store, $model->getPrice(true), $isFormat); // old price
            
            // add striked to old price
            if ($isFormat) {
                $priceStr =  '<s>' . $priceStr .  '</s> ';
            } else {
                $priceStr =  '(' . $priceStr .  ') ';
            }
            
            $priceStr .= $this->_formatPrice($product, $store, $model->getSpecialPrice(), $isFormat); // special price            
            
        } else {
            $special = false;
            $price = ($model->getIsSkuPrice() && $model->getSpecialPrice()>0 ? $model->getSpecialPrice() : $model->getPrice(true));
            $priceStr = $this->_formatPrice($product, $store, $price, $isFormat); // standart price
        }
        // remove first plus
        if ($isAbsolutePrice) $priceStr = str_replace('+', '', $priceStr);
        
        if ($special && $model->getSpecialComment()) $priceStr .= ' '. $model->getSpecialComment();
        
        return $priceStr;
    }
    
    // translate and jsEscape
    public function __js($str) {
        return $this->jsEscape($this->__($str));
    }
    
    public function jsEscape($str) {
        return str_replace(array('\'', "\r", "\n"), array('\\\'', '', '\n'), $str);
    }
    
    protected $_products = array();    
    public function getProductIdBySku($sku) {
        if ($sku=='') return 0;
        if (isset($this->_products[$sku]['id'])) return $this->_products[$sku]['id'];
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        if ($product) $productId = $product->getId(); else $productId = 0;        
        $this->_products[$sku]['id'] = $productId;
        return $productId;
    }
    
    
    public function getProductNameBySku($sku, $storeId = 0) {
        if ($sku=='') return '';
        $productId = $this->getProductIdBySku($sku);
        if (!$productId) return '';
        return Mage::getModel('catalog/product')->setStoreId($storeId)->load($productId)->getName();
    }
    
    
    public function getTaxPrice($price, $quote, $taxClassId, $address=null) {
        if (!$quote) return 0;
        
        // prepare tax calculator
        if (!$address) {
            $address = $quote->getShippingAddress();
            if (!$address) $address = $quote->getBillingAddress();
        }        
        
        $calc = Mage::getSingleton('tax/calculation');
        $store = $quote->getStore();
        $addressTaxRequest = $calc->getRateRequest(
            $address,
            $quote->getBillingAddress(),
            $quote->getCustomerTaxClassId(),
            $store
        );                
        $addressTaxRequest->setProductClassId($taxClassId);                
        $rate = $calc->getRate($addressTaxRequest);
        
        return $calc->calcTaxAmount($price, $rate, false, true);
    }
    
    
    // exclude only default_tax!!
    public function getPriceExcludeTax($price, $quote, $taxClassId, $address=null) {
        if (!$quote || !$taxClassId || !$price) return $price;
        if (!$address) {
            $address = $quote->getShippingAddress();
            if (!$address) $address = $quote->getBillingAddress();
        }
        
        $countryId = $address->getCountryId();
        $regionId = $address->getRegionId();
        $postcode = $address->getPostcode();
        
        $address->setCountryId(Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_DEFAULT_COUNTRY));
        $address->setRegionId(Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_DEFAULT_REGION));
        $address->setPostcode(Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_DEFAULT_POSTCODE));
        
        $rate = $this->getTaxRate($quote, $taxClassId, $address);
        
        $address->setCountryId($countryId);
        $address->setRegionId($regionId);
        $address->setPostcode($postcode);
        
        return $quote->getStore()->roundPrice($price / ((100 + $rate)/100));
    }
    
    public function getTaxRate($quote, $taxClassId, $address=null) {
        if (!$quote || !$taxClassId) return 0;
        // prepare tax calculator
        if (!$address) {
            $address = $quote->getShippingAddress();
            if (!$address) $address = $quote->getBillingAddress();
        }
        
        $calc = Mage::getSingleton('tax/calculation');
        $store = $quote->getStore();
        $addressTaxRequest = $calc->getRateRequest(
            $address,
            $quote->getBillingAddress(),
            $quote->getCustomerTaxClassId(),
            $store
        );                
        $addressTaxRequest->setProductClassId($taxClassId);                
        $rate = $calc->getRate($addressTaxRequest);
        return $rate;
    }

    public function getActualProductPrice($product) {
        if ($product->getActualPrice()) return $product->getActualPrice();
        
        $price = $product->getPrice();
        $specialPrice = $product->getSpecialPrice();        
        if (!is_null($specialPrice) && $specialPrice != false) {
            if (Mage::app()->getLocale()->isStoreDateInInterval($product->getStore(), $product->getSpecialFromDate(), $product->getSpecialToDate())) {
                $price = min($price, $specialPrice);
            }
        }
        
        $groupPrices = $product->getData('group_price');
        if (is_null($groupPrices)) {
            $attribute = $product->getResource()->getAttribute('group_price');
            if ($attribute) {
                $attribute->getBackend()->afterLoad($product);
                $groupPrices = $product->getData('group_price');
            }
        }
        if ($groupPrices && count($groupPrices)>0) {
            $customerGroupId = $this->getCustomerGroupId();
            foreach($groupPrices as $group) {
                if ($group['cust_group']==Mage_Customer_Model_Group::CUST_GROUP_ALL || $group['cust_group']==$customerGroupId) {
                    $price = min($price, floatval($group['price']));
                }
            }
        }
        
        return $price;
    }
    
    // $model => $option or $value model
    public function getOptionPriceByQty($model, $qty, $product) {
        
        $basePrice = $this->getActualProductPrice($product);
        
        // load and calculate option price
        if (!$model->getIsPriceCalculated()) {
            if ($this->isSkuPriceLinkingEnabled() && $this->applyLinkedBySkuDataToOption($model, $model->getSku(), $product->getStore(), $product->getTaxClassId())) {
                $this->calculateOptionSpecialPrice($model, $product, false);
            }
            $model->setIsPriceCalculated(true);
        }
        
        
        if ($model->getSpecialPrice()!=0) {
            // apply special price
            $price = $model->getSpecialPrice();
        } else {
            // calculate product percent price
            $price = $model->getPrice();
            if ($price!=0 && $model->getPriceType()=='percent') {
                $price = $basePrice * ($price/100);
            }
        }
        
        $origPrice = $price;
        
        // apply tier price
        if ($qty > 1) {
            $tiers = $model->getTiers();
            if ($tiers && count($tiers)>0) {
                $customerGroupId = $this->getCustomerGroupId();
                foreach ($tiers as $tier) {
                    if ($qty>=$tier['qty'] && ($tier['customer_group_id']==Mage_Customer_Model_Group::CUST_GROUP_ALL || $tier['customer_group_id']==$customerGroupId)) {
                        $tierPrice = floatval($tier['price']);
                        if ($tier['price_type']=='percent' && $tierPrice!=0) {   
                            if ($model->getPriceType()=='fixed') {
                                // % of fixed option (or special price)
                                $tierPrice = $origPrice * ($tierPrice/100);
                            } else {
                                // % of product
                                $tierPrice = $basePrice * ($tierPrice/100);
                            }
                        }
                        if ($tierPrice < $price) {
                            $price = $tierPrice;
                        }
                    }
                }
            }
        }
        // total option price
        return $price * $qty;
    }
    
    
    // $model -> $option or $value model
    // return: true - applyed, false - no valid sku
    public function applyLinkedBySkuDataToOption($model, $sku, $store, $productTaxClassId) {
        if (!$sku) return false;
        $skuPriceLinkingEnabled = $this->isSkuPriceLinkingEnabled();
        $skuWeightLinkingEnabled = $this->isSkuWeightLinkingEnabled();
        $skuCostLinkingEnabled = $this->isSkuCostLinkingEnabled();
        
        if (!$skuPriceLinkingEnabled && !$skuWeightLinkingEnabled && !$skuCostLinkingEnabled) return false;
        
        if (!isset($this->_products[$sku]['id']) || ($this->_products[$sku]['id']>0 && !isset($this->_products[$sku]['price']))) {
            $productBySku = Mage::getModel('catalog/product')->setStoreId($store->getId())->loadByAttribute('sku', $sku);
            if ($productBySku && $productBySku->getId() > 0) {
                
                $this->_products[$sku]['id'] = $productBySku->getId();
                $this->_products[$sku]['weight'] = $productBySku->getWeight();
                $this->_products[$sku]['cost'] = $productBySku->getCost();

                $price = $finalPrice = $productBySku->getPrice();
                $this->_products[$sku]['price'] = $price;

                $specials = array();

                // check special_price
                $specialPrice = $productBySku->getSpecialPrice();        
                if (!is_null($specialPrice) && $specialPrice!=false) {
                    if ($specialPrice < $price) {
                        $finalPrice = $specialPrice;
                        $specials[] = array(
                            'customer_group_id' => 32000,
                            'price' => $specialPrice,
                            'price_type' => 'fixed',
                            'comment' => '',
                            'date_from' => $productBySku->getSpecialFromDate(),
                            'date_to' => $productBySku->getSpecialToDate()
                        );
                    }
                }

                // apply catalog price rules
                if (!Mage::app()->getStore()->isAdmin() || Mage::app()->getRequest()->getControllerName()!='catalog_product') {
                    $productBySku->setFinalPrice($finalPrice);
                    Mage::dispatchEvent('catalog_product_get_final_price', array('product'=>$productBySku, 'qty' => null));
                    if ($finalPrice>$productBySku->getFinalPrice()) {
                        $specials[] = array(
                                'customer_group_id' => 32000,
                                'price' => $productBySku->getFinalPrice(),
                                'price_type' => 'fixed',
                                'comment' => '',
				'date_from'=> '',
                                'date_to'=> ''
                            );
                    }
                }

                // check group_price
                if ($this->isSpecialPriceEnabled()) {
                    $groupPrices = $productBySku->getData('group_price'); //$productBySku->getGroupPrice();
                    if (is_null($groupPrices)) {
                        $attribute = $productBySku->getResource()->getAttribute('group_price');
                        if ($attribute) {
                            $attribute->getBackend()->afterLoad($productBySku);
                            $groupPrices = $productBySku->getData('group_price');
                        }
                    }

                    if ($groupPrices && count($groupPrices)>0) {
                        foreach($groupPrices as $group) {
                            if (!isset($this->_customerGroups[$group['cust_group']])) continue;
                            $specials[] = array(
                                'customer_group_id' => $group['cust_group'],
                                'price' => $group['price'],
                                'price_type' => 'fixed',
                                'comment' => '',
				'date_from'=> '',
                                'date_to'=> ''
                            );
                        }

                        //sort
                        usort($specials, array($this, '_sortPrices'));
                    }
                }

                if (count($specials)>0) $this->_products[$sku]['specials'] = $specials;


                // check tier_prices
                if ($this->isTierPriceEnabled()) {
                    $tiers = $productBySku->getData('tier_price'); //$productBySku->getTierPrice();
                    if (is_null($tiers)) {
                        $attribute = $productBySku->getResource()->getAttribute('tier_price');
                        if ($attribute) {
                            $attribute->getBackend()->afterLoad($productBySku);
                            $tiers = $productBySku->getData('tier_price');
                        }
                    }

                    if ($tiers && count($tiers)>0) {
                        $this->_products[$sku]['tiers'] = array();
                        foreach($tiers as $tier) {
                            if (!isset($this->_customerGroups[$tier['cust_group']])) continue;
                            $this->_products[$sku]['tiers'][] = array(
                                'customer_group_id' => $tier['cust_group'],
                                'qty' => intval($tier['price_qty']),
                                'price' => $tier['price'],
                                'price_type' => 'fixed'
                            );
                        }

                        //sort
                        usort($this->_products[$sku]['tiers'], array($this, '_sortPrices'));
                    }
                }


                $this->_products[$sku]['tax_class_id'] = $productBySku->getTaxClassId();
                $catalogHelper = Mage::helper('catalog');
                $this->_products[$sku]['msrp'] = ((method_exists($catalogHelper, 'canApplyMsrp')) ? $catalogHelper->canApplyMsrp($productBySku) : false);


            } else {
                $this->_products[$sku]['id'] = 0;
            }
        }

        if (isset($this->_products[$sku]['price'])) {
            if ($skuPriceLinkingEnabled) {
                $model->setPrice($this->_products[$sku]['price']);
                $model->setPriceType('fixed');

                if (isset($this->_products[$sku]['specials'])) $model->setSpecials($this->_products[$sku]['specials']);
                if (isset($this->_products[$sku]['tiers'])) $model->setTiers($this->_products[$sku]['tiers']);

                if ($this->_products[$sku]['tax_class_id']!=$productTaxClassId) $model->setTaxClassId($this->_products[$sku]['tax_class_id']);
                $model->setIsMsrp($this->_products[$sku]['msrp']);
                $model->setIsSkuPrice(true);
            }
            
            if ($skuCostLinkingEnabled) {
                $model->setCost($this->_products[$sku]['cost']);
                $model->setIsSkuCost(true);
            }
            
            if ($skuWeightLinkingEnabled) {
                $model->setWeight($this->_products[$sku]['weight']);
                $model->setIsSkuWeight(true);
            }
            
            return true;
        }
                
        return false;
    }
    
    public function _sortPrices($a, $b) {
        if (isset($this->_customerGroups[$a['customer_group_id']]['label']) && $a['customer_group_id'] != $b['customer_group_id']) {
            return $this->_customerGroups[$a['customer_group_id']]['label'] < $this->_customerGroups[$b['customer_group_id']]['label'] ? -1 : 1;
        }
        
        if (isset($a['qty']) && $a['qty']!=$b['qty']) {
            return $a['qty'] < $b['qty'] ? -1 : 1;
        }
        return 0;
    }    
    
    // $model -> $option or $value model
    public function calculateOptionSpecialPrice($model, $product, $isSpecialPriceSeparate) {
        $specials = $model->getSpecials();
        if (!$specials) return false;
        foreach ($specials as $key=>$special) {
            if (isset($special['date_from']) && isset($special['date_to']) &&  !Mage::app()->getLocale()->isStoreDateInInterval(Mage::app()->getStore(), $special['date_from'], $special['date_to'])) {
                unset($specials[$key]);
            }
        }
        if (!$specials || count($specials)==0) return false;
        
        $customerGroupId = $this->getCustomerGroupId();
        if ($product) {
            $basePrice = $this->getActualProductPrice($product);
            $price = $origPrice = $model->getPrice(true);
        } else {
            $basePrice = 100;
            $price = $origPrice = $model->getPrice();
        }
        
        $priceType = $model->getPriceType();
        
        $specialComment = '';
        
        foreach ($specials as $special) {
            if ($special['customer_group_id']==Mage_Customer_Model_Group::CUST_GROUP_ALL || $special['customer_group_id']==$customerGroupId) {
                $specialPrice = $special['price'];
                
                if ($special['price_type']=='percent' && $specialPrice!=0) {
                    if ($priceType=='fixed') {
                        // % of fixed option
                        $specialPrice = $origPrice * ($specialPrice/100);
                    } else {
                        // % of product
                        $specialPrice = $basePrice * ($specialPrice/100);
                    }
                }
                if ($specialPrice < $price) {
                    $price = $specialPrice;
                    $specialComment = $special['comment'];
                }
            }
        }
        if ($price < $origPrice) {
            if ($isSpecialPriceSeparate) {
                $model->setSpecialPrice($price);
                $model->setSpecialComment($specialComment);
            } else {
                $model->setPrice($price);
                $model->setPriceType('fixed');
            }
            return true;
        }
        return false;
    }
    
    protected $_customerGroups;
    public function getCustomerGroups() {
        if (is_null($this->_customerGroups)) {
            $customerGroups = array(Mage_Customer_Model_Group::CUST_GROUP_ALL => array('value' => Mage_Customer_Model_Group::CUST_GROUP_ALL, 'label' => Mage::helper('catalog')->__('ALL GROUPS')));
            $collection = Mage::getModel('customer/group')->getCollection();
            foreach ($collection as $item) {
                $customerGroups[$item->getId()]['value'] = $item->getId();
                $customerGroups[$item->getId()]['label'] = $item->getCustomerGroupCode();
            }
            $this->_customerGroups = $customerGroups;
        }
        return $this->_customerGroups;
    }    
    
    
    public function getRowValueByIGI($IGI, $productId=0) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $select = $connection->select()->from(array('option_tbl' => $tablePrefix . 'catalog_product_option'), array())
                ->joinLeft(array('value_tbl'=> $tablePrefix . 'catalog_product_option_type_value'), 'value_tbl.option_id = option_tbl.option_id', array('option_type_id', 'sku', 'customoptions_qty'))
                ->where('option_tbl.product_id = ?', $productId)
                ->where('value_tbl.in_group_id = ?', $IGI);
        $row = $connection->fetchRow($select);
        return $row;
    }
    
    public function getCustomoptionsQty($customoptionsQty, $sku, $productId=0, $value=null, $quoteItemId=0, $quote=null, $returnWithBackorders=false) {
        $backorders = false;
        
        if (Mage::registry('customoptions_do_not_check_quote') > 0) $value = null;
        
        if ($value) {
            $optionTypeId = $value->getOptionTypeId();
            $IGI = $value->getInGroupId();
        } else {
            $optionTypeId = 0;
            $IGI = 0;
        }
        $newOptionTypeId = 0;
        // check link inventory to other option by IGI
        if (substr($customoptionsQty, 0, 1)=='i' && $productId>0) {
            $IGI = substr($customoptionsQty, 1);
            $row = $this->getRowValueByIGI($IGI, $productId);
            if ($row) {
                $newOptionTypeId = $row['option_type_id'];
                $sku = $row['sku'];
                $customoptionsQty = $row['customoptions_qty'];
            }
        }
        
        if (substr($customoptionsQty, 0, 1)=='x' || substr($customoptionsQty, 0, 1)=='i' || substr($customoptionsQty, 0, 1)=='l' && $productId>0) {
            // special inventory
            $customoptionsQty = '';
        } else {
            if ($sku!='' && $this->isSkuQtyLinkingEnabled()) {
                if (isset($this->_products[$sku]['qty'])) {
                    $customoptionsQty = $this->_products[$sku]['qty'];
                    $backorders = $this->_products[$sku]['backorders'];
                } elseif (!isset($this->_products[$sku]['id']) || $this->_products[$sku]['id']>0) { 
                    $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);                
                    if (isset($product) && $product && $product->getId() > 0) {
                        $this->_products[$sku]['id'] = $product->getId();
                        $customoptionsQty = '0';
                        // check product status!='disabled'
                        if ($product->getStatus()!=2) {
                            $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);                            
                            if ($item) {
                                if ($item->getUseConfigManageStock()) {
                                    $manageStock = Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_ITEM . 'manage_stock');
                                } else {
                                    $manageStock = $item->getManageStock();
                                }
                                if ($manageStock) {
                                    if ($item->getIsInStock()) {
                                        $customoptionsQty = strval(intval($item->getQty()));
                                        $backorders = $item->getBackorders();
                                    }
                                } else {
                                    $customoptionsQty = '';
                                }
                            }
                        }
                        $this->_products[$sku]['qty'] = $customoptionsQty;
                        $this->_products[$sku]['backorders'] = $backorders;
                    } else {
                        $this->_products[$sku]['id'] = 0;
                    }
                }
            }
            
            // check already added options in cart
            if ($optionTypeId>0 && ($customoptionsQty!=='' && $customoptionsQty!=='0')) {
                $opTotalQty = 0;
                if (is_null($quote)) {
                    if (Mage::app()->getStore()->isAdmin()) {
                        $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
                    } else {
                        //$quote = Mage::getSingleton('checkout/cart')->getQuote();
                        $quote = Mage::getSingleton('checkout/session')->getQuote();
                    }
                }
                
                
                
                $allItems = $quote->getAllItems();
                foreach ($allItems as $item) {                                        
                    //if (is_null($item->getId())) continue;
                    
                    // get correct $item qty
                    $qty = 0;
                    $cartPost = Mage::app()->getRequest()->getParam('cart', false);
                    if ($cartPost && isset($cartPost[$item->getId()]['qty'])) $qty = $cartPost[$item->getId()]['qty'];
                    if (!$qty) $qty = $item->getQty();
                    
                    
                    if ($item->getId()==$quoteItemId) {
                    	$onlyLinkedInventoryFlag = true;
                    } else {
                    	$onlyLinkedInventoryFlag = false;
                        // if is options sku
                        if ($item->getSku()==$sku) $opTotalQty += $qty;
                    }
                    //if ($item->getProductId()!=$productId) continue;
                    
                    $options = false;
                    $this->doNotCheckQuote(1);
                    $post = $this->getInfoBuyRequest($item->getProduct());
                    $this->doNotCheckQuote(-1);
                    
                    if (isset($post['options'])) $options = $post['options'];
                    if ($options) {
                        foreach ($options as $opId => $option) {
                            if (!$opId) continue; // || (($opId!=$optionId && $opId!=$newOptionId) && (!$sku || $this->_products[$sku]['id']==0))
                            
                            $productOption = Mage::getSingleton('catalog/product_option')->load($opId);
                            // check Options Inventory
                            $opType = $productOption->getType();
                            if ($productOption->getGroupByType($opType)!=Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) continue;
                            
                            if (!is_array($option)) $option = array($option);
                            foreach ($option as $opTypeId) {
                                if (!$opTypeId) continue;
                                $row = $productOption->getOptionValue($opTypeId);
                                if (!$row) continue;
                                
                                if ($row['customoptions_qty']!='i'.$IGI) {
                                    if ($onlyLinkedInventoryFlag) continue;
                                	
                                    if ($sku && $this->_products[$sku]['id']>0) {
                                        if  (!isset($row['sku']) || !$row['sku'] || $row['sku']!=$sku) continue;
                                    } elseif ($opTypeId!=$optionTypeId && $opTypeId!=$newOptionTypeId) {
                                        continue;
                                    }
                                }
                                
                                switch ($opType) {
                                    case 'checkbox':
                                    case 'multiswatch':
                                    case 'hidden':
                                        if (isset($post['options_'.$opId.'_'.$opTypeId.'_qty'])) $opQty = intval($post['options_'.$opId.'_'.$opTypeId.'_qty']); else $opQty = 1;
                                        break;
                                    case 'drop_down':
                                    case 'radio':
                                    case 'swatch':
                                        if (isset($post['options_'.$opId.'_qty'])) $opQty = intval($post['options_'.$opId.'_qty']); else $opQty = 1;
                                        break;
                                    case 'multiple':
                                        $opQty = 1;
                                        break;
                                }
                                
                                $opTotalQty += ($productOption->getCustomoptionsIsOnetime()?$opQty:$opQty*$qty);
                            }
                        }
                    }                    
                }
                
                // correction option inventory
                $customoptionsQty -= $opTotalQty;                
            }
            
            if ($customoptionsQty==='0') $customoptionsQty = 0;
            
            if (!$returnWithBackorders) {
                if ($customoptionsQty<0) $customoptionsQty = 0;
                if ($backorders && $customoptionsQty===0) $customoptionsQty = '';
            }
            
        }
        
        // false - APO Backorders Settings, 0 - No Backorders, 1 - Allow Qty Below 0, 2 - Allow Qty Below 0 and Notify Customer
        if ($returnWithBackorders) return array($customoptionsQty, $backorders);
        return $customoptionsQty;
    }
    
    public function getProductAbsolutePrice($product) {
        $flag = $product->getAbsolutePrice();
        if (!is_null($flag)) return $flag;
        $productId = $product->getId();
        if (!$productId) return false;
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $select = $connection->select()->from($tablePrefix . 'catalog_product_entity', array('absolute_price'))->where('entity_id = ' . $productId);
        return $connection->fetchOne($select);
    }
    
    public function getProductAbsoluteWeight($product) {
        $flag = $product->getAbsoluteWeight();
        if (!is_null($flag)) return $flag;
        $productId = $product->getId();
        if (!$productId) return false;
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $select = $connection->select()->from($tablePrefix . 'catalog_product_entity', array('absolute_weight'))->where('entity_id = ' . $productId);
        return $connection->fetchOne($select);
    }
    
    public function getProductSkuPolicy($product) {
        $flag = $product->getSkuPolicy();
        if (!is_null($flag)) return $flag;
        $productId = $product->getId();
        if (!$productId) return false;
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $select = $connection->select()->from($tablePrefix . 'catalog_product_entity', array('sku_policy'))->where('entity_id = ' . $productId);
        return $connection->fetchOne($select);
    }
    
    public function getOptionsJsonConfig($options) {
        $config = array();
        foreach ($options as $option) {
            /* @var $option Mage_Catalog_Model_Product_Option */
            if ($option->getGroupByType() == Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) {
                foreach ($option->getValues() as $value) {
                    $config[$option->getId()][$value->getId()] = $this->_getOptionConfiguration($option, $value);                    
                }
            } else {
                $config[$option->getId()] = $this->_getOptionConfiguration($option);
            }
            
            $config[$option->getId()]['is_onetime'] = $option->getCustomoptionsIsOnetime();
            $config[$option->getId()]['image_mode'] = ($this->isImageModeEnabled()?$option->getImageMode():1);
            $config[$option->getId()]['exclude_first_image'] = $option->getExcludeFirstImage();
        }
        return Mage::helper('core')->jsonEncode($config);
    }
    public function _getOptionConfiguration($option, $value = null) {
        $data = array();        
        // price
        if ($value) {
            if ($value->getIsMsrp()) {
                $data['price'] = 0;
            } elseif ($value->getSpecialPrice()>0) {
                $data['price'] = Mage::helper('core')->currency($value->getSpecialPrice(), false, false);
                $data['old_price'] = Mage::helper('core')->currency($value->getPrice(), false, false);
            } else {
                $data['price'] = Mage::helper('core')->currency($value->getPrice(), false, false);
            }
            $data['price_type'] = $value->getPriceType();
            if ($this->isTierPriceEnabled()) {
                $tierPrices = $this->_getTierPrices($value, $data);
                if (count($tierPrices)>0) $data['tier_prices'] = $tierPrices;
            }
            
            // x-qty data
            $customoptionsQty = $value->getCustomoptionsQty();
            if ($customoptionsQty) {
                if (substr($customoptionsQty, 0, 1)=='x') {
                    $data['x_qty'] = intval(substr($customoptionsQty, 1));
                    $option->setIsXQtyEnabled(true);
                    $product = $option->getProduct();
                    if ($product) $product->setIsXQtyEnabled(true);
                } elseif (substr($customoptionsQty, 0, 1)=='l') {
                    $data['l_qty'] = intval(substr($customoptionsQty, 1));
                    $value->setIsParentLQty(true);
                    $option->setIsParentLQty(true);
                }
            }
            
            // tax data
            if ($value->getTaxClassId()>0) $data['tax'] = $this->_getTaxPercentForCatalog($value->getTaxClassId());
        } else {
            if ($option->getIsMsrp()) {
                $data['price'] = 0;
            } elseif ($option->getSpecialPrice()>0) {
                $data['price'] = Mage::helper('core')->currency($option->getSpecialPrice(), false, false);
                $data['old_price'] = Mage::helper('core')->currency($option->getPrice(), false, false);
            } else {
                $data['price'] = Mage::helper('core')->currency($option->getPrice(), false, false);
            }
            $data['price_type'] = $option->getPriceType();
            
            if ($option->getMaxCharacters()) {
                $data['max_characters'] = intval($option->getMaxCharacters());
                $option->setIsDependentLQty(true);
            }
            
            if ($option->getTaxClassId()>0) $data['tax'] = $this->_getTaxPercentForCatalog($option->getTaxClassId());
        }
        
        // images
        if ($value && (($option->getType()==Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN || $option->getType()==Mage_Catalog_Model_Product_Option::OPTION_TYPE_MULTIPLE) || $option->getImageMode()>1)) {
            $images = $value->getImages();
            if ($images) {
                foreach($images as $image) {
                    $arr = $this->getImgData($image['image_file']);
                    if (isset($arr['big_img_url'])) $data['images'][] = array($arr['url'], $arr['big_img_url']);
                    if (isset($arr['color'])) $data['images'][] = array($arr['color'], false);
                }
            }
        }        
        // value title
        if ($value) $data['title'] = $value->getTitle();
        
        return $data;
    }
    
    public function _getTierPrices($value, $data) {
        $tiers = $value->getTiers();
        $tiersArr = array();
        if ($tiers && count($tiers)>0) {
            if ($value->getPriceType()=='fixed') {
                $basePrice = $data['price'];
            } else {
                $basePrice = $value->getProduct()->getFinalPrice();
            }
            
            $customerGroupId = $this->getCustomerGroupId();
            $tiersJsArr = array();
            foreach ($tiers as $tier) {
                if ($tier['customer_group_id']!=Mage_Customer_Model_Group::CUST_GROUP_ALL && $tier['customer_group_id']!=$customerGroupId) continue;
                if ($tier['price_type']== 'percent') {
                    $price = ($tier['price']!=0 ? $basePrice*($tier['price']/100) : 0);
                } else {
                    $price = $tier['price'];
                }
                if ($data['price'] <= $price) continue;
                $price = number_format($price, 2, null, '');
                $tiersArr[$tier['qty']] = $price;
                ksort($tiersArr);
            }
        }
        return $tiersArr;
    }    
    public function _getTaxPercentForCatalog($taxClassId) {
        $request = Mage::getSingleton('tax/calculation')->getRateRequest();
        $percent = Mage::getSingleton('tax/calculation')->getRate($request->setProductClassId($taxClassId));
        return $percent;
    }
    
    
    
    protected $_dependentIds = array();
    protected $_defaultData = array();
    protected $_limitQtyData = array();
    
    public function getJsonDependentData($options) {
        $dependentData = array();
        $this->_dependentIds = array();
        foreach ($options as $option) {
            if ($option->getGroupByType()==Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) {
                foreach ($option->getValues() as $value) {
                    if ($value->getDependentIds()) {
                        $dependentIds = explode(',', $value->getDependentIds());
                        foreach($dependentIds as &$id) {
                            $id = intval($id);
                            if (isset($this->_dependentIds[$id])) {
                                $this->_dependentIds[$id]++;
                            } else {
                                $this->_dependentIds[$id] = 1;
                            }
                            if ($value->getIsParentLQty()) $this->_limitQtyData[$id] = 1;
                        }
                        $dependentData[intval($value->getOptionTypeId())] = $dependentIds;
                    }
                }
            }
        }
        return Mage::helper('core')->jsonEncode($dependentData);
    }
    
    public function getJsonInGroupIdData($options) {
        $inGroupIdData = array();
        $defaultData = array();
        
        $quoteItemId = intval(Mage::app()->getRequest()->getParam('id'));
        
        foreach ($options as $option) {
            if (!$option->getIsDependent()) continue;
            
            $optionId = $option->getId();
            
            $configValue = $this->getPreconfiguredValues($option->getProduct(), $optionId);
            
            if ($option->getGroupByType()==Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) {
                $count = 0;
                if ($configValue && !is_array($configValue)) $configValue = array($configValue);
                
                foreach ($option->getValues() as $value) {
                    $count++;
                    $inGroupId = intval($value->getInGroupId());
                    
                    if (!isset($this->_dependentIds[$inGroupId]) || $this->_dependentIds[$inGroupId]==0) continue;
                    if (isset($this->_limitQtyData[$inGroupId])) $option->setIsDependentLQty(true);
                    
                    // dependency => count parent options for AND or 1 => for OR
                    $data = array('dependency' => ($option->getIsDependent()==2 ? $this->_dependentIds[$inGroupId] : 1));
                    if ($option->getType()==Mage_Catalog_Model_Product_Option::OPTION_TYPE_RADIO || $option->getType()==Mage_Catalog_Model_Product_Option::OPTION_TYPE_CHECKBOX || $option->getType()==MageWorx_CustomOptions_Model_Catalog_Product_Option::OPTION_TYPE_HIDDEN) {
                        $outOfStock = false;
                        if ($this->isInventoryEnabled()) {
                            $customoptionsQty = $this->getCustomoptionsQty($value->getCustomoptionsQty(), $value->getSku(), $option->getProduct()->getId(), $value, $quoteItemId);
                            if ($this->getOutOfStockOptions()==1 && ($customoptionsQty===0 || $value->getIsOutOfStock())) continue;
                            if ($customoptionsQty===0 && $this->getOutOfStockOptions()==0) $outOfStock = true;
                        }
                        $data['out_of_stock'] = $outOfStock;
                        $data['view_mode'] = $option->getViewMode();
                        $elementId = 'options_'. $optionId .'_'. $count;
                        $data[$elementId] = 1;
                        
                    } else {
                        $elementId = 'select_'. $optionId;
                        $data[$elementId] = $value->getOptionTypeId();
                    }
                    $inGroupIdData[$inGroupId] = $data;
                    
                    // add defaultData
                    if ((!$configValue && $value->getDefault()==1) || ($configValue && in_array($value->getOptionTypeId(), $configValue))) {
                        
                        if ($option->getType()==Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN || $option->getType()==MageWorx_CustomOptions_Model_Catalog_Product_Option::OPTION_TYPE_SWATCH) {
                            $defaultData[$elementId] = $value->getOptionTypeId();
                        } elseif ($option->getType()==Mage_Catalog_Model_Product_Option::OPTION_TYPE_MULTIPLE || $option->getType()==MageWorx_CustomOptions_Model_Catalog_Product_Option::OPTION_TYPE_MULTISWATCH || $option->getType()==MageWorx_CustomOptions_Model_Catalog_Product_Option::OPTION_TYPE_HIDDEN) {
                            $defaultData[$elementId][$value->getOptionTypeId()] = 1;
                        } else {
                            // checkbox, radio
                            $defaultData[$elementId] = 1;
                            
                        }
                    }
                }
            } else {
                if (!$option->getInGroupId()) continue;
                $inGroupId = intval($option->getInGroupId());
                if (!isset($this->_dependentIds[$inGroupId]) || $this->_dependentIds[$inGroupId]==0) continue;
                if (isset($this->_limitQtyData[$inGroupId])) $option->setIsDependentLQty(true);
                
                // dependency => count parent options for AND or 1 => for OR
                $data = array('dependency' => ($option->getIsDependent()==2 ? $this->_dependentIds[$inGroupId] : 1));
                $data['view_mode'] = $option->getViewMode();
                
                
                switch ($option->getType()) {
                    case 'field':
                    case 'area':
                        $data['options_'. $optionId .'_text'] = 1;
                        
                        // add defaultData
                        $defaultText = $configValue;
                        if (!$defaultText && $this->isDefaultTextEnabled() && $option->getDefaultText()) $defaultText = $option->getDefaultText();
                        if ($defaultText) $defaultData['options_'. $optionId .'_text'] = str_replace(array("\r\n", "\n"), '\n', $this->htmlEscape($defaultText));
                        break;
                    case 'file':
                        $data['options_'. $optionId .'_file'] = 1;
                        if (Mage::getConfig()->getModuleConfig('Mico_Mupload')->is('active', true)) $data['mico-mupload-uploader-container-'. $optionId] = 1;
                        break;
                    case 'date_time':
                    case 'time':
                        $data['options_'. $optionId .'_minute'] = 1;
                        $data['options_'. $optionId .'_hour'] = 1;
                        $data['options_'. $optionId .'_day_part'] = 1;
                    case 'date':
                        $data['options_'. $optionId .'_date'] = 1;
                        $data['options_'. $optionId .'_day'] = 1;
                        $data['options_'. $optionId .'_month'] = 1;
                        $data['options_'. $optionId .'_year'] = 1;
                        break;
                }
                $inGroupIdData[$inGroupId] = $data;
            }
        }
        $this->_defaultData = $defaultData;
        return Mage::helper('core')->jsonEncode($inGroupIdData);
    }
    
    public function getJsonDefaultData($options) {
        return Mage::helper('core')->jsonEncode($this->_defaultData);
    }
    
    public function getOptionDescription($option) {
        if (!$this->isOptionDescriptionEnabled() || !$option->getDescription()) return '';
        if ($this->getDescriptionAppearance()==2) {
            return '<div class="tagtip-question" id="description_'. $option->getId() .'"></div>
            <script type="text/javascript">
                new Tagtip(\'description_'. $option->getId() .'\', \''. $this->jsEscape($option->getDescription()) .'\', {align: \'rightMiddle\'});
            </script>';
        } else {
            return '<p class="note">'. $option->getDescription() .'</p>';
        }        
    }    
    
    public function getCustomerGroupId() {
        if (Mage::app()->getStore()->isAdmin()) {                    
            $sessionQuote = Mage::getSingleton('adminhtml/session_quote');
            $customerGroupId = $sessionQuote ? $sessionQuote->getCustomer()->getGroupId() : 0;
        } else {
            $customerSession = Mage::getSingleton('customer/session');
            $customerGroupId = $customerSession->isLoggedIn() ? $customerSession->getCustomer()->getGroupId() : 0;
        }
        return $customerGroupId;
    }
    
    private $_categories = array();
    public function getCategories() {
        if (count($this->_categories)) {
            return $this->_categories;
        }
        foreach (Mage::app()->getWebsites() as $website) {
            $defaultStore = $website->getDefaultStore();
        	if ($defaultStore) {
            	$rootId = $defaultStore->getRootCategoryId();
            	$rootCat = Mage::getModel('catalog/category')->load($rootId);
            	$this->_categories[$rootId] = $rootCat->getName();
            	$this->getChildCats($rootCat, 0);
            }
        }
        return $this->_categories;
    }
    
    public function getChildCats($cat, $level) {
        if ($children = $cat->getChildren()) {
            $level++;
            $children = explode(',', $children);
            foreach ($children as $childId) {
                $childCat = Mage::getModel('catalog/category')->load($childId);
                $this->_categories[$childId] = str_repeat('&nbsp;&nbsp;&nbsp;', $level) . $childCat->getName();
                if ($childCat->getChildren()) {
                    $this->getChildCats($childCat, $level);
                }
            }
        }
    }
    
    public function calculateAdditionalWidthOfFields($width) {
        if ($this->isDependentEnabled()) $width += 210;
        if ($this->isWeightEnabled()) $width += 85;
        if ($this->isCostEnabled()) $width += 85;
        if ($this->isTierPriceEnabled()) $width += 30 ;
        if ($this->isSpecialPriceEnabled()) $width += 50;
        if ($this->isSpecialPriceEnabled() || $this->isTierPriceEnabled()) $width += 60;
        if ($this->isInventoryEnabled()) $width += 50;
        return $width;
    }
    
    public function getInfoBuyRequest($product) {
        $post = array();
        if ($product) {
            $infoBuyRequest = $product->getCustomOption('info_buyRequest');
            if ($infoBuyRequest) {
                if ($infoBuyRequest->getValue()) $post = unserialize($infoBuyRequest->getValue());
            }
        }
        return $post;   
    }
    
    public function getPreconfiguredValues($product, $optionId) {
        $selectOptions = Mage::app()->getRequest()->getParam('config', '');
        if ($selectOptions) {
            $options = explode(',', $selectOptions);
            foreach($options as $option) {
                $values = explode('-', $option);
                if ($optionId == array_shift($values)) return $values;
            }
        }
        
        if (version_compare($this->getMagetoVersion(), '1.5.0', '>=')) {
            return $product->getPreconfiguredValues()->getData('options/' . $optionId);
        }
        return false;
    }
    
    public function getMaxInputVars() {
        $maxInputVars = ini_get('max_input_vars');
        if (!$maxInputVars) $maxInputVars = 9999999; // fix for PHP < 5.3
        $suhosinPostMaxVars = ini_get('suhosin.post.max_vars');
        $suhosinRequestMaxVars = ini_get('suhosin.request.max_vars');        
        if ($suhosinPostMaxVars > 0 && $suhosinRequestMaxVars > 0) {
            return intval(min(array($maxInputVars, $suhosinPostMaxVars, $suhosinRequestMaxVars)));
        }
        return intval($maxInputVars);
    }
    
    // $flag: 1 - start, -1 stop;
    public function doNotCheckQuote($flag) {
        $doNotCheckQuote = Mage::registry('customoptions_do_not_check_quote');
        if (!is_null($doNotCheckQuote)) Mage::unregister('customoptions_do_not_check_quote');
        $doNotCheckQuote = intval($doNotCheckQuote) + $flag;
        Mage::register('customoptions_do_not_check_quote', $doNotCheckQuote);        
    }
    
    public function isMagetoEnterprise() {
        $isEnterprise = false;
        $i = Mage::getVersionInfo();
        if ($i['major']==1) {
            if (method_exists('Mage', 'getEdition')) {
                if (Mage::getEdition()==Mage::EDITION_ENTERPRISE) $isEnterprise = true;
            } elseif ($i['minor'] > 7) {
                $isEnterprise = true;
            }
        }
        return $isEnterprise;
    }
    
    public function getMagetoVersion() {
        $i = Mage::getVersionInfo();
        if ($i['major']==1 && $this->isMagetoEnterprise()) $i['minor'] -= 5;
        return trim("{$i['major']}.{$i['minor']}.{$i['revision']}" . ($i['patch'] != '' ? ".{$i['patch']}" : "") . "-{$i['stability']}{$i['number']}", '.-');
    }
    
}