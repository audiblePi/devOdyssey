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

class MageWorx_Adminhtml_Block_Customoptions_Options_Edit_Tab_Options_Option extends MageWorx_Adminhtml_Block_Customoptions_Adminhtml_Catalog_Product_Edit_Tab_Options_Option {

    public function __construct() {
        parent::__construct();
    }

    protected function getStoreId() {
        if (Mage::app()->getStore()->isAdmin()) {
            return Mage::registry('store_id');
        } else {
            return Mage::app()->getStore()->getId();
        }
    }

    public function getTemplateData() {
        if ($data = Mage::getSingleton('adminhtml/session')->getData('customoptions_data')) {
            if (isset($data['general'])) {
                return $data['general'];
            } else {
                return null;
            }
        } elseif (Mage::registry('customoptions_data')) {
            return Mage::registry('customoptions_data')->getData();
        }
    }
    
    
    public function getOptionValues() {                        
        $data = array();                
        
        $optionsArr = '';
        $data = $this->getTemplateData();
        if (isset($data['hash_options'])) $optionsArr = $data['hash_options'];
       
        $zendDate = new Zend_Date();
        $dateFormat = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        
        $helper = Mage::helper('customoptions');
        $helper->getCustomerGroups(); // init customer_groups for sort prices

        $groupId = (int) $this->getRequest()->getParam('group_id');
        if ($optionsArr) $optionsArr = unserialize($optionsArr);
        
        $store = Mage::app()->getStore($this->getStoreId());
        
        $storeOptionsArr = array();
        $groupStore = Mage::getSingleton('customoptions/group_store')->loadByGroupAndStore($groupId, $this->getStoreId());        
        if ($groupStore->getHashOptions()) $storeOptionsArr = unserialize($groupStore->getHashOptions()); 
        //print_r($storeOptionsArr); exit;
        
        $optionModel = Mage::getSingleton('catalog/product_option');                        
        
        if (!$this->_values && $optionsArr) {
            $values = array();
            $sortOrder = array();
            $scope = (int) Mage::app()->getStore()->getConfig(Mage_Core_Model_Store::XML_PATH_PRICE_SCOPE);
            $optionItemCount = count($optionsArr);
            foreach ($optionsArr as $optionId=>$option) {
                $option = new Varien_Object($option);
                $value = array();                
                if ($option->getIsDelete() != '1') {
                    $value['id'] = $option->getOptionId();
                    $value['item_count'] = $optionItemCount;
                    $value['option_id'] = $option->getOptionId();
                    $value['title'] = $this->htmlEscape(isset($storeOptionsArr[$optionId]['title'])?$storeOptionsArr[$optionId]['title']:$option->getTitle());
                    
                    // old view_mode = hidden => to new type = 'hidden';
                    if ($optionModel->getGroupByType($option->getType())==Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT && $option->getViewMode()==2) {
                        $option->setType('hidden');
                        $option->setViewMode(1);
                    }
                                        
                    $value['type'] = $option->getType();
                    $value['is_require'] = $option->getIsRequire();
                    
                    $value['view_mode'] = isset($storeOptionsArr[$optionId]['view_mode'])?$storeOptionsArr[$optionId]['view_mode']:$option->getViewMode();
                    
                    $value['is_dependent'] = $option->getIsDependent();
                    $value['div_class'] = $option->getDivClass();
                    $value['sku_policy'] = $option->getSkuPolicy();
                    
                    $value['customoptions_is_onetime'] = $option->getCustomoptionsIsOnetime();
                    $value['qnty_input'] = ($option->getQntyInput()?'checked':'');
                    $value['qnty_input_disabled'] = (($option->getType()=='multiple' || $option->getType()=='hidden')?'disabled':'');
                    
                    $value['image_mode'] = $option->getImageMode();
                    $value['image_mode_disabled'] = (($optionModel->getGroupByType($option->getType())!=Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT)?'disabled':'');
                    $value['exclude_first_image'] = ($option->getExcludeFirstImage()?'checked':'');
                    
                    $value['description'] = $this->htmlEscape(isset($storeOptionsArr[$optionId]['description'])?$storeOptionsArr[$optionId]['description']:$option->getDescription());
                    if ($helper->isCustomerGroupsEnabled() && $option->getCustomerGroups() != null) {
                        $value['customer_groups'] = implode(',', $option->getCustomerGroups());
                    }
                    
                    $value['in_group_id'] = $option->getInGroupId();
                    $value['in_group_id_view'] = $option->getInGroupId();
                    
                    $value['sort_order'] = $this->_getSortOrder($option);                    

                    if ($this->getStoreId() != '0') {
                        $value['checkboxScopeTitle'] = $this->getCheckboxScopeHtml($option->getOptionId(), 'title', !isset($storeOptionsArr[$optionId]['title']));
                        $value['scopeTitleDisabled'] = !isset($storeOptionsArr[$optionId]['title']) ? 'disabled' : null;
                        
                        $value['checkboxScopeViewMode'] = $this->getCheckboxScopeHtml($option->getOptionId(), 'view_mode', !isset($storeOptionsArr[$optionId]['view_mode']));
                        $value['scopeViewModeDisabled'] = !isset($storeOptionsArr[$optionId]['view_mode']) ? 'disabled' : null;
                        
                        $value['checkboxScopeDescription'] = $this->getCheckboxScopeHtml($option->getOptionId(), 'description', !isset($storeOptionsArr[$optionId]['description']));
                        $value['scopeDescriptionDisabled'] = !isset($storeOptionsArr[$optionId]['description']) ? 'disabled' : null;
                    }                                        

                    if ($optionModel->getGroupByType($option->getType())==Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) {
                        $countValues = count($option->getValues());
                        if ($countValues>0) {
                            foreach ($option->getValues() as $key => $_value) {
                                $_value = new Varien_Object($_value);
                                $_value->setOptionTypeId($key);

                                if ($_value->getIsDelete() != '1') {
                                    $defaultArray = $option->getDefault() !== null ? $option->getDefault() : array();                                    
                                    
                                    if (isset($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['price'])) $_value->setPrice(floatval($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['price']));
                                    if (isset($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['price_type'])) $_value->setPriceType($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['price_type']);
                                    
                                    // for support old format:
                                    if (isset($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['special_price'])) $_value->setSpecialPrice(floatval($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['special_price']));
                                    if (isset($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['special_comment'])) $_value->setSpecialComment($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['special_comment']);
                                    if ($_value->getSpecialPrice()) {
                                        $_value->setSpecials(array(array(
                                            'customer_group_id' => 32000,
                                            'price' => $_value->getSpecialPrice(),
                                            'price_type' => 'fixed',
                                            'comment' => $_value->getSpecialComment(),
                                            'date_from' => '',
                                            'date_to' => ''
                                        )));
                                    }
                                    
                                    $helper->applyLinkedBySkuDataToOption($_value, $_value->getSku(), $store, 0);
                                    
                                    $helper->calculateOptionSpecialPrice($_value, null, $helper->isSpecialPriceEnabled());
                                    $priceDisabled = $_value->getIsSkuPrice();
                                    
                                    list($skuClass, $viewProductBySkuHtml) = $this->getViewSkuData($_value->getSku());
                                    
                                    if (!$helper->isSkuQtyLinkingEnabled() || $helper->getProductIdBySku($_value->getSku())==0) {
                                        $customoptionsQty = $_value->getCustomoptionsQty();
                                    } else {
                                        list($customoptionsQty, $backorders) = $helper->getCustomoptionsQty($_value->getCustomoptionsQty(), $_value->getSku(), 0, null, null, null, true);
                                    }                                    
                                    
                                    $value['optionValues'][$key] = array(
                                        'item_count' => $countValues,
                                        'option_id' => $option->getOptionId(),
                                        'option_type_id' => $_value->getOptionTypeId(),
                                        'title' => $this->htmlEscape(isset($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['title'])?$storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['title']:$_value->getTitle()),
                                        'price' => $this->getPriceValue($_value->getPrice(), $_value->getPriceType()),
                                        'price_type' => $_value->getPriceType(),
                                        'price_disabled' => $priceDisabled,
                                        'cost' => $this->getPriceValue($_value->getCost(), 'fixed'),
                                        'cost_disabled' => ($_value->getIsSkuCost() ? 'disabled' : ''),
                                        'customoptions_qty' => $customoptionsQty,
                                        'customoptions_qty_disabled' => ($helper->isSkuQtyLinkingEnabled() && $helper->getProductIdBySku($_value->getSku())?'disabled="disabled"':''),
                                        'sku' => $this->htmlEscape($_value->getSku()),
                                        'sku_class' => $skuClass,
                                        'view_product_by_sku_html' => $viewProductBySkuHtml,
                                        'image_button_label' => $helper->__('Add Image'),
                                        'sort_order' => $this->_getSortOrder($_value),
                                        'checked' => array_search($_value->getOptionTypeId(), $defaultArray) !== false ? 'checked' : '',
                                        'default_type' => (($option->getType()=='checkbox' || $option->getType()=='multiple' || $option->getType()=='multiswatch' || $option->getType()=='hidden') ? 'checkbox' : 'radio'),                                    
                                        'in_group_id' => $_value->getInGroupId(),
                                        'in_group_id_view' => $_value->getInGroupId(),
                                        'dependent_ids' => $_value->getDependentIds(),
                                        'weight' => number_format(floatval($_value->getWeight()), 4, null, ''),
                                        'weight_disabled' => ($_value->getIsSkuWeight() ? 'disabled' : '')
                                    );
                                    
                                    // getImages
                                    $images = $_value->getImages();
                                    
                                    if ($images) {
                                        $imagePath = $groupId . DS . $option->getId() . DS . $_value->getOptionTypeId() . DS;
                                        foreach($images as $fileName) {
                                            if (substr($fileName, 0, 1)=='#') { // color
                                                $colorArr = array(
                                                    'id' => $option->getId(),
                                                    'select_id' => $_value->getOptionTypeId(),
                                                    'image_file' => $fileName,
                                                    'option_type_image_id' => $fileName,
                                                    'source' => 2
                                                );
                                                $value['optionValues'][$key]['images'][] = $colorArr;
                                            } else { // file
                                                $imgArr = $helper->getImgData($imagePath . $fileName, $option->getId(), $_value->getOptionTypeId());
                                                if ($imgArr) {
                                                    $imgArr['option_type_image_id'] = $imgArr['file_name'];
                                                    $value['optionValues'][$key]['images'][] = $imgArr;
                                                }
                                            }
                                        }
                                    } elseif ($_value->getImagePath()) {
                                        // old format
                                        $imgArr = $helper->getImgData($_value->getImagePath(), $option->getId(), $_value->getOptionTypeId());
                                        if ($imgArr) {
                                            $imgArr['option_type_image_id'] = $imgArr['file_name'];
                                            $value['optionValues'][$key]['images'][] = $imgArr;
                                        }
                                    } else {
                                        $value['optionValues'][$key]['image_tr_style'] = 'display:none';
                                    }                                    
                                    
                                                                        
                                    //getOptionValueSpecialPrices
                                    $specialPrices = isset($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['specials'])?$storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['specials']:$_value->getSpecials();                                    
                                    if ($specialPrices) {
                                        foreach ($specialPrices as $specialKey=>$specialPrice) {
                                            $specialPrices[$specialKey]['price'] = $this->getPriceValue($specialPrice['price'], $specialPrice['price_type']);
                                            
                                            if (isset($specialPrice['date_from']) && $specialPrice['date_from']) {
                                                $specialPrices[$specialKey]['date_from'] = $zendDate->setDate($specialPrice['date_from'], Varien_Date::DATE_INTERNAL_FORMAT)->toString($dateFormat);
                                            } else {
                                                $specialPrices[$specialKey]['date_from'] = '';
                                            }
                                            
                                            if (isset($specialPrice['date_to']) && $specialPrice['date_to']) {
                                                $specialPrices[$specialKey]['date_to'] = $zendDate->setDate($specialPrice['date_to'], Varien_Date::DATE_INTERNAL_FORMAT)->toString($dateFormat);
                                            } else {
                                                $specialPrices[$specialKey]['date_to'] = '';
                                            }
                                        }
                                        usort($specialPrices, array($helper, '_sortPrices'));
                                        $value['optionValues'][$key]['specials'] = $specialPrices;
                                    }
                                    
                                    //getOptionValueTierPrices
                                    $tierPrices = isset($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['tiers'])?$storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['tiers']:$_value->getTiers();                                    
                                    if ($tierPrices) {
                                        foreach ($tierPrices as $tierKey=>$tierPrice) {
                                            $tierPrices[$tierKey]['price'] = $this->getPriceValue($tierPrice['price'], $tierPrice['price_type']);
                                        }
                                        usort($tierPrices, array($helper, '_sortPrices'));
                                        $value['optionValues'][$key]['tiers'] = $tierPrices;
                                    }
                                    
                                    if ($this->getStoreId()!='0') {
                                        $value['optionValues'][$key]['checkboxScopeTitle'] = $this->getCheckboxScopeHtml($option->getOptionId(), 'title', !isset($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['title']), $_value->getOptionTypeId());
                                        $value['optionValues'][$key]['scopeTitleDisabled'] = !isset($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['title']) ? 'disabled' : null;

                                        if ($scope == Mage_Core_Model_Store::PRICE_SCOPE_WEBSITE) {
                                            if (isset($storeOptionsArr[$optionId]['values'][$_value->getOptionTypeId()]['price'])) $scopePrice = true; else $scopePrice = false;
                                            if (!$priceDisabled) $value['optionValues'][$key]['checkboxScopePrice'] = $this->getCheckboxScopeHtml($option->getOptionId(), 'price', !$scopePrice, $_value->getOptionTypeId());
                                            $value['optionValues'][$key]['scopePriceDisabled'] = !$scopePrice ? 'disabled' : null;
                                        }
                                    }
                                }
                            }
                            $value['optionValues'] = array_values($value['optionValues']);
                        }                        
                        
                    } else {
                        
                        
                        if (isset($storeOptionsArr[$optionId]['price'])) $option->setPrice(floatval($storeOptionsArr[$optionId]['price']));
                        if (isset($storeOptionsArr[$optionId]['price_type'])) $option->setPriceType($storeOptionsArr[$optionId]['price_type']);
                        
                        $helper->applyLinkedBySkuDataToOption($option, $option->getSku(), $store, 0);
                        
                        $helper->calculateOptionSpecialPrice($option, null, false);
                        $priceDisabled = $option->getIsSkuPrice();
                        
                        list($skuClass, $viewProductBySkuHtml) = $this->getViewSkuData($option->getSku());
                        
                        $value['price'] = $this->getPriceValue($option->getPrice(), $option->getPriceType());
                        $value['price_type'] = $option->getPriceType();
                        $value['price_disabled'] = $priceDisabled;
                        $value['sku'] = $this->htmlEscape($option->getSku());
                        $value['sku_class'] = $skuClass;
                        $value['view_product_by_sku_html'] = $viewProductBySkuHtml;
                        $value['max_characters'] = $option->getMaxCharacters();
                        $value['default_text'] = $this->htmlEscape(isset($storeOptionsArr[$optionId]['default_text'])?$storeOptionsArr[$optionId]['default_text']:$option->getDefaultText());
                        $value['file_extension'] = $option->getFileExtension();
                        $value['image_size_x'] = $option->getImageSizeX();
                        $value['image_size_y'] = $option->getImageSizeY();
                        $value['image_button_label'] = $helper->__('Add Image');
                        
                        $imgHtml = $helper->getImgHtml($helper->getImgData($option->getImagePath(), $option->getId()));
                        if ($imgHtml) {
                            $value['image'] = $imgHtml;
                            $value['image_button_label'] = $helper->__('Change Image');
                        }

                        if ($this->getStoreId() != '0' && $scope == Mage_Core_Model_Store::PRICE_SCOPE_WEBSITE) {
                            if (!$priceDisabled) $value['checkboxScopePrice'] = $this->getCheckboxScopeHtml($option->getOptionId(), 'price', !isset($storeOptionsArr[$optionId]['price']));
                            $value['scopePriceDisabled'] = !isset($storeOptionsArr[$optionId]['price']) ? 'disabled' : null;
                            
                            $value['checkboxScopeDefaultText'] = $this->getCheckboxScopeHtml($option->getOptionId(), 'default_text', !isset($storeOptionsArr[$optionId]['default_text']));
                            $value['scopeDefaultTextDisabled'] = !isset($storeOptionsArr[$optionId]['default_text']) ? 'disabled' : null;
                        }
                    }
                    $values[] = new Varien_Object($value);
                }
            }            
            $this->_values = $values;
        }
        return $this->_values ? $this->_values : array();
    }

    private function _getSortOrder(Varien_Object $obj) {
        $sortOrder = $obj->getSortOrder();
        return empty($sortOrder) ? 0 : $sortOrder;
    }
}