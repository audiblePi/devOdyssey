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

class MageWorx_CustomOptions_Model_Importexport_Import_Entity_Product_M1800 extends Mage_ImportExport_Model_Import_Entity_Product
{
    protected $_particularAttributes = array(
        '_store', '_attribute_set', '_type', '_category', '_root_category', '_product_websites', '_tier_price_website',
        '_tier_price_customer_group', '_tier_price_qty', '_tier_price_price', '_links_related_sku',
        '_group_price_website', '_group_price_customer_group', '_group_price_price',
        '_links_related_position', '_links_crosssell_sku', '_links_crosssell_position', '_links_upsell_sku',
        '_links_upsell_position',
        
        // APO additions block
        '_absolute_price', '_absolute_weight', '_sku_policy',
        
        // Standart magento
        '_custom_option_store', '_custom_option_type', '_custom_option_title',
        '_custom_option_is_required', '_custom_option_price', '_custom_option_sku', '_custom_option_max_characters', '_custom_option_sort_order', 
        '_custom_option_file_extension', '_custom_option_image_size_x', '_custom_option_image_size_y',
        
        // APO additions block
        '_custom_option_view_mode', '_custom_option_customoptions_is_onetime', 
        '_custom_option_image_path', '_custom_option_customer_groups', 
        '_custom_option_qnty_input', '_custom_option_in_group_id', '_custom_option_is_dependent', '_custom_option_div_class',
        '_custom_option_image_mode', '_custom_option_exclude_first_image', '_custom_option_description', '_custom_option_default_text',
        '_custom_option_sku_policy',
        
        // Standart magento
        '_custom_option_row_title', '_custom_option_row_price',
        '_custom_option_row_sku', '_custom_option_row_sort',
        
        // APO additions block
        '_custom_option_row_customoptions_qty', '_custom_option_row_image_data', '_custom_option_row_default', 
        '_custom_option_row_in_group_id', '_custom_option_row_dependent_ids', '_custom_option_row_weight', '_custom_option_row_cost',
        '_custom_option_row_special_data', '_custom_option_row_tier_data',
        
        // Standart magento
        '_media_attribute_id', '_media_image', '_media_lable',
        '_media_position', '_media_is_disabled'
        
    );
    
    protected function _saveCustomOptions() {
        // to be less support :)
        $coreResource = Mage::getSingleton('core/resource');
        $write = $coreResource->getConnection('core_write');
        $write->query('set foreign_key_checks = 0');        
        
        $productTable   = $coreResource->getTableName('catalog/product');
        $optionTable    = $coreResource->getTableName('catalog/product_option');
        $priceTable     = $coreResource->getTableName('catalog/product_option_price');
        $titleTable     = $coreResource->getTableName('catalog/product_option_title');
        $typePriceTable = $coreResource->getTableName('catalog/product_option_type_price');
        $typeTitleTable = $coreResource->getTableName('catalog/product_option_type_title');
        $typeValueTable = $coreResource->getTableName('catalog/product_option_type_value');
        
        // APO additions block
        $optionModel = Mage::getModel('catalog/product_option');
        $viewModeTable = $coreResource->getTableName('customoptions/option_view_mode');
        $descriptionTable = $coreResource->getTableName('customoptions/option_description');
        $defaultTextTable = $coreResource->getTableName('customoptions/option_default');
        $typeValueImageTable = $coreResource->getTableName('customoptions/option_type_image');
        $typeValueSpecialPriceTable = $coreResource->getTableName('customoptions/option_type_special_price');
        $typeValueTierPriceTable = $coreResource->getTableName('customoptions/option_type_tier_price');
        
                
        if (version_compare(Mage::helper('customoptions')->getMagetoVersion(), '1.6.0', '>=')) {
            $nextOptionId   = Mage::getResourceHelper('importexport')->getNextAutoincrement($optionTable);
            $nextValueId    = Mage::getResourceHelper('importexport')->getNextAutoincrement($typeValueTable);
            $nextOptionTypePriceId    = Mage::getResourceHelper('importexport')->getNextAutoincrement($typePriceTable);
        } else {
            $nextOptionId   = $this->getNextAutoincrement($optionTable);
            $nextValueId    = $this->getNextAutoincrement($typeValueTable);
            $nextOptionTypePriceId    = $this->getNextAutoincrement($typePriceTable);
        }        
        
        $lastProductId = 0;
        
        $priceIsGlobal  = Mage::helper('catalog')->isPriceGlobal();
        $type           = null;
        $groupByType    = '';
        
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $customOptions = array(
                'product_id'    => array(),
                $optionTable    => array(),
                $priceTable     => array(),
                $titleTable     => array(),
                $typePriceTable => array(),
                $typeTitleTable => array(),
                $typeValueTable => array()
            );

            foreach ($bunch as $rowNum => $rowData) {
                $this->_filterRowData($rowData);
                if (!$this->isRowAllowedToImport($rowData, $rowNum)) {
                    continue;
                }
                if (self::SCOPE_DEFAULT == $this->getRowScope($rowData)) {
                    $productId = $this->_newSku[$rowData[self::COL_SKU]]['entity_id'];
                } elseif (!isset($productId)) {
                    continue;
                }
                if (!empty($rowData['_custom_option_store'])) {
                    if (!isset($this->_storeCodeToId[$rowData['_custom_option_store']])) {
                        continue;
                    }
                    $storeId = $this->_storeCodeToId[$rowData['_custom_option_store']];
                } else {
                    $storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;
                }
                
                
                if (!empty($rowData['_custom_option_type'])) { // get CO type if its specified
                    $type = $rowData['_custom_option_type'];
                    $groupByType = $optionModel->getGroupByType($type);
                    if (!$groupByType) {
                        $type = null;
                        continue;
                    }
                    $rowIsMain = true;
                } else {
                    if (null === $type) {
                        continue;
                    }
                    $rowIsMain = false;
                }
                if (!isset($customOptions['product_id'][$productId])) { // for update product entity table
                    $customOptions['product_id'][$productId] = array(
                        'entity_id'        => $productId,
                        'has_options'      => 0,
                        'required_options' => 0,
                        
                        // APO additions block
                        'absolute_price' => intval($rowData['_absolute_price']),
                        'absolute_weight' => intval($rowData['_absolute_weight']),
                        'sku_policy' => intval($rowData['_sku_policy']),
                        
                        // stabdart magento
                        'updated_at'       => now()
                    );
                }
                
                if ($rowIsMain) {
                    $solidParams = array(
                        'option_id'      => $nextOptionId,
                        'sku'            => '',
                        'max_characters' => 0,
                        'file_extension' => empty($rowData['_custom_option_file_extension']) ? null : $rowData['_custom_option_file_extension'],
                        'image_size_x'   => empty($rowData['_custom_option_image_size_x']) ? 0 : $rowData['_custom_option_image_size_x'],
                        'image_size_y'   => empty($rowData['_custom_option_image_size_y']) ? 0 : $rowData['_custom_option_image_size_y'],
                        'product_id'     => $productId,
                        'type'           => $type,
                        'is_require'     => empty($rowData['_custom_option_is_required']) ? 0 : 1,
                        'sort_order'     => empty($rowData['_custom_option_sort_order'])
                                            ? 0 : abs($rowData['_custom_option_sort_order']),
                        
                        // APO additions block
                        'customoptions_is_onetime' => $rowData['_custom_option_customoptions_is_onetime'],
                        'image_path' => (isset($rowData['_custom_option_image_path']) ? $rowData['_custom_option_image_path'] : ''),
                        'customer_groups' => (isset($rowData['_custom_option_customer_groups']) ? $rowData['_custom_option_customer_groups'] : ''),
                        'qnty_input' => (isset($rowData['_custom_option_qnty_input']) ? $rowData['_custom_option_qnty_input'] : ''),
                        'in_group_id' => (isset($rowData['_custom_option_in_group_id']) ? $rowData['_custom_option_in_group_id'] : ''),
                        'is_dependent' => (isset($rowData['_custom_option_is_dependent']) ? $rowData['_custom_option_is_dependent'] : ''),
                        'div_class' => (isset($rowData['_custom_option_div_class']) ? $rowData['_custom_option_div_class'] : ''),
                        'image_mode' => (isset($rowData['_custom_option_image_mode']) ? $rowData['_custom_option_image_mode'] : ''),
                        'exclude_first_image' => (isset($rowData['_custom_option_exclude_first_image']) ? $rowData['_custom_option_exclude_first_image'] : ''),
                        'sku_policy' => (isset($rowData['_custom_option_sku_policy']) ? $rowData['_custom_option_sku_policy'] : '')
                    );

                    
                    $customOptions[$optionTable][] = $solidParams;
                    $customOptions['product_id'][$productId]['has_options'] = 1;

                    if (!empty($rowData['_custom_option_is_required'])) {
                        $customOptions['product_id'][$productId]['required_options'] = 1;
                    }
                    $prevOptionId = $nextOptionId++; // increment option id, but preserve value for $typeValueTable
                }
                
                if (!empty($rowData['_custom_option_title'])) {
                    if (!isset($customOptions[$titleTable][$prevOptionId][0])) { // ensure default title is set
                        $customOptions[$titleTable][$prevOptionId][0] = $rowData['_custom_option_title'];
                    }
                    $customOptions[$titleTable][$prevOptionId][$storeId] = $rowData['_custom_option_title'];
                }
                
                if (!empty($rowData['_custom_option_price'])) {
                    if (!isset($customOptions[$priceTable][$prevOptionId][0])) { // ensure default price is set
                        $customOptions[$priceTable][$prevOptionId][0] = $rowData['_custom_option_price'];
                    }
                    $customOptions[$priceTable][$prevOptionId][$storeId] = $rowData['_custom_option_price'];
                }
                
                if ($groupByType==Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) {
                    if (empty($rowData['_custom_option_store']) && $storeId==0) {
                        // complex CO option row
                        $customOptions[$typeValueTable][$prevOptionId][] = array(
                            'option_type_id' => $nextValueId,
                            'sort_order'     => empty($rowData['_custom_option_row_sort'])
                                                ? 0 : abs($rowData['_custom_option_row_sort']),
                            'sku'            => !empty($rowData['_custom_option_row_sku'])
                                                ? $rowData['_custom_option_row_sku'] : '',


                            // APO additions block
                            'customoptions_qty' => $rowData['_custom_option_row_customoptions_qty'],
                            'default'           => $rowData['_custom_option_row_default'],
                            'in_group_id'       => $rowData['_custom_option_row_in_group_id'],
                            'dependent_ids'     => $rowData['_custom_option_row_dependent_ids'],
                            'weight'            => $rowData['_custom_option_row_weight'],
                            'cost'              => $rowData['_custom_option_row_cost']
                        );



                        // APO additions block
                        if ($rowData['_custom_option_row_image_data']) {
                            $imageArr = explode('|', $rowData['_custom_option_row_image_data']);
                            foreach ($imageArr as $image) {
                                list($imageFile, $sortOrder, $source) = explode(':', $image);
                                $customOptions[$typeValueImageTable][] = array(
                                    'option_type_id' => $nextValueId,
                                    'image_file'     => $imageFile,
                                    'sort_order'     => $sortOrder,
                                    'source'         => $source
                                );
                            }
                        }
                        // end APO additions block
                        $prevValueId = $nextValueId++;
                        if (!empty($rowData['_custom_option_type'])) $firstPrevValueId = $prevValueId;
                        $prevStoreId = 0;
                    } else {
                        if ($storeId!=$prevStoreId) {
                            $prevStoreId = $storeId;
                            $prevValueId = $firstPrevValueId;
                        } else {
                            $prevValueId++;
                        }
                    }
                }
                    
                if (!empty($rowData['_custom_option_row_title'])) {
                    if ($storeId>0 && !isset($customOptions[$typeTitleTable][$prevValueId][0])) { // ensure default title is set
                        $customOptions[$typeTitleTable][$prevValueId][0] = $rowData['_custom_option_row_title'];
                    }
                    $customOptions[$typeTitleTable][$prevValueId][$storeId] = $rowData['_custom_option_row_title'];
                }
                
                if (!empty($rowData['_custom_option_row_price'])) {
                    $typePriceRow = array(
                        'price'      => (float) rtrim($rowData['_custom_option_row_price'], '%'),
                        'price_type' => 'fixed'
                    );
                    if ('%' == substr($rowData['_custom_option_row_price'], -1)) {
                        $typePriceRow['price_type'] = 'percent';
                    }
                    if ($priceIsGlobal) {
                        $typePriceRow['option_type_price_id'] = $nextOptionTypePriceId;
                        $customOptions[$typePriceTable][$prevValueId][0] = $typePriceRow;
                    } else {
                        // ensure default price is set
                        if ($storeId>0 && !isset($customOptions[$typePriceTable][$prevValueId][0])) {
                            $typePriceRow['option_type_price_id'] = $nextOptionTypePriceId;
                            $customOptions[$typePriceTable][$prevValueId][0] = $typePriceRow;
                            $nextOptionTypePriceId++;
                        }
                        $typePriceRow['option_type_price_id'] = $nextOptionTypePriceId;
                        $customOptions[$typePriceTable][$prevValueId][$storeId] = $typePriceRow;
                    }
                    $prevOptionTypePriceId = $nextOptionTypePriceId++;
                }
                
                
                // APO additions block
                if ($rowData['_custom_option_row_special_data']) {
                    $specialArr = explode('|', $rowData['_custom_option_row_special_data']);
                    foreach ($specialArr as $special) {
                        list($customerGroupId, $price, $priceType, $comment) = explode(':', $special);
                        $customOptions[$typeValueSpecialPriceTable][] = array(
                            'option_type_price_id' => $prevOptionTypePriceId,
                            'customer_group_id' => $customerGroupId,
                            'price'             => $price,
                            'price_type'        => $priceType,
                            'comment'           => $comment
                        );
                    }
                }

                if ($rowData['_custom_option_row_tier_data']) {
                    $tierArr = explode('|', $rowData['_custom_option_row_tier_data']);
                    foreach ($tierArr as $tier) {
                        list($customerGroupId, $qty, $price, $priceType) = explode(':', $tier);
                        $customOptions[$typeValueTierPriceTable][] = array(
                            'option_type_price_id'  => $prevOptionTypePriceId,
                            'customer_group_id'     => $customerGroupId,
                            'qty'                   => $qty,
                            'price'                 => $price,
                            'price_type'            => $priceType                                
                        );
                    }
                }
                
                if ($rowData['_custom_option_view_mode']!=='' && !is_null($rowData['_custom_option_view_mode'])) {
                    if (!isset($customOptions[$viewModeTable][$prevOptionId][0])) { // ensure default view_mode is set
                        $customOptions[$viewModeTable][$prevOptionId][0] = intval($rowData['_custom_option_view_mode']);
                    }
                    $customOptions[$viewModeTable][$prevOptionId][$storeId] = intval($rowData['_custom_option_view_mode']);
                }
                
                if (!empty($rowData['_custom_option_description'])) {
                    if (!isset($customOptions[$descriptionTable][$prevOptionId][0])) { // ensure default description is set
                        $customOptions[$descriptionTable][$prevOptionId][0] = $rowData['_custom_option_description'];
                    }
                    $customOptions[$descriptionTable][$prevOptionId][$storeId] = $rowData['_custom_option_description'];
                }
                
                if (!empty($rowData['_custom_option_default_text'])) {
                    if (!isset($customOptions[$defaultTextTable][$prevOptionId][0])) { // ensure default default_text is set
                        $customOptions[$defaultTextTable][$prevOptionId][0] = $rowData['_custom_option_default_text'];
                    }
                    $customOptions[$defaultTextTable][$prevOptionId][$storeId] = $rowData['_custom_option_default_text'];
                }                
                // end APO additions block
                
            }

            if ($this->getBehavior() != Mage_ImportExport_Model_Import::BEHAVIOR_APPEND) { // remove old data?
                $productIds = array_keys($customOptions['product_id']);
                if (isset($productIds[0]) && $productIds[0]==$lastProductId) array_shift($productIds);
                if(!empty($productIds)) {
                    $this->_connection->delete(
                        $optionTable,
                        $this->_connection->quoteInto('product_id IN (?)', $productIds)
                    );
                    $lastProductId = array_pop($productIds);
                }
            }
            // if complex options does not contain values - ignore them
            foreach ($customOptions[$optionTable] as $key => $optionData) {
                if ($optionModel->getGroupByType($optionData['type'])==Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT && !isset($customOptions[$typeValueTable][$optionData['option_id']])) {
                    unset($customOptions[$optionTable][$key], $customOptions[$titleTable][$optionData['option_id']]);
                }
            }
            if ($customOptions[$optionTable] || $customOptions[$typeValueTable]) {
                if ($customOptions[$optionTable]) $this->_connection->insertMultiple($optionTable, $customOptions[$optionTable]);
            } else {
                continue; // nothing to save
            }
            
            $titleRows = array();
            foreach ($customOptions[$titleTable] as $optionId => $storeInfo) {
                foreach ($storeInfo as $storeId => $title) {
                    $titleRows[] = array('option_id' => $optionId, 'store_id' => $storeId, 'title' => $title);
                }
            }
            if ($titleRows) {
                $this->_connection->insertOnDuplicate($titleTable, $titleRows, array('title'));
            }
            
            $priceRows = array();
            foreach ($customOptions[$priceTable] as $optionId => $storeInfo) {
                foreach ($storeInfo as $storeId => $price) {
                    if ('%' == substr($price, -1)) $priceType = 'percent'; else $priceType = 'fixed';
                    $price = (float) rtrim($price, '%');
                    $priceRows[] = array('option_id' => $optionId, 'store_id' => $storeId, 'price' => $price, 'price_type' => $priceType);
                }
            }            
            if ($priceRows) {
                $this->_connection->insertOnDuplicate($priceTable, $priceRows, array('price', 'price_type'));
            }
            
            // APO additions block
            if (isset($customOptions[$viewModeTable])) {
                $viewModeRows = array();
                foreach ($customOptions[$viewModeTable] as $optionId => $storeInfo) {
                    foreach ($storeInfo as $storeId => $viewMode) {
                        $viewModeRows[] = array('option_id' => $optionId, 'store_id' => $storeId, 'view_mode' => $viewMode);
                    }
                }
                if ($viewModeRows) {
                    $this->_connection->insertOnDuplicate($viewModeTable, $viewModeRows, array('view_mode'));
                }
            }
            
            if (isset($customOptions[$descriptionTable])) {
                $descriptionRows = array();
                foreach ($customOptions[$descriptionTable] as $optionId => $storeInfo) {
                    foreach ($storeInfo as $storeId => $description) {
                        $descriptionRows[] = array('option_id' => $optionId, 'store_id' => $storeId, 'description' => $description);
                    }
                }
                if ($descriptionRows) {
                    $this->_connection->insertOnDuplicate($descriptionTable, $descriptionRows, array('description'));
                }
            }
            
            if (isset($customOptions[$defaultTextTable])) {
                $defaultTextRows = array();
                foreach ($customOptions[$defaultTextTable] as $optionId => $storeInfo) {
                    foreach ($storeInfo as $storeId => $defaultText) {
                        $defaultTextRows[] = array('option_id' => $optionId, 'store_id' => $storeId, 'default_text' => $defaultText);
                    }
                }
                if ($defaultTextRows) {
                    $this->_connection->insertOnDuplicate($defaultTextTable, $defaultTextRows, array('default_text'));
                }
            }
            // end APO additions block
            
            $typeValueRows = array();
            foreach ($customOptions[$typeValueTable] as $optionId => $optionInfo) {
                foreach ($optionInfo as $row) {
                    $row['option_id'] = $optionId;
                    $typeValueRows[]  = $row;
                }
            }
            if ($typeValueRows) {
                try {$this->_connection->insertMultiple($typeValueTable, $typeValueRows);} catch(Exception $e) {Mage::log("Import error ".$e->getMessage());}
            }
            
            
            // APO additions block
            if (isset($customOptions[$typeValueImageTable]) && $customOptions[$typeValueImageTable]) {
                try {$this->_connection->insertMultiple($typeValueImageTable, $customOptions[$typeValueImageTable]);} catch(Exception $e) {Mage::log("Import error ".$e->getMessage());}
            }
            if (isset($customOptions[$typeValueSpecialPriceTable]) && $customOptions[$typeValueSpecialPriceTable]) {
                try {$this->_connection->insertMultiple($typeValueSpecialPriceTable, $customOptions[$typeValueSpecialPriceTable]);} catch(Exception $e) {Mage::log("Import error ".$e->getMessage());}
            }
            if (isset($customOptions[$typeValueTierPriceTable]) && $customOptions[$typeValueTierPriceTable]) {
                try {$this->_connection->insertMultiple($typeValueTierPriceTable, $customOptions[$typeValueTierPriceTable]);} catch(Exception $e) {Mage::log("Import error ".$e->getMessage());}
            }
            // end APO additions block
            
            $optionTypePriceRows = array();
            $optionTypeTitleRows = array();

            foreach ($customOptions[$typePriceTable] as $optionTypeId => $storesData) {
                foreach ($storesData as $storeId => $row) {
                    $row['option_type_id'] = $optionTypeId;
                    $row['store_id']       = $storeId;
                    $optionTypePriceRows[] = $row;
                }
            }
            foreach ($customOptions[$typeTitleTable] as $optionTypeId => $storesData) {
                foreach ($storesData as $storeId => $title) {
                    $optionTypeTitleRows[] = array(
                        'option_type_id' => $optionTypeId,
                        'store_id'       => $storeId,
                        'title'          => $title
                    );
                }
            }
            if ($optionTypePriceRows) {
                $this->_connection->insertOnDuplicate(
                    $typePriceTable,
                    $optionTypePriceRows,
                    array('price', 'price_type')
                );
            }
            if ($optionTypeTitleRows) {
                $this->_connection->insertOnDuplicate($typeTitleTable, $optionTypeTitleRows, array('title'));
            }
            if ($customOptions['product_id']) { // update product entity table to show that product has options
                $this->_connection->insertOnDuplicate(
                    $productTable,
                    $customOptions['product_id'],
                    array('has_options', 'required_options', 'absolute_price', 'absolute_weight', 'sku_policy', 'updated_at')
                );
            }
        }
        return $this;
    }
    
    protected function _deleteProducts() {
        parent::_deleteProducts();
        // fix and clean up the debris of tables whith options
        $resource = Mage::getSingleton('core/resource');
        $this->_connection->query("
            DELETE t1 FROM `{$resource->getTableName('catalog_product_option')}` AS t1 WHERE (SELECT COUNT(*) FROM `{$resource->getTableName('catalog_product_entity')}` WHERE `entity_id` = t1.`product_id`) = 0;
            DELETE t1 FROM `{$resource->getTableName('catalog_product_option_title')}` AS t1 WHERE (SELECT COUNT(*) FROM `{$resource->getTableName('catalog_product_option')}` WHERE `option_id` = t1.`option_id`) = 0;
            DELETE t1 FROM `{$resource->getTableName('catalog_product_option_price')}` AS t1 WHERE (SELECT COUNT(*) FROM `{$resource->getTableName('catalog_product_option')}` WHERE `option_id` = t1.`option_id`) = 0;
            DELETE t1 FROM `{$resource->getTableName('catalog_product_option_type_value')}` AS t1 WHERE (SELECT COUNT(*) FROM `{$resource->getTableName('catalog_product_option')}` WHERE `option_id` = t1.`option_id`) = 0;
            DELETE t1 FROM `{$resource->getTableName('catalog_product_option_type_title')}` AS t1 WHERE (SELECT COUNT(*) FROM `{$resource->getTableName('catalog_product_option_type_value')}` WHERE `option_type_id` = t1.`option_type_id`) = 0;
            DELETE t1 FROM `{$resource->getTableName('catalog_product_option_type_price')}` AS t1 WHERE (SELECT COUNT(*) FROM `{$resource->getTableName('catalog_product_option_type_value')}` WHERE `option_type_id` = t1.`option_type_id`) = 0;
            DELETE t1 FROM `{$resource->getTableName('custom_options_relation')}` AS t1 WHERE (SELECT COUNT(*) FROM `{$resource->getTableName('catalog_product_option')}` WHERE `option_id` = t1.`option_id`) = 0;
            DELETE t1 FROM `{$resource->getTableName('custom_options_option_view_mode')}` AS t1 WHERE (SELECT COUNT(*) FROM `{$resource->getTableName('catalog_product_option')}` WHERE `option_id` = t1.`option_id`) = 0;
            DELETE t1 FROM `{$resource->getTableName('custom_options_option_description')}` AS t1 WHERE (SELECT COUNT(*) FROM `{$resource->getTableName('catalog_product_option')}` WHERE `option_id` = t1.`option_id`) = 0;
            DELETE t1 FROM `{$resource->getTableName('custom_options_option_default')}` AS t1 WHERE (SELECT COUNT(*) FROM `{$resource->getTableName('catalog_product_option')}` WHERE `option_id` = t1.`option_id`) = 0;
            DELETE t1 FROM `{$resource->getTableName('custom_options_option_type_image')}` AS t1 WHERE (SELECT COUNT(*) FROM `{$resource->getTableName('catalog_product_option_type_value')}` WHERE `option_type_id` = t1.`option_type_id`) = 0;
        ");
        return $this;
    }
    
    protected function _importData() {
        $result = parent::_importData();
        $resource = Mage::getSingleton('core/resource');
        $this->_connection->query("DELETE FROM `{$resource->getTableName('core_resource')}` WHERE `code` = 'customoptions_setup';");
        return $result;
    }
    
    protected function _filterRowData(&$rowData) {
        $rowData = array_filter($rowData, 'strlen');
        if (!isset($rowData[self::COL_SKU])) {
            $rowData[self::COL_SKU] = null;
        }
    }
    
}