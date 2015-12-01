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

class MageWorx_CustomOptions_Model_Catalog_Product_Option extends Mage_Catalog_Model_Product_Option {

    const OPTION_TYPE_SWATCH = 'swatch';
    const OPTION_TYPE_MULTISWATCH = 'multiswatch';
    const OPTION_TYPE_HIDDEN = 'hidden';
    
    protected function _construct() {
        parent::_construct();
        $this->_init('customoptions/product_option');       
    }    
    
    public function decodeViewIGI($IGI) {
        $prefix = '';
        if (substr($IGI, 0, 1)=='i') {
            $IGI = substr($IGI, 1);
            $prefix = 'i';
        }
        $tmp = explode('x', $IGI);
        if (count($tmp)<2) return $prefix . intval($IGI);        
        return $prefix . ((intval($tmp[0])*65535) + intval($tmp[1]));
    }
    

    public function _prepareOptions($options, $templateId, $groupIsActive = 2) {
        $out = array();
        foreach ($options as $key=>$op) {                
            //unset($op['option_id']);

            if (isset($op['id']) && ($op['type']=='field' || $op['type']=='area') && !isset($op['image_path'])) {
                if (Mage::helper('customoptions')->isCustomOptionsFile($templateId, $op['id'])) {                        
                    $op['image_path'] = $templateId . DS . $op['id'] . DS;
                } else {
                    $op['image_path'] = '';
                }                    
            }
            $op['customer_groups'] = isset($op['customer_groups']) ? implode(',', $op['customer_groups']) : '';
            if ($groupIsActive==2) $op['view_mode'] = 0;                

            if (isset($op['in_group_id'])) {
                $op['in_group_id'] = ((intval($op['in_group_id'])>0)?($templateId * 65535) + intval($op['in_group_id']):0);
                $key = 'IGI'.$op['in_group_id'];
            }    
            if (!isset($op['sku'])) $op['sku'] = '';
            if (!isset($op['max_characters'])) $option['max_characters'] = null;
            if (!isset($op['file_extension'])) $option['file_extension'] = null;
            if (!isset($op['image_size_x'])) $option['image_size_x'] = 0;
            if (!isset($op['image_size_y'])) $option['image_size_y'] = 0;

            if (isset($op['qnty_input'])) $op['qnty_input'] = intval($op['qnty_input']);
            if (isset($op['exclude_first_image'])) $op['exclude_first_image'] = intval($op['exclude_first_image']);

            if ($this->getGroupByType($op['type'])==self::OPTION_GROUP_SELECT) {
                if (isset($op['values']) && is_array($op['values'])) {
                    $defaultArray = isset($op['default']) ? $op['default'] : array();
                    $tm = array();
                    foreach ($op['values'] as $k=>$value) {
                        $value['default'] = (array_search($k, $defaultArray)!==false?1:0);
                        
                        // prepare images
                        if (isset($value['images']) && is_array($value['images']) && count($value['images'])>0) {
                            $imagePath = $templateId . DS . $op['id'] . DS . $k . DS;
                            foreach($value['images'] as $i=>$fileName) {
                                if (substr($fileName, 0, 1)=='#') { // color
                                    $imageFile = $fileName;
                                } else { // file
                                    $imageFile = $imagePath . $fileName;
                                }
                                $value['images'][$i] = $imageFile;
                            }
                        } elseif (isset($value['image_path']) && $value['image_path']) { 
                            // old version compatibility
                            $result = Mage::helper('customoptions')->getCheckImgPath($value['image_path']);
                            if ($result) {
                                list($imagePath, $fileName) = $result;
                                $value['images'][] = $imagePath . $fileName;
                            }
                        }

                        if (isset($value['dependent_ids']) && $value['dependent_ids']!='') {                                
                            $dependentIds = array();
                            $dependentIdsTmp = explode(',', $value['dependent_ids']);
                            foreach ($dependentIdsTmp as $d_id) {
                                if (intval($d_id)>0) $dependentIds[] = ($templateId * 65535) + intval($d_id);
                            }
                            $value['dependent_ids'] = implode(',', $dependentIds);
                        }

                        if (!isset($value['customoptions_qty'])) $value['customoptions_qty'] = '';
                        if (substr($value['customoptions_qty'], 0, 1)=='i') {
                            $value['customoptions_qty'] = 'i' . (($templateId * 65535) + intval(substr($value['customoptions_qty'], 1)));
                        }                        
                        
                        if (isset($value['in_group_id'])) {
                            $value['in_group_id'] = ($templateId * 65535) + intval($value['in_group_id']);
                            $k = 'IGI'.$value['in_group_id'];
                        }    
                        $tm[$k] = $value;                                            
                    }
                    $op['values'] = $tm;
                } else {
                    $op['values'] = array();
                }
            }                

            $out[$key] = $op;
        }              
        return $out;
    }

    public function removeProductOptionsAndRelationByGroup($templateId) {
        $result = false;
        if ($templateId>0) {
            $result = $this->removeProductOptions($templateId);
            Mage::getResourceSingleton('customoptions/relation')->deleteGroup($templateId); // just in case
        }
        return $result;
    }
    
    // comparison arrays
    public function comparisonArrays($newOptions, $prevOptions) {
        $diffOptions = array();
        foreach ($newOptions as $key=>$op) {
            if (isset($prevOptions[$key])) {
                if (is_array($op)) {
                    $result = $this->comparisonArrays($op, $prevOptions[$key]);
                    if ($result) $diffOptions[$key] = $result;
                } else {
                    if ($prevOptions[$key]!=$op) $diffOptions[$key] = $op;
                }
            } else {                    
                $diffOptions[$key] = $op;
            }    
        }        
        return $diffOptions;        
    }
    
    
    public function saveProductOptions($newOptions, array $prevOptions, array $productIds, Varien_Object $group, $prevGroupIsActive = 1, $place = 'apo', $prevStoreOptionsData = array()) {
        if (isset($productIds) && is_array($productIds) && count($productIds)>0) {
            $relation = Mage::getResourceSingleton('customoptions/relation');            

            $templateId = $group->getId();
            $groupIsActive = $group->getIsActive();

            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $tablePrefix = (string) Mage::getConfig()->getTablePrefix();

            $condition = '';
            if ($place == 'product') {
                $condition = ' AND product_id IN (' . implode(',', $productIds) . ')';
            }

            // get and prepare $optionRelations
            $select = $connection->select()->from($tablePrefix . 'custom_options_relation')->where('group_id = ' . $templateId . $condition);
            $optionRelations = $connection->fetchAll($select);
            if (is_array($optionRelations) && count($optionRelations)>0) {
                $tmp = array();
                foreach ($optionRelations as $option) {
                    $tmp[$option['product_id']][$option['option_id']] = $option;
                }
                $optionRelations = $tmp;
            } else {
                $optionRelations = array();
            }                        
            
            if (isset($newOptions) && is_array($newOptions)) {
            
                $newOptions = $this->_prepareOptions($newOptions, $templateId, $groupIsActive);
                $prevOptions = $this->_prepareOptions($prevOptions, $templateId, $prevGroupIsActive);            

                // comparison arrays
                $diffOptions = $this->comparisonArrays($newOptions, $prevOptions);
                
                // get all store options            
                $select = $connection->select()->from($tablePrefix . 'custom_options_group_store')->where('group_id = '.$templateId);
                $allStoreOptions = $connection->fetchAll($select);            
                foreach ($allStoreOptions as $key=>$storeOptions) {
                    if ($storeOptions['hash_options']) $hashOptions = unserialize($storeOptions['hash_options']); else $hashOptions = array();
                    if (isset($prevStoreOptionsData['store_id']) && $storeOptions['store_id']==$prevStoreOptionsData['store_id']) {
                        if ($prevStoreOptionsData['hash_options']) $prevHashOptions = unserialize($prevStoreOptionsData['hash_options']); else $prevHashOptions = array();
                        // link to reset no deault!!
                        foreach ($prevHashOptions as $optionId=>$option) {
                            // add to check remove store option
                            if (!isset($hashOptions[$optionId])) $hashOptions[$optionId] = array('option_id' => $option['option_id'], 'type'=>$option['type']);                            
                            if (isset($option['values'])) {
                                foreach ($option['values'] as $valueId=>$value) {
                                    // add to check remove store option value
                                    if (!isset($hashOptions[$optionId]['values'][$valueId])) {
                                        $hashOptions[$optionId]['values'][$valueId]['option_type_id'] = $value['option_type_id'];
                                    } else {
                                        // add prev specials data
                                        if (isset($value['specials'])) {
                                            $hashOptions[$optionId]['values'][$valueId]['prev_specials'] = $value['specials'];
                                        }
                                        // add prev tiers data
                                        if (isset($value['tiers'])) {
                                            $hashOptions[$optionId]['values'][$valueId]['prev_tiers'] = $value['tiers'];
                                        }
                                    }
                                }
                            }
                        }
                    }                
                    $allStoreOptions[$key]['hash_options'] = $hashOptions;
                }

//                print_r($newOptions);
//                print_r($prevOptions);
//                print_r($diffOptions);
//                exit;
                
                foreach ($productIds as $productId) {                
                    $realOptionIds = array();
                    $options = $newOptions; // work copy options
                    
                    // $optionRelations
                    // update options
                    if (isset($optionRelations[$productId])) {
                        foreach ($optionRelations[$productId] as $optionId=>$prevOption) {
                            //$optionId = $prevOption['option_id'];
                            $prevOption = Mage::getModel('catalog/product_option')->load($optionId);
                            if (isset($options['IGI'.$prevOption->getInGroupId()]) && (!isset($options['IGI'.$prevOption->getInGroupId()]['is_delete']) || $options['IGI'.$prevOption->getInGroupId()]['is_delete']!=1)) {
                                $option = $options['IGI'.$prevOption->getInGroupId()];                                
                                if (isset($diffOptions['IGI'.$prevOption->getInGroupId()])) $diffOption = $diffOptions['IGI'.$prevOption->getInGroupId()]; else $diffOption = array();                                
                                $this->saveOption($productId, $diffOption, $optionId, 0, $option['type']);                                
                                $realOptionIds[$option['option_id']]['value'] = $optionId;
                                
                                if ($this->getGroupByType($option['type'])==self::OPTION_GROUP_SELECT) {
                                    $select = $connection->select()->from($tablePrefix . 'catalog_product_option_type_value')->where('option_id = '.$optionId);
                                    $prevValues = $connection->fetchAll($select);
                                    if (is_array($prevValues) && count($prevValues)>0) {
                                        foreach ($prevValues as $prValue) {
                                            if (isset($option['values']['IGI'.$prValue['in_group_id']]) && (!isset($option['values']['IGI'.$prValue['in_group_id']]['is_delete']) || $option['values']['IGI'.$prValue['in_group_id']]['is_delete']!=1)) {
                                                // update option value
                                                if (isset($prevOptions['IGI'.$prevOption->getInGroupId()]['values']['IGI'.$prValue['in_group_id']])) $prevValue = $prevOptions['IGI'.$prevOption->getInGroupId()]['values']['IGI'.$prValue['in_group_id']]; else $prevValue = array();
                                                if (isset($newOptions['IGI'.$prevOption->getInGroupId()]['values']['IGI'.$prValue['in_group_id']])) $newValue = $newOptions['IGI'.$prevOption->getInGroupId()]['values']['IGI'.$prValue['in_group_id']]; else $newValue = array();
                                                if (isset($diffOption['values']['IGI'.$prValue['in_group_id']])) $diffValue = $diffOption['values']['IGI'.$prValue['in_group_id']]; else $diffValue = array();
                                                if (isset($diffValue['customoptions_qty']) && !$group->getUpdateInventory()) unset($diffValue['customoptions_qty']);
                                                $this->saveOptionValue($optionId, $diffValue, $prevValue, $newValue, $prValue['option_type_id'], 0);
                                                $realOptionIds[$option['option_id']][$option['values']['IGI'.$prValue['in_group_id']]['option_type_id']] = $prValue['option_type_id'];
                                                unset($option['values']['IGI'.$prValue['in_group_id']]);
                                            } else {
                                                // delete option value
                                                $connection->delete($tablePrefix . 'catalog_product_option_type_value', 'option_type_id = ' . $prValue['option_type_id']);
                                            }
                                        }
                                    }                                    
                                    // insert option values
                                    if (count($option['values'])>0) {
                                        foreach ($option['values'] as $value) {
                                            if (isset($value['is_delete']) && $value['is_delete']==1) continue;
                                            if ($group->getOnlyUpdate() && !isset($diffOptions['IGI'.$option['in_group_id']]['values']['IGI'.$value['in_group_id']]['in_group_id'])) continue;
                                            
                                            $this->saveOptionValue($optionId, $value, array(), $value, false, 0);
                                        }
                                    }

                                }                                    

                                unset($options['IGI'.$prevOption->getInGroupId()]);
                                
                            } else {
                                if (isset($prevOption['option_id'])) {
                                    // delete option
                                    $connection->delete($tablePrefix . 'catalog_product_option', 'option_id = ' . $prevOption['option_id']);
                                    $connection->delete($tablePrefix . 'catalog_product_option_type_value', 'option_id = ' . $prevOption['option_id']);
                                    $connection->delete($tablePrefix . 'custom_options_option_view_mode', 'option_id = ' . $prevOption['option_id']);
                                    $connection->delete($tablePrefix . 'custom_options_option_description', 'option_id = ' . $prevOption['option_id']);
                                    $connection->delete($tablePrefix . 'custom_options_option_default', 'option_id = ' . $prevOption['option_id']);
                                    $connection->delete($tablePrefix . 'custom_options_relation', 'group_id = '. $templateId .' AND product_id = '.$productId.' AND option_id = ' . $prevOption['option_id']);
                                }
                            }                            
                        }
                    }                                        
                    
                    // insert default options
                    foreach ($options as $option) {
                        if (isset($option['is_delete']) && $option['is_delete'] == 1) continue;
                        if ($group->getOnlyUpdate() && isset($optionRelations[$productId]) && !isset($diffOptions['IGI'.$option['in_group_id']])) continue;
                        
                        
                        $optionId = $this->saveOption($productId, $option, false, 0, $option['type']);
                        $realOptionIds[$option['option_id']]['value'] = $optionId;
                        $optionRelation = array(
                            'option_id' => $optionId,
                            'group_id' => $templateId,
                            'product_id' => $productId,
                        );
                        $connection->insert($tablePrefix . 'custom_options_relation', $optionRelation);
                        
                        // insert option values
                        if ($this->getGroupByType($option['type'])==self::OPTION_GROUP_SELECT && count($option['values'])>0) {
                            foreach ($option['values'] as $value) {
                                if (isset($value['is_delete']) && $value['is_delete'] == 1) continue;
                                if ($group->getOnlyUpdate() && isset($optionRelations[$productId]) && !isset($diffOptions['IGI'.$option['in_group_id']]['values']['IGI'.$value['in_group_id']]['in_group_id'])) continue;
                                
                                $optionTypeId = $this->saveOptionValue($optionId, $value, array(), $value, false, 0);
                                $realOptionIds[$option['option_id']][$value['option_type_id']] = $optionTypeId;
                            }
                        }
                        
                    }
                    

                    // insert all store options
                    //print_r($allStoreOptions); exit;
                    foreach ($allStoreOptions as $storeOptions) {
                        foreach ($storeOptions['hash_options'] as $option) {
                            if (isset($realOptionIds[$option['option_id']]['value']) && $realOptionIds[$option['option_id']]['value']) $optionId = $this->saveOption($productId, $option, $realOptionIds[$option['option_id']]['value'], $storeOptions['store_id'], $option['type']); else $optionId = false;
                            // insert option values
                            if ($optionId && $this->getGroupByType($option['type'])==self::OPTION_GROUP_SELECT && count($option['values'])>0) {
                                foreach ($option['values'] as $value) {
                                    if (isset($realOptionIds[$option['option_id']][$value['option_type_id']]) && $realOptionIds[$option['option_id']][$value['option_type_id']]) {
                                        $prevValue = $value;
                                        if (isset($value['prev_tiers'])) $prevValue['tiers'] = $value['prev_tiers'];
                                        if (isset($value['prev_specials'])) $prevValue['specials'] = $value['prev_specials'];
                                        $this->saveOptionValue($optionId, $value, $prevValue, $value, $realOptionIds[$option['option_id']][$value['option_type_id']], $storeOptions['store_id']);
                                    }
                                }
                            }
                        }                        
                    }
                                       
                    if (isset($optionRelations[$productId])) {
                        unset($optionRelations[$productId]);
                    }
                    
                    $this->updateProductFlags($productId, $group->getAbsolutePrice(), $group->getAbsoluteWeight(), $group->getSkuPolicy());                    
                }                
            }
                        
            // remnants of the options that must be removed
            if (count($optionRelations)>0) {
                foreach ($optionRelations as $productId=>$prevOptions) {
                    if (count($prevOptions)>0 && !in_array($productId, $productIds)) {
                        foreach ($prevOptions as $prevOption) {
                            $connection->delete($tablePrefix . 'catalog_product_option', 'option_id = ' . $prevOption['option_id']);
                            $connection->delete($tablePrefix . 'custom_options_option_view_mode', 'option_id = ' . $prevOption['option_id']);
                            $connection->delete($tablePrefix . 'custom_options_option_description', 'option_id = ' . $prevOption['option_id']);
                            $connection->delete($tablePrefix . 'custom_options_option_default', 'option_id = ' . $prevOption['option_id']);
                            $connection->delete($tablePrefix . 'custom_options_relation', 'group_id = '. $templateId .' AND product_id = '.$productId.' AND option_id = ' . $prevOption['option_id']);
                        }
                        $this->updateProductFlags($productId, $group->getAbsolutePrice(), $group->getAbsoluteWeight(), $group->getSkuPolicy());
                    }
                }
            }    
            
            
        }
                
    }
    
    public function saveOption($productId, $option, $optionId = 0, $storeId = 0, $type = '') {
        
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        
        if ($storeId==0) {
            $optionData = array();
            
            // old view_mode = hidden => to new type = 'hidden';
            if (isset($option['type']) && $option['type'] && $this->getGroupByType($option['type'])==Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT 
                    && isset($option['view_mode']) && $option['view_mode']==2) {
                $option['type'] = 'hidden';
                $option['view_mode'] = 1;
            }
            
            if (isset($option['type'])) $optionData['type'] = $option['type'];
            if (isset($option['is_require'])) $optionData['is_require'] = $option['is_require'];
            if (isset($option['sku'])) $optionData['sku'] = $option['sku'];
            if (isset($option['max_characters'])) $optionData['max_characters'] = $option['max_characters'];
            if (isset($option['file_extension'])) $optionData['file_extension'] = $option['file_extension'];
            if (isset($option['image_path'])) $optionData['image_path'] = $option['image_path'];
            if (isset($option['image_size_x'])) $optionData['image_size_x'] = $option['image_size_x'];
            if (isset($option['image_size_y'])) $optionData['image_size_y'] = $option['image_size_y'];
            if (isset($option['sort_order'])) $optionData['sort_order'] = $option['sort_order'];
            if (isset($option['customoptions_is_onetime'])) $optionData['customoptions_is_onetime'] = $option['customoptions_is_onetime'];
            if (isset($option['customer_groups'])) $optionData['customer_groups'] = $option['customer_groups'];
            if (isset($option['qnty_input'])) $optionData['qnty_input'] = $option['qnty_input'];
            if (isset($option['in_group_id'])) $optionData['in_group_id'] = $option['in_group_id'];
            if (isset($option['is_dependent'])) $optionData['is_dependent'] = $option['is_dependent'];
            if (isset($option['div_class'])) $optionData['div_class'] = $option['div_class'];
            if (isset($option['sku_policy'])) $optionData['sku_policy'] = $option['sku_policy'];
            if (isset($option['image_mode'])) $optionData['image_mode'] = $option['image_mode'];
            if (isset($option['exclude_first_image'])) $optionData['exclude_first_image'] = $option['exclude_first_image'];

            if (count($optionData)>0) $optionData['product_id'] = $productId;        

            if ($optionId) {            
                $updateFlag = true;
                if (count($optionData)>0) $connection->update($tablePrefix . 'catalog_product_option', $optionData, 'option_id = '.$optionId);
            } else {
                $updateFlag = false;
                $connection->insert($tablePrefix . 'catalog_product_option', $optionData);
                $optionId = $connection->lastInsertId($tablePrefix . 'catalog_product_option');            
            }
        }    
                
        if (isset($option['title'])) {
            $optionTitle = array (    
                'option_id' => $optionId,
                'store_id' => $storeId,
                'title' => $option['title']
            );
            
            if ($storeId>0) {
                $select = $connection->select()->from($tablePrefix . 'catalog_product_option_title', array('title'))->where('option_id = '.$optionId.' AND `store_id` = '.$storeId);
                $updateFlag = $connection->fetchOne($select);
            }            
            if ($option['title']!==$updateFlag) {
                if ($updateFlag) {
                    $connection->update($tablePrefix . 'catalog_product_option_title', $optionTitle, 'option_id = '.$optionId.' AND store_id = '.$storeId);
                } else {
                    $connection->insert($tablePrefix . 'catalog_product_option_title', $optionTitle);
                }
            }    
        } elseif ($storeId>0) {
            $connection->delete($tablePrefix . 'catalog_product_option_title', 'option_id = '.$optionId.' AND store_id = '.$storeId);
        }
        
        if (isset($option['view_mode'])) {
            $optionMode = array(
                'option_id' => $optionId,
                'store_id' => $storeId,
                'view_mode' => $option['view_mode']
            );
            $select = $connection->select()->from($tablePrefix . 'custom_options_option_view_mode', array('view_mode'))->where('option_id = '.$optionId.' AND `store_id` = '.$storeId);
            $updateViewModeFlag = $connection->fetchOne($select);
            if ($option['view_mode']!==$updateViewModeFlag) {
                if ($updateViewModeFlag!==false) {
                    $connection->update($tablePrefix . 'custom_options_option_view_mode', $optionMode, 'option_id = '.$optionId.' AND `store_id` = '.$storeId);
                } else {
                    $connection->insert($tablePrefix . 'custom_options_option_view_mode', $optionMode);
                }
            }    
        } elseif ($storeId>0 || isset($option['view_mode'])) {
            $connection->delete($tablePrefix . 'custom_options_option_view_mode', 'option_id = '.$optionId.' AND store_id = '.$storeId);
        }
        
        
        if (isset($option['description']) && $option['description']!='') {
            $optionDesc = array(
                'option_id' => $optionId,
                'store_id' => $storeId,
                'description' => $option['description']
            );
            $select = $connection->select()->from($tablePrefix . 'custom_options_option_description', array('description'))->where('option_id = '.$optionId.' AND `store_id` = '.$storeId);
            $updateDescriptionFlag = $connection->fetchOne($select);
            if ($option['description']!==$updateDescriptionFlag) {
                if ($updateDescriptionFlag) {
                    $connection->update($tablePrefix . 'custom_options_option_description', $optionDesc, 'option_id = '.$optionId.' AND `store_id` = '.$storeId);
                } else {
                    $connection->insert($tablePrefix . 'custom_options_option_description', $optionDesc);
                }
            }    
        } elseif ($storeId>0 || isset($option['description']) && $option['description']=='') {
            $connection->delete($tablePrefix . 'custom_options_option_description', 'option_id = '.$optionId.' AND store_id = '.$storeId);
        }
        
        if (isset($option['default_text']) && $option['default_text']!='') {
            $optionDef = array(
                'option_id' => $optionId,
                'store_id' => $storeId,
                'default_text' => $option['default_text']
            );            
            $select = $connection->select()->from($tablePrefix . 'custom_options_option_default', array('default_text'))->where('option_id = '.$optionId.' AND `store_id` = '.$storeId);
            $updateDefaultTextFlag = $connection->fetchOne($select);
            if ($option['default_text']!==$updateDefaultTextFlag) {
                if ($updateDefaultTextFlag) {
                    $connection->update($tablePrefix . 'custom_options_option_default', $optionDef, 'option_id = '.$optionId.' AND `store_id` = '.$storeId);
                } else {
                    $connection->insert($tablePrefix . 'custom_options_option_default', $optionDef);
                }
            }    
        } elseif ($storeId>0 || (isset($option['default_text']) && $option['default_text']=='')) {
            $connection->delete($tablePrefix . 'custom_options_option_default', 'option_id = '.$optionId.' AND store_id = '.$storeId);
        }                
        
        if ($type=='field' || $type=='area' || $type=='file' || $type=='date' || $type=='date_time' || $type=='time') {
            $optionPrice = array();
            if (isset($option['price'])) $optionPrice['price'] = $option['price'];
            if (isset($option['price_type'])) $optionPrice['price_type'] = $option['price_type'];
            if (count($optionPrice)>0) {
                $optionPrice['option_id'] = $optionId;
                $optionPrice['store_id'] = $storeId;
                if ($storeId>0) {
                    $select = $connection->select()->from($tablePrefix . 'catalog_product_option_price', array('price', 'price_type'))->where('option_id = '.$optionId.' AND `store_id` = '.$storeId);
                    $updateFlag = $connection->fetchRow($select);
                }
                
                if (!is_array($updateFlag) || (isset($option['price']) && $option['price']!=$updateFlag['price']) || (isset($option['price_type']) && $option['price_type']!=$updateFlag['price_type'])) {
                    if ($updateFlag) {
                        $connection->update($tablePrefix . 'catalog_product_option_price', $optionPrice, 'option_id = '.$optionId.' AND `store_id` = '.$storeId);
                    } else {
                        $connection->insert($tablePrefix . 'catalog_product_option_price', $optionPrice);
                    }
                }    
            } elseif ($storeId>0) {
                $connection->delete($tablePrefix . 'catalog_product_option_price', 'option_id = '.$optionId.' AND store_id = '.$storeId);
            }   
        }
        
        return $optionId;
    }
    
    
    // ($value - diff(part)) to save, ($prevValue - prev(full), $newValue - new(full)) - to remove previos or update
    public function saveOptionValue($optionId, $value, $prevValue, $newValue, $optionTypeId = 0, $storeId = 0) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        
        if ($storeId==0) {
            $optionValue = array();        
            if (isset($value['sku'])) $optionValue['sku'] = $value['sku'];
            if (isset($value['sort_order'])) $optionValue['sort_order'] = $value['sort_order'];
            if (isset($value['customoptions_qty'])) $optionValue['customoptions_qty'] = $value['customoptions_qty'];
            if (isset($value['default'])) $optionValue['default'] = $value['default'];
            if (isset($value['in_group_id'])) $optionValue['in_group_id'] = $value['in_group_id'];
            if (isset($value['dependent_ids'])) $optionValue['dependent_ids'] = $value['dependent_ids'];
            if (isset($value['weight'])) $optionValue['weight'] = $value['weight'];
            if (isset($value['cost'])) $optionValue['cost'] = $value['cost'];

            if (count($optionValue)>0) $optionValue['option_id'] = $optionId;

            if ($optionTypeId) {
                $updateFlag = true;
                unset($optionValue['option_id']);
                if (count($optionValue)>0) $connection->update($tablePrefix . 'catalog_product_option_type_value', $optionValue, 'option_type_id = '.$optionTypeId);
            } else {
                $updateFlag = false;
                $connection->insert($tablePrefix . 'catalog_product_option_type_value', $optionValue);
                $optionTypeId = $connection->lastInsertId($tablePrefix . 'catalog_product_option_type_value');
            }
        }
        
        $optionTypePrice = array();
        if (isset($value['price'])) $optionTypePrice['price'] = $value['price'];
        if (isset($value['price_type'])) $optionTypePrice['price_type'] = $value['price_type'];
        
        // for support old format:
        if (isset($value['special_price']) && $value['special_price']!=0) {
            $value['specials'] = $newValue['specials'] = array(array(
                'customer_group_id' => 32000,
                'price' => $value['special_price'],
                'price_type' => 'fixed',
                'comment' => (isset($value['special_comment'])?$value['special_comment']:''),
                'date_from' => '',
                'date_to' => ''
            ));
        }
        
        $optionTypePriceId = 0;
        
        if (count($optionTypePrice)>0) {
            $optionTypePrice['option_type_id'] = $optionTypeId;
            $optionTypePrice['store_id'] = $storeId;
            if ($storeId>0) {
                $select = $connection->select()->from($tablePrefix . 'catalog_product_option_type_price', array('option_type_price_id', 'price', 'price_type'))->where('option_type_id = '.$optionTypeId.' AND `store_id` = '.$storeId);
                $updateFlag = $connection->fetchRow($select);
                if (isset($updateFlag['option_type_price_id'])) $optionTypePriceId = $updateFlag['option_type_price_id'];
            }
            
            if (!is_array($updateFlag)
                || (isset($value['price']) && $value['price']!=$updateFlag['price']) 
                || (isset($value['price_type']) && $value['price_type']!=$updateFlag['price_type'])
                ) {                
                if ($updateFlag) {
                    $connection->update($tablePrefix . 'catalog_product_option_type_price', $optionTypePrice, 'option_type_id = '.$optionTypeId.' AND `store_id` = '.$storeId);
                } else {
                    $connection->insert($tablePrefix . 'catalog_product_option_type_price', $optionTypePrice);
                    $optionTypePriceId = $connection->lastInsertId($tablePrefix . 'catalog_product_option_type_price');
                }
            }
        } elseif ($storeId>0) {
            $connection->delete($tablePrefix . 'catalog_product_option_type_price', 'option_type_id = '.$optionTypeId.' AND store_id = '.$storeId);
            $optionTypePriceId = -1;
        }
        
        
        
        if ($optionTypePriceId>=0) {
            if ($optionTypePriceId==0) {
                $select = $connection->select()->from($tablePrefix . 'catalog_product_option_type_price', array('option_type_price_id'))->where('option_type_id = '.$optionTypeId.' AND `store_id` = '.$storeId);
                $optionTypePriceId = $connection->fetchOne($select);
            }
            if ($optionTypePriceId>0) {
                
                
                // save option value special_prices
                if ((isset($prevValue['specials']) && count($prevValue['specials'])>0) || (isset($value['specials']) && count($value['specials'])>0)) {
                    // remove missing value specials
                    if (isset($prevValue['specials'])) {
                        foreach ($prevValue['specials'] as $key=>$specialValue) {
                            if (!isset($newValue['specials'][$key])) {
                                $connection->delete($tablePrefix . 'custom_options_option_type_special_price', 'option_type_price_id = '. $optionTypePriceId .' AND customer_group_id = '. $specialValue['customer_group_id']);
                            }
                        }
                    }
                    // save option value special
                    if (isset($value['specials']) && count($value['specials'])>0) {
                        foreach ($value['specials'] as $key=>$specialValue) {
                            if (isset($prevValue['specials'][$key]['customer_group_id'])) $prevCustomerGroupId = $prevValue['specials'][$key]['customer_group_id']; else $prevCustomerGroupId = null;
                            $this->saveOptionValueSpecial($optionTypePriceId, $prevCustomerGroupId, $newValue['specials'][$key]);
                        }
                    }
                }
                
                
                // save option value tier_prices
                if ((isset($prevValue['tiers']) && count($prevValue['tiers'])>0) || (isset($value['tiers']) && count($value['tiers'])>0)) {
                    // remove missing value tiers
                    if (isset($prevValue['tiers'])) {
                        foreach ($prevValue['tiers'] as $key=>$tierValue) {
                            if (!isset($newValue['tiers'][$key])) {
                                $connection->delete($tablePrefix . 'custom_options_option_type_tier_price',
                                        'option_type_price_id = '. $optionTypePriceId . 
                                        (isset($tierValue['customer_group_id']) ? ' AND customer_group_id = '. $tierValue['customer_group_id'] : '').
                                        ' AND qty = '. $tierValue['qty']);
                            }
                        }
                    }
                    // save option value tier
                    if (isset($value['tiers']) && count($value['tiers'])>0) {
                        foreach ($value['tiers'] as $key=>$tierValue) {
                            if (isset($prevValue['tiers'][$key]['customer_group_id'])) $prevCustomerGroupId = $prevValue['tiers'][$key]['customer_group_id']; else $prevCustomerGroupId = null;
                            if (isset($prevValue['tiers'][$key]['qty'])) $prevQty = $prevValue['tiers'][$key]['qty']; else $prevQty = 0;
                            $this->saveOptionValueTier($optionTypePriceId, $prevQty, $prevCustomerGroupId, $newValue['tiers'][$key]);
                        }
                    }
                }
                
            }
        }
        
        
        
        if (isset($value['title'])) {
            $optionTypeTitle = array(
                'option_type_id' => $optionTypeId,
                'store_id' => $storeId,
                'title' => $value['title']
            );
            
            if ($storeId>0) {
                $select = $connection->select()->from($tablePrefix . 'catalog_product_option_type_title', array('title'))->where('option_type_id = '.$optionTypeId.' AND `store_id` = '.$storeId);
                $updateFlag = $connection->fetchOne($select);
            }
            if ($value['title']!==$updateFlag) {
                if ($updateFlag) {
                    $connection->update($tablePrefix . 'catalog_product_option_type_title', $optionTypeTitle, 'option_type_id = '.$optionTypeId.' AND `store_id` = '.$storeId);
                } else {
                    $connection->insert($tablePrefix . 'catalog_product_option_type_title', $optionTypeTitle);
                }
            }    
        } elseif ($storeId>0) {
            $connection->delete($tablePrefix . 'catalog_product_option_type_title', 'option_type_id = '.$optionTypeId.' AND store_id = '.$storeId);
        }
        
        // option value images
        if ((isset($prevValue['images']) && count($prevValue['images'])>0) || (isset($value['images']) && count($value['images'])>0)) {
            if ($optionTypeId>0) {
                // remove missing value image
                if (isset($prevValue['images'])) {
                    if (!isset($newValue['images'])) $newValue['images'] = array();
                    foreach ($prevValue['images'] as $imageFile) {
                        if (!in_array($imageFile, $newValue['images'])) {
                            $connection->delete($tablePrefix . 'custom_options_option_type_image', $connection->quoteInto('option_type_id = '.$optionTypeId.' AND  image_file = ?', $imageFile));
                        }
                    }
                }
                // save option value image
                if (isset($value['images']) && count($value['images'])>0) {
                    foreach ($value['images'] as $sort=>$imageFile) {
                        if (isset($prevValue['images']) && in_array($imageFile, $prevValue['images'])) $isUpdate = true; else $isUpdate = 0;                    
                        $this->saveOptionValueImage($optionTypeId, $imageFile, $sort, $isUpdate);
                    }
                }
            }
        }
        
        return $optionTypeId;
    }
    
    public function saveOptionValueImage($optionTypeId, $imageFile, $sort, $isUpdate) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        
        $optionTypeImageId = 0;
        if ($isUpdate) {
            $select = $connection->select()->from($tablePrefix . 'custom_options_option_type_image', array('option_type_image_id'))->where($connection->quoteInto('option_type_id = '. $optionTypeId .' AND image_file = ?', $imageFile));
            $optionTypeImageId = $connection->fetchOne($select);
        }
        
        $source = 1; // file
        if (substr($imageFile, 0, 1)=='#') $source = 2; // color
        
        $optionTypeImage = array(
            'option_type_id' => $optionTypeId,
            'image_file' => $imageFile,
            'sort_order' => $sort,
            'source' => $source
        );        
        if ($optionTypeImageId>0) {
            $connection->update($tablePrefix . 'custom_options_option_type_image', $optionTypeImage, 'option_type_image_id = ' . $optionTypeImageId);
        } else {
            $connection->insert($tablePrefix . 'custom_options_option_type_image', $optionTypeImage);
        }
    }
    
    public function saveOptionValueSpecial($optionTypePriceId, $prevCustomerGroupId, $specialValue) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        
        $optionTypeSpecialPriceId = 0;
        if (!is_null($prevCustomerGroupId)) {
            $select = $connection->select()->from($tablePrefix . 'custom_options_option_type_special_price', array('option_type_special_price_id'))
                    ->where('option_type_price_id = ' .$optionTypePriceId . ' AND customer_group_id = '. $prevCustomerGroupId);
            $optionTypeSpecialPriceId = $connection->fetchOne($select);
        }        
        $optionTypeSpecialPrice = array(
            'option_type_price_id' => $optionTypePriceId,
            'customer_group_id' => $specialValue['customer_group_id'],
            'price' => $specialValue['price'],
            'price_type' => $specialValue['price_type'],
            'comment' => $specialValue['comment'],
            'date_from'=> ($specialValue['date_from'] ? $specialValue['date_from'] : null),
            'date_to' => ($specialValue['date_to'] ? $specialValue['date_to'] : null)
        );
        if ($optionTypeSpecialPriceId>0) {
            $connection->update($tablePrefix . 'custom_options_option_type_special_price', $optionTypeSpecialPrice, 'option_type_special_price_id = '.$optionTypeSpecialPriceId);
        } else {
            try {
                $connection->insert($tablePrefix . 'custom_options_option_type_special_price', $optionTypeSpecialPrice);
            } catch (Exception $e) {}
        }
    }
    
    public function saveOptionValueTier($optionTypePriceId, $prevQty, $prevCustomerGroupId, $tierValue) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $optionTypeTierPriceId = 0;
        if ($prevQty>0) {
            $select = $connection->select()->from($tablePrefix . 'custom_options_option_type_tier_price', array('option_type_tier_price_id'))
                    ->where('option_type_price_id = ' .$optionTypePriceId . (!is_null($prevCustomerGroupId) ? ' AND customer_group_id = '. $prevCustomerGroupId : '') .' AND qty = '. $prevQty);
            $optionTypeTierPriceId = $connection->fetchOne($select);
        }
        
        $optionTypeTierPrice = array(
            'option_type_price_id' => $optionTypePriceId,
            'customer_group_id' => (isset($tierValue['customer_group_id']) ? $tierValue['customer_group_id']: 32000),
            'qty' => $tierValue['qty'],
            'price' => $tierValue['price'],
            'price_type' => $tierValue['price_type']
        );        
        if ($optionTypeTierPriceId>0) {
            $connection->update($tablePrefix . 'custom_options_option_type_tier_price', $optionTypeTierPrice, 'option_type_tier_price_id = '.$optionTypeTierPriceId);
        } else {
            $connection->insert($tablePrefix . 'custom_options_option_type_tier_price', $optionTypeTierPrice);
        }
    }
    
    
    public function updateProductFlags($productId, $absolutePrice = 0, $absoluteWeight = 0, $skuPolicy = 0) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        
        // check Has & RequiredOptions
        $select = $connection->select()
                ->from(array('option_tbl' => $tablePrefix . 'catalog_product_option'), array('require'=>'MAX(is_require)', 'count'=>'COUNT(*)'))
                ->join(array('view_mode_tbl' => $tablePrefix . 'custom_options_option_view_mode'), 'view_mode_tbl.option_id = option_tbl.option_id AND view_mode_tbl.store_id=0', '')
                ->where('product_id = ? AND view_mode_tbl.`view_mode` > 0', $productId);
        $row = $connection->fetchRow($select);

        $hasOptions = 0;
        if (!$row) {
            $isRequire = 0;
        } else {
            $isRequire = $row['require'];
            if ($row['count'] > 0) $hasOptions = 1;
        }
 
        // if no options - absolute = 0
        if ($hasOptions==0) {
            $absolutePrice = 0;
            $absoluteWeight = 0;
            $skuPolicy = 0;
        }
        
        $connection->update($tablePrefix .'catalog_product_entity', array('has_options'=>$hasOptions, 'required_options'=>$isRequire, 'absolute_price'=>$absolutePrice, 'absolute_weight'=>$absoluteWeight, 'sku_policy'=>$skuPolicy), 'entity_id = ' . $productId);
        
        // check attr options_container
        $select = $connection->select()->from($tablePrefix .'eav_attribute', array('attribute_id', 'entity_type_id'))->where("`attribute_code` = 'options_container'");
        $attribute = $connection->fetchRow($select);
        if ($attribute) {
            $select = $connection->select()->from($tablePrefix .'catalog_product_entity_varchar', array('value'))
                    ->where("`attribute_id` = ?", $attribute['attribute_id'])
                    ->where("`entity_id` = ?", $productId)
                    ->order('LENGTH(value)');
            $container = $connection->fetchOne($select);
            if (strlen($container)<9) {
                $data = array('entity_type_id' => $attribute['entity_type_id'],
                      'attribute_id' => $attribute['attribute_id'],
                      'entity_id' => $productId,
                      'value' => 'container1');
                if ($container===false) {
                    $connection->insert($tablePrefix .'catalog_product_entity_varchar', $data);
                } else {
                    $connection->update($tablePrefix .'catalog_product_entity_varchar', $data, '`attribute_id` = '. $attribute['attribute_id'] .' AND `entity_id` = '. $productId);
                }
            }
        }
    }
    
    // denomination in_group_id
    public function unlinkProductOptions($templateId, $productId) {
        $relation = Mage::getResourceSingleton('customoptions/relation');
        $relationOptionIds = $relation->getOptionIds($templateId, $productId);
        if (isset($relationOptionIds) && is_array($relationOptionIds) && count($relationOptionIds)>0) {
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
            
            $relationOptionIds = implode(',', $relationOptionIds);
            
            // define $maxInGroupId for GROUP_SELECT
            $select = $connection->select()->from($tablePrefix . 'catalog_product_option', array('MIN(in_group_id)'))
                ->where("`product_id` = ? AND 
                    `type` <> 'field' AND `type` <> 'area' AND `type` <> 'file' AND `type` <> 'date' AND `type` <> 'date_time' AND `type` <> 'time' AND
                    `in_group_id` <= 65535", $productId)
                ->group('product_id');
            $maxInGroupId = $connection->fetchOne($select);
            
            
            // define $minInGroupId for not GROUP_SELECT
            $select = $connection->select()->from($tablePrefix . 'catalog_product_option', array('MAX(in_group_id)'))
                ->where('`product_id` = ?', $productId)
                ->where("`type` = 'field' OR `type` = 'area' OR `type` = 'file' OR `type` = 'date' OR `type` = 'date_time' OR `type` = 'time'")
                ->where('`in_group_id` <= 65535')
                ->group('product_id');
            $minInGroupId1 = $connection->fetchOne($select);
            
            // define $minInGroupId for GROUP_SELECT
            $select = $connection->select()->from(array('option_tbl' => $tablePrefix . 'catalog_product_option'), array('MAX(option_value_tbl.in_group_id)'))
                ->join(array('option_value_tbl' => $tablePrefix . 'catalog_product_option_type_value'), 'option_value_tbl.option_id = option_tbl.option_id')
                ->where("`product_id` = ? AND option_value_tbl.`in_group_id` <= 65535", $productId)
                ->group('product_id');
            $minInGroupId2 = $connection->fetchOne($select);
            
            // total $minInGroupId
            $minInGroupId = max($minInGroupId1, $minInGroupId2);
            
            // coefficient difference
            if (!$minInGroupId) $minInGroupId = 0;
            $diffMinInGroupId = ($templateId * 65535) - $minInGroupId;
            if (!$maxInGroupId) $maxInGroupId = 65535;
            $diffMaxInGroupId = ($templateId * 65535) + (65535 - $maxInGroupId) + 1;
            
            // denominate dependent_ids
            $select = $connection->select()->from(array('option_tbl' => $tablePrefix . 'catalog_product_option_type_value'), array('option_type_id', 'dependent_ids'))
                ->where("`option_id` IN (". $relationOptionIds .") AND `in_group_id` > 65535 AND `dependent_ids` <> ''");
            $dependentIdsArr = $connection->fetchAll($select);
            foreach ($dependentIdsArr as $row) {
                $dependentIds = explode(',', $row['dependent_ids']);
                foreach ($dependentIds as &$dependentId) {
                    if ($dependentId > $diffMinInGroupId) {
                        $dependentId -= $diffMinInGroupId;
                    }
                }
                $dependentIds = implode(',', $dependentIds);
                $connection->update($tablePrefix . 'catalog_product_option_type_value', array('dependent_ids' => $dependentIds), "`option_type_id` = ". $row['option_type_id']);
            }
             
            // denominate in_group_id
            $connection->update($tablePrefix . 'catalog_product_option',
                    array('in_group_id' => new Zend_Db_Expr('`in_group_id` - ' . $diffMinInGroupId)), 
                    "`option_id` IN (". $relationOptionIds .") AND
                    (`type` = 'field' OR `type` = 'area' OR `type` = 'file' OR `type` = 'date' OR `type` = 'date_time' OR `type` = 'time') AND
                    `in_group_id` > 65535");
            
            $connection->update($tablePrefix . 'catalog_product_option_type_value',
                    array('in_group_id' => new Zend_Db_Expr('`in_group_id` - ' . $diffMinInGroupId)), 
                    "`option_id` IN (". $relationOptionIds .") AND `in_group_id` > 65535");
            
            $connection->update($tablePrefix . 'catalog_product_option',
                    array('in_group_id' => new Zend_Db_Expr('`in_group_id` - ' . $diffMaxInGroupId)), 
                    "`option_id` IN (". $relationOptionIds .") AND
                    (`type` <> 'field' AND `type` <> 'area' AND `type` <> 'file' AND `type` <> 'date' AND `type` <> 'date_time' AND `type` <> 'time') AND
                    `in_group_id` > 65535");
            return true;
        } else {
            return false;
        }
    }

    public function removeProductOptions($templateId, $productId = null) {
        $relation = Mage::getResourceSingleton('customoptions/relation');
        if (is_null($productId)) {
            $productIds = $relation->getProductIds($templateId);
            if (isset($productIds) && is_array($productIds) && count($productIds)>0) {
                foreach ($productIds as $productId) {                    
                    $relationOptionIds = $relation->getOptionIds($templateId, $productId);
                    $this->_removeRelationOptions($relationOptionIds);                                        
                    $this->updateProductFlags($productId);
                }
                return true;
            } else {
                return false;
            }            
        } else {
            $relationOptionIds = $relation->getOptionIds($templateId, $productId);
            if (isset($relationOptionIds) && is_array($relationOptionIds) && count($relationOptionIds)>0) {
                $this->_removeRelationOptions($relationOptionIds);
                return true;
            } else {
                return false;
            }
        }
    }
    
    private function _removeOptionViewMode($id) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $connection->delete($tablePrefix . 'custom_options_option_view_mode', 'option_id = ' . $id);
    }
    
    private function _removeOptionDescription($id) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $connection->delete($tablePrefix . 'custom_options_option_description', 'option_id = ' . $id);
    }
    
    private function _removeOptionDefaultText($id) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $connection->delete($tablePrefix . 'custom_options_option_default', 'option_id = ' . $id);
    }

    private function _removeRelationOptions($relationOptionIds) {
        if (isset($relationOptionIds) && is_array($relationOptionIds)) {
            foreach ($relationOptionIds as $id) {
                $this->_removeOptionViewMode($id);
                $this->_removeOptionDescription($id);
                $this->_removeOptionDefaultText($id);
                $this->getValueInstance()->deleteValue($id);
                $this->deletePrices($id);
                $this->deleteTitles($id);
                $this->setId($id)->delete();
            }
        }
    }

    // only form editing product page
    private function _uploadImage($keyFile, $optionId, $valueId = false, $value = array()) {
        $result = false;
        
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $helper = Mage::helper('customoptions');
        
        
        $imageSort = isset($value['image_sort'])?$value['image_sort']:array();
        $imageDelete = isset($value['image_delete'])?$value['image_delete']:array();
        $imageChange = isset($value['image_change'])?$value['image_change']:0;
        
        // check and save image_sort_change
        
        if ($imageChange) {
            // image_delete
            foreach ($imageDelete as $optionTypeImageId) {
                $select = $connection->select()->from($tablePrefix . 'custom_options_option_type_image', array('image_file'))->where('option_type_image_id = ' . intval($optionTypeImageId));
                $imageFile = $connection->fetchOne($select);
                if ($imageFile) {
                    $fileNameArr = explode(DS, $imageFile);
                    $fileName = end($fileNameArr);
                    if ($fileName) $helper->deleteOptionFile(null, $optionId, $valueId, $fileName);
                }
                $connection->delete($tablePrefix . 'custom_options_option_type_image', 'option_type_image_id = ' . intval($optionTypeImageId));
            }
            
            // save new sort order
            foreach ($imageSort as $sort=>$optionTypeImageId) {
                $data = array('sort_order'=>$sort);
                if (isset($value['image_color'][$optionTypeImageId])) $data['image_file'] = $value['image_color'][$optionTypeImageId];
                $connection->update($tablePrefix . 'custom_options_option_type_image', $data, 'option_type_image_id = '.intval($optionTypeImageId));
            }
        }        
        
        $uploadType = isset($value['upload_type'])?$value['upload_type']:array();
        $files = array();
        $colors = array();
        $galleries = array();
        foreach($uploadType as $index=>$type) {
            if ($type=='file') {
                $files[] = $index;
            } elseif ($type=='color') {
                $colors[] = $index;
            } elseif ($type=='gallery') {
                $galleries[] = $index;
            }
        }
        
        
        // upload image(s)
        if (isset($_FILES[$keyFile]['name'])) {
            $keyFileArr = array($keyFile);
        } else {
            $keyFileArr = array();
            foreach ($files as $index) {
                if (isset($_FILES[$keyFile . '_' . $index]['name'])) {
                    $keyFileArr[$index] = $keyFile . '_' . $index;
                }
            }
        }
        foreach ($keyFileArr as $index=>$keyFile) {
            if (isset($_FILES[$keyFile]['name']) && $_FILES[$keyFile]['name'] != '') {
                try {
                    $isUpdate = $helper->deleteOptionFile(null, $optionId, $valueId, ($valueId?$_FILES[$keyFile]['name']:''));

                    $uploader = new Varien_File_Uploader($keyFile);
                    $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png'));
                    $uploader->setAllowRenameFiles(false);
                    $uploader->setFilesDispersion(false);

                    $saveResult = $uploader->save(Mage::helper('customoptions')->getCustomOptionsPath(false, $optionId, $valueId, $_FILES[$keyFile]['name']));
                    
                    if ($saveResult && isset($saveResult['file'])) {
                        if ($valueId && !$isUpdate) {
                            $data = array(
                                'option_type_id' => $valueId,
                                'image_file' => 'options' . DS . $optionId . DS . $valueId . DS . $saveResult['file'],
                                'sort_order'=> $index + count($imageSort),
                                'source' => 1
                            );
                            $connection->insert($tablePrefix . 'custom_options_option_type_image', $data);
                        }
                        $result = true;
                    }
                } catch (Exception $e) {
                    if ($e->getMessage()) {
                        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    }
                }
            }
        }
        
        // upload colors
        $colorArr = array();
        foreach($colors as $index) {
            if (isset($value['upload_color'][$index]) && strlen($value['upload_color'][$index])>4) $colorArr[$index] = $value['upload_color'][$index];
        }
        foreach ($colorArr as $index=>$color) {
            $data = array(
                'option_type_id' => $valueId,
                'image_file' => $color,
                'sort_order'=> $index + count($imageSort),
                'source' => 2
            );
            $connection->insert($tablePrefix . 'custom_options_option_type_image', $data);
        }
        
        return $result;
    }

    // magento save from product page
    public function saveOptions() {
        
        $helper = Mage::helper('customoptions');
        if (!$helper->isEnabled() || (Mage::app()->getRequest()->getControllerName()!='catalog_product' && Mage::app()->getRequest()->getControllerName()!='adminhtml_catalog_product')) {
            return parent::saveOptions();
    	}
        
        $options = $this->getOptions();        
        $post = Mage::app()->getRequest()->getPost();
        $productId = $this->getProduct()->getId();                
        $storeId = $this->getProduct()->getStoreId();
        
        // bug magento fix
        if ($storeId> 0 && isset($post['copy_to_stores'][$storeId]) && $post['copy_to_stores'][$storeId]==0) {
            return $this;
        }
        
        $relation = Mage::getSingleton('customoptions/relation');

        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        
        if (isset($post['image_delete'])) {
            $productOption = Mage::getModel('catalog/product_option');
            foreach ($post['image_delete'] as $optionId) {
                $connection->update($tablePrefix . 'catalog_product_option', array('image_path' => ''), 'option_id = ' . intval($optionId));
                $helper->deleteOptionFile(null, $optionId, false);
            }
        }

        foreach ($options as $option) {
            if (isset($option['option_id'])) {
                $connection->update($tablePrefix . 'catalog_product_option_type_value', array('default' => 0), 'option_id = ' . $option['option_id']);
                if (isset($option['default'])) {
                    foreach ($option['default'] as $value) {
                        $connection->update($tablePrefix . 'catalog_product_option_type_value', array('default' => 1), 'option_type_id = ' . $value);
                    }
                }
            }
        }
                        
        
        if ($helper->isCustomerGroupsEnabled()) {
            $options = $this->getOptions();
            foreach ($options as $key => $option) {
                if (isset($option['customer_groups'])) {
                    $options[$key]['customer_groups'] = implode(',', $option['customer_groups']);
                }
            }
            $this->setOptions($options);
        }
        
        // qnty_input, exclude_first_image       
        $options = $this->getOptions();
        foreach ($options as $key => $option) {
            if (!isset($option['qnty_input']) || $this->getGroupByType($option['type'])!=self::OPTION_GROUP_SELECT || $option['type']=='multiple' || $option['type']=='hidden') $options[$key]['qnty_input'] = 0;
            if (!isset($option['exclude_first_image']) || $this->getGroupByType($option['type'])!=self::OPTION_GROUP_SELECT) $options[$key]['exclude_first_image'] = 0;
            if ($helper->isSkuNameLinkingEnabled() && (!isset($option['scope']['title']) && $option['scope']['title']!=1) && (!isset($option['title']) || $option['title']=='') && $this->getGroupByType($option['type'])!=self::OPTION_GROUP_SELECT && $option['sku']) {
                $options[$key]['title'] = $helper->getProductNameBySku($option['sku'], $storeId);
            }
        }
        $this->setOptions($options);        
        
        
        // original code m1510 parent::saveOptions();
        foreach ($this->getOptions() as $option) {
            $this->setData($option)
                ->setData('product_id', $productId)
                ->setData('store_id', $storeId);

            if ($this->getData('option_id') == '0') {
                $this->unsetData('option_id');
            } else {
                $this->setId($this->getData('option_id'));
            }
            $isEdit = (bool)$this->getId()? true:false;

            if ($this->getData('is_delete')=='1') {
                if ($isEdit) {
                    $this->getValueInstance()->deleteValue($this->getId());
                    $this->deletePrices($this->getId());
                    $this->deleteTitles($this->getId());
                    $this->delete();
                    $helper->deleteOptionFile(null, $this->getId(), false);
                }
            } else {
                if ($this->getData('previous_type') != '') {
                    
                    
                    $previousType = $this->getData('previous_type');
                    //if previous option has dfferent group from one is came now need to remove all data of previous group
                    if ($this->getGroupByType($previousType) != $this->getGroupByType($this->getData('type'))) {
                        switch ($this->getGroupByType($previousType)) {
                            case self::OPTION_GROUP_SELECT:
                                $this->unsetData('values');
                                if ($isEdit) {
                                    $this->getValueInstance()->deleteValue($this->getId());
                                }
                                break;
                            case self::OPTION_GROUP_FILE:
                                $this->setData('file_extension', '');
                                $this->setData('image_size_x', '0');
                                $this->setData('image_size_y', '0');
                                break;
                            case self::OPTION_GROUP_TEXT:
                                $this->setData('max_characters', '0');
                                break;
                            case self::OPTION_GROUP_DATE:
                                break;
                        }
                        if ($this->getGroupByType($this->getData('type')) == self::OPTION_GROUP_SELECT) {
                            $this->setData('sku', '');
                            $this->unsetData('price');
                            $this->unsetData('price_type');
                            if ($isEdit) {
                                $this->deletePrices($this->getId());
                            }
                        }
                    }
                }
                
                // error protection
                if (!$this->getGroupByType($this->getType())) {
                    if (is_null($this->getPrice())) $this->setPrice(0);
                    if (is_null($this->getPriceType())) $this->setPriceType('fixed');
                }
                
                $this->save();
                
                if (!isset($option['option_id']) || !$option['option_id']) {                                        
                    $values = $this->getValues();                    
                    $option['option_id']=$this->getId();                    
                }    
                
                switch ($option['type']) {
                    case 'field':
                    case 'area':
                        if ($this->_uploadImage('file_' . $option['id'], $option['option_id'])) {                            
                            $this->setImagePath('options' . DS . $option['option_id'] . DS)->save();
                        }
                        break;
                    case 'drop_down':
                    case 'radio':
                    case 'checkbox':
                    case 'multiple':
                    case 'swatch':
                    case 'multiswatch':
                    case 'hidden':
                        break;
                    case 'file':
                    case 'date':
                    case 'date_time':
                    case 'time':
                        // no image
                        if (isset($option['option_id'])) {
                            $helper->deleteOptionFile(null, $option['option_id'], false);
                            $this->setImagePath('')->save();                            
                        }                         
                        break;
                }
                
            }
        }//eof foreach()
        // end original code m1510 parent::saveOptions();                        
        
        if ($productId && isset($post['affect_product_custom_options'])) {
            
            if (isset($post['customoptions']['groups'])) $postGourps = $post['customoptions']['groups']; else $postGourps = array();
            
            
            $groupModel = Mage::getSingleton('customoptions/group');
            $groups = $relation->getResource()->getGroupIds($productId, false);            
            
            if (isset($groups) && is_array($groups) && count($groups)>0) {
                $keepOptionsFlag = (isset($post['general']['keep_options'])?$post['general']['keep_options']:0);
                
                foreach ($groups as $id) {                    
                    if (count($postGourps)==0 || !in_array($id, $postGourps)) {
                        if ($keepOptionsFlag) {
                            $this->unlinkProductOptions($id, $productId);
                        } else {
                            $this->removeProductOptions($id, $productId);
                        }
                        $relation->getResource()->deleteGroupProduct($id, $productId);
                    } else {
                        $relationOptionIds = $relation->getResource()->getOptionIds($id, $productId);                        
                        if ($relationOptionIds && is_array($relationOptionIds) && count($relationOptionIds)>0) {
                            foreach ($relationOptionIds as $opId) {
                                $check = Mage::getModel('catalog/product_option')->load($opId)->getData();
                                if (empty($check)) $relation->getResource()->deleteOptionProduct($id, $productId, $opId);
                            }
                        }
                        if (count($postGourps)>0) {
                            $key = array_search($id, $postGourps);                        
                            unset($postGourps[$key]);
                        }    
                    }
                }
            }
            
            if (count($postGourps)>0) {
                foreach ($postGourps as $templateId) {
                    if (!empty($templateId)) {
                        $group = $groupModel->load($templateId);
                        $optionsHash = unserialize($group->getData('hash_options'));                        
                        $this->saveProductOptions($optionsHash, array(), array($productId), $group, 1, 'product');
                    }
                }
            } else {
                // save absolutePrice, absoluteWeight and skuPolicy
                $absolutePrice = (isset($post['general']['absolute_price'])?$post['general']['absolute_price']:0);
                $absoluteWeight = (isset($post['general']['absolute_weight'])?$post['general']['absolute_weight']:0);
                $skuPolicy = (isset($post['general']['sku_policy'])?$post['general']['sku_policy']:0);
                $this->updateProductFlags($productId, $absolutePrice, $absoluteWeight, $skuPolicy);
            }                        
        }
        return $this;
    }       
    
    protected function _afterSave() {
        if (!Mage::helper('customoptions')->isEnabled() || (Mage::app()->getRequest()->getControllerName()!='catalog_product' && Mage::app()->getRequest()->getControllerName()!='adminhtml_catalog_product')) {
            return parent::_afterSave();
    	}
        
        $optionId = $this->getData('option_id');
        $defaultArray = $this->getData('default') ? $this->getData('default') : array();
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        
        $helper = Mage::helper('customoptions');
        
        $storeId = $this->getProduct()->getStoreId();
        if (is_array($this->getData('values'))) {
            $values=array();
            foreach ($this->getData('values') as $key => $value) {
                if (isset($value['option_type_id'])) {
                                        
                    if (isset($value['dependent_ids']) && $value['dependent_ids']!='') {                                
                        $dependentIds = array();
                        $dependentIdsTmp = explode(',', $value['dependent_ids']);
                        foreach ($dependentIdsTmp as $d_id) {
                            if ($this->decodeViewIGI($d_id)>0) $dependentIds[] = $this->decodeViewIGI($d_id);
                        }
                        $value['dependent_ids'] = implode(',', $dependentIds);
                    }
                    
                    $value['sku'] = trim($value['sku']);
                    
                    // prepare customoptions_qty
                    $customoptionsQty = '';
                    if (isset($value['customoptions_qty']) && (!$helper->isSkuQtyLinkingEnabled() || $helper->getProductIdBySku($value['sku'])==0)) {
                        $customoptionsQty = strtolower(trim($value['customoptions_qty']));
                        if (substr($customoptionsQty, 0, 1)!='x' && substr($customoptionsQty, 0, 1)!='i' && substr($customoptionsQty, 0, 1)!='l' && !is_numeric($customoptionsQty)) $customoptionsQty='';
                        if (is_numeric($customoptionsQty)) $customoptionsQty = intval($customoptionsQty);
                        if (substr($customoptionsQty, 0, 1)=='i') $customoptionsQty = $this->decodeViewIGI($customoptionsQty);
                    }
                    
                    $optionValue = array(
                        'option_id' => $optionId,
                        'sku' => $value['sku'],
                        'sort_order' => $value['sort_order'],
                        'customoptions_qty' => $customoptionsQty,
                        'default' => array_search($key, $defaultArray) !== false ? 1 : 0,
                        'in_group_id' => $value['in_group_id']
                    );                    
                    if (isset($value['dependent_ids'])) $optionValue['dependent_ids'] = $value['dependent_ids'];
                    if (isset($value['weight'])) $optionValue['weight'] = $value['weight'];
                    if (isset($value['cost'])) $optionValue['cost'] = $value['cost'];
                    
                    $optionTypePriceId = 0;
                    
                    if ($helper->isSkuNameLinkingEnabled() && (!isset($value['scope']['title']) || $value['scope']['title']!=1) && (!isset($value['title']) || $value['title']=='') && $value['sku']) {
                        $value['title'] = $helper->getProductNameBySku($value['sku'], $storeId);
                    }
                    
                    if (isset($value['option_type_id']) && $value['option_type_id']>0) {
                        $optionTypeId = $value['option_type_id'];
                        if ($value['is_delete']=='1') {
                            $connection->delete($tablePrefix . 'catalog_product_option_type_value', 'option_type_id = ' . $optionTypeId);                            
                            $helper->deleteOptionFile(null, $optionId, $optionTypeId);
                        } else {
                            $connection->update($tablePrefix . 'catalog_product_option_type_value', $optionValue, 'option_type_id = ' . $optionTypeId);

                            // update or insert price
                            //if ($storeId>0) {
                                $select = $connection->select()->from($tablePrefix . 'catalog_product_option_type_price', array('option_type_price_id'))->where('option_type_id = '.$optionTypeId.' AND `store_id` = '.$storeId);
                                $optionTypePriceId = $isUpdate = $connection->fetchOne($select);
                            //} else {
                            //    $isUpdate = 1;
                            //}    
                            if (isset($value['price']) && isset($value['price_type'])) {
                                $priceValue = array('price' => $value['price'], 'price_type' => $value['price_type']);
                                if ($isUpdate) {
                                    $connection->update($tablePrefix . 'catalog_product_option_type_price', $priceValue, 'option_type_id = ' . $optionTypeId.' AND `store_id` = '.$storeId);
                                } else {
                                    $priceValue['option_type_id'] = $optionTypeId;
                                    $priceValue['store_id'] = $storeId;
                                    $connection->insert($tablePrefix . 'catalog_product_option_type_price', $priceValue);
                                    $optionTypePriceId = $connection->lastInsertId($tablePrefix . 'catalog_product_option_type_price');
                                }
                            } elseif (isset($value['scope']['price']) && $value['scope']['price']==1 && $isUpdate && $storeId>0) {
                                $connection->delete($tablePrefix . 'catalog_product_option_type_price', 'option_type_id = ' . $optionTypeId.' AND `store_id` = '.$storeId);
                                $optionTypePriceId = -1;
                            }                            
                            
                            // update or insert title
                            if ($storeId>0) {
                                $select = $connection->select()->from($tablePrefix . 'catalog_product_option_type_title', array('COUNT(*)'))->where('option_type_id = '.$optionTypeId.' AND `store_id` = '.$storeId);
                                $isUpdate = $connection->fetchOne($select);
                            } else {
                                $isUpdate = 1;
                            }
                            
                            if (isset($value['title'])) {                                
                                if ($isUpdate) {                                
                                    $connection->update($tablePrefix . 'catalog_product_option_type_title', array('title' => $value['title']), 'option_type_id = ' . $optionTypeId.' AND `store_id` = '.$storeId);
                                } else {
                                    $connection->insert($tablePrefix . 'catalog_product_option_type_title', array('option_type_id' =>$optionTypeId, 'store_id'=>$storeId, 'title' => $value['title']));
                                }
                            } elseif (isset($value['scope']['title']) && $value['scope']['title']==1 && $isUpdate && $storeId>0) {
                                $connection->delete($tablePrefix . 'catalog_product_option_type_title', 'option_type_id = ' . $optionTypeId.' AND `store_id` = '.$storeId);
                            }     
                        }    
                    } else {
                        if ($value['is_delete']=='1') continue;
                        $connection->insert($tablePrefix . 'catalog_product_option_type_value', $optionValue);                
                        $optionTypeId = $connection->lastInsertId($tablePrefix . 'catalog_product_option_type_value');
                        if (isset($value['price']) && isset($value['price_type'])) {                            
                            // save not default
                            //if ($storeId>0) $connection->insert($tablePrefix . 'catalog_product_option_type_price', array('option_type_id' =>$optionTypeId, 'store_id'=>$storeId, 'price' => $value['price'], 'price_type' => $value['price_type']));
                            // save default
                            $connection->insert($tablePrefix . 'catalog_product_option_type_price', array('option_type_id' =>$optionTypeId, 'store_id'=>0, 'price' => $value['price'], 'price_type' => $value['price_type']));
                            $optionTypePriceId = $connection->lastInsertId($tablePrefix . 'catalog_product_option_type_price');
                        }
                        if (isset($value['title'])) {
                            // save not default
                            //if ($storeId>0) $connection->insert($tablePrefix . 'catalog_product_option_type_title', array('option_type_id' =>$optionTypeId, 'store_id'=>$storeId, 'title' => $value['title']));
                            // save default
                            $connection->insert($tablePrefix . 'catalog_product_option_type_title', array('option_type_id' =>$optionTypeId, 'store_id'=>0, 'title' => $value['title']));
                        }    
                    }

                    if ($optionTypeId>0 && $optionTypePriceId>=0) {
                        $id = $this->getData('id');
                        
                        $this->_uploadImage('file_'.$id.'_'.$key, $optionId, $optionTypeId, $value);
                        
                        // check $optionTypePriceId
                        if ($optionTypePriceId==0) {
                            $select = $connection->select()->from($tablePrefix . 'catalog_product_option_type_price', array('option_type_price_id'))->where('option_type_id = '.$optionTypeId.' AND `store_id` = '.$storeId);
                            $optionTypePriceId = $isUpdate = $connection->fetchOne($select);
                        }                        
                        if ($optionTypePriceId) {
                            
                            // save special prices
                            if (isset($value['specials']) && is_array($value['specials'])) {
                                $specials = array();
                                foreach ($value['specials'] as $special) {
                                    if ($special['is_delete']=='1' || isset($specials[$special['customer_group_id']])) {
                                        if ($special['special_price_id']>0) $connection->delete($tablePrefix . 'custom_options_option_type_special_price', 'option_type_special_price_id = ' . intval($special['special_price_id']));
                                        continue;
                                    }
                                    $specials[$special['customer_group_id']] = $special;
                                }
                                if (count($specials)>0) {
                                    foreach ($specials as $special) {
                                        $zendDate = new Zend_Date();
                                        $dateFormat = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
                                        if ($special['date_from']) $special['date_from'] = $zendDate->setDate($special['date_from'], $dateFormat)->toString(Varien_Date::DATE_INTERNAL_FORMAT); else $special['date_from'] = null;
                                        if ($special['date_to']) $special['date_to'] = $zendDate->setDate($special['date_to'], $dateFormat)->toString(Varien_Date::DATE_INTERNAL_FORMAT); else $special['date_to'] = null;
                                        
                                        $specialData = array('option_type_price_id' => $optionTypePriceId,
                                                             'customer_group_id' => $special['customer_group_id'],
                                                             'price' => floatval($special['price']),
                                                             'price_type' => $special['price_type'],
                                                             'comment' => trim($special['comment']),
                                                             'date_from' => $special['date_from'],
                                                             'date_to' => $special['date_to'],
                                                        );
                                        if ($special['special_price_id']>0) {
                                            $connection->update($tablePrefix . 'custom_options_option_type_special_price', $specialData, 'option_type_special_price_id = ' . intval($special['special_price_id']));
                                        } else {
                                            $connection->insert($tablePrefix . 'custom_options_option_type_special_price', $specialData);
                                        }
                                    }
                                }
                            }
                            
                            // save tier prices
                            if (isset($value['tiers']) && is_array($value['tiers'])) {
                                $tiers = array();
                                foreach ($value['tiers'] as $tier) {
                                    $uniqKey = $tier['qty']. '+' .$tier['customer_group_id'];
                                    if ($tier['is_delete']=='1' || isset($tiers[$uniqKey])) {
                                        if ($tier['tier_price_id']>0) $connection->delete($tablePrefix . 'custom_options_option_type_tier_price', 'option_type_tier_price_id = ' . intval($tier['tier_price_id']));
                                        continue;
                                    }
                                    $tiers[$uniqKey] = $tier;
                                }
                                if (count($tiers)>0) {
                                    foreach ($tiers as $tier) {
                                        $tierData = array('option_type_price_id'=>$optionTypePriceId, 'customer_group_id'=>$tier['customer_group_id'], 'qty'=>intval($tier['qty']), 'price'=>floatval($tier['price']), 'price_type'=>$tier['price_type']);
                                        if ($tier['tier_price_id']>0) {
                                            $connection->update($tablePrefix . 'custom_options_option_type_tier_price', $tierData, 'option_type_tier_price_id = ' . intval($tier['tier_price_id']));
                                        } else {
                                            $connection->insert($tablePrefix . 'custom_options_option_type_tier_price', $tierData);
                                        }
                                    }
                                }
                            }
                        }
                        
                    }
                    unset($value['option_type_id']);
                }    
                
                $values[$key] = $value;
                
            }
            $this->setData('values', $values);            
        
            
        } elseif ($this->getGroupByType($this->getType()) == self::OPTION_GROUP_SELECT) {
            Mage::throwException(Mage::helper('catalog')->__('Select type options required values rows.'));
        }
        
        if (version_compare($helper->getMagetoVersion(), '1.4.0', '>=')) $this->cleanModelCache();
        
        Mage::dispatchEvent('model_save_after', array('object'=>$this));
        if (version_compare($helper->getMagetoVersion(), '1.4.0', '>=')) Mage::dispatchEvent($this->_eventPrefix.'_save_after', $this->_getEventData());
        return $this;
    }
    
    
    
    public function getProductOptionCollection(Mage_Catalog_Model_Product $product) {
        $helper = Mage::helper('customoptions');
        
        $collection = $this->getCollection()->addFieldToFilter('product_id', $product->getId())
                ->addTitleToResult($product->getStoreId())
                ->addPriceToResult($product->getStoreId())
                ->addViewModeToResult($product->getStoreId())
                ->addDescriptionToResult($product->getStoreId())                    
                ->addDefaultTextToResult($product->getStoreId())
                ->setOrder('sort_order', 'asc')
                ->setOrder('title', 'asc');
                
        $isProductEditPage = Mage::app()->getStore()->isAdmin() && Mage::app()->getRequest()->getControllerName()=='catalog_product';
        if ($isProductEditPage) $collection->addTemplateTitleToResult();
        
        $collection->addValuesToResult($product->getStoreId());
        
        if (!$isProductEditPage) {
            // filter by view_mode
            $isRequire = false;
            foreach($collection as $key => $item) {
                // 0-Disable, 1-Visible, 2-Hidden, 3-Backend, 4-Admin Only
                if ($item->getViewMode()==0 && !is_null($item->getViewMode())) {
                    $collection->removeItemByKey($key);
                } elseif (!Mage::app()->getStore()->isAdmin() && ($item->getViewMode()==3 || $item->getViewMode()==4)) {
                    $collection->removeItemByKey($key);
                } elseif ($item->getIsRequire(true)) {
                    $isRequire = true;
                }
            }
            
            if (!$isRequire) $product->setRequiredOptions(0);                
            if (count($collection)==0) $product->setHasOptions(0);
            
            $customerGroupId = $helper->getCustomerGroupId();
            
            // filter by CustomerGroups
            if ($helper->isCustomerGroupsEnabled()) {
                $isRequire = false;
                foreach($collection as $key => $item) {
                    $groups = $item->getCustomerGroups();
                    if ($groups!=='' && !in_array($customerGroupId, explode(',', $groups))) {
                        $collection->removeItemByKey($key);
                    } elseif ($item->getIsRequire(true)) {
                        $isRequire = true;
                    }
                }                
                if (!$isRequire) $product->setRequiredOptions(0);                
                if (count($collection)==0) $product->setHasOptions(0);                
            }
            
            // recheck inventory
            if ($product->getRequiredOptions()) {
                if ($helper->isInventoryEnabled() && ($helper->getOutOfStockOptions()==1 || $helper->isSetProductOutOfStock())) {
                    $isDependentEnabled = $helper->isDependentEnabled();
                    
                    // checkDependentInventory for parent -> set parent option "Out of stock"
                    if ($isDependentEnabled) {
                        foreach ($collection as $option) {
                            if ($this->getGroupByType($option->getType())!=self::OPTION_GROUP_SELECT || count($option->getValues())==0) continue;
                            foreach ($option->getValues() as $value) {
                                if (!$value->getDependentIds()) continue;
                                $customoptionsQty = $helper->getCustomoptionsQty($value->getCustomoptionsQty(), $value->getSku(), $product->getId(), $value);
                                if ($customoptionsQty!==0 && !$this->checkDependentInventory($collection, $value, $product)) {
                                    $value->setCustomoptionsQty(0);
                                }
                            }
                        }
                    }
                    
                    // if all required options "Out of stock" -> set product "Out of stock"
                    foreach ($collection as $option) {
                        if (!$option->getIsRequire(true) || ($isDependentEnabled && $option->getIsDependent()) || $this->getGroupByType($option->getType())!=self::OPTION_GROUP_SELECT || count($option->getValues())==0) continue;
                        $outOfStockFlag = true;
                        foreach ($option->getValues() as $value) {
                            $customoptionsQty = $helper->getCustomoptionsQty($value->getCustomoptionsQty(), $value->getSku(), $product->getId(), $value);
                            if ($customoptionsQty!==0) {
                                if ($isDependentEnabled && !$this->checkDependentInventory($collection, $value, $product)) continue;
                                $outOfStockFlag = false;
                                break;
                            }
                        }
                        if ($outOfStockFlag) {
                            $product->setData('is_salable', false);
                            break;
                        }
                    }
                }
            }           
        }
        
        // add images, special_prices, tier_prices
        $specialPriceEnabled = $helper->isSpecialPriceEnabled();
        $tierPriceEnabled = $helper->isTierPriceEnabled();
        $helper->getCustomerGroups(); // init customer_groups for sort prices
        
        foreach($collection as $key => $option) {
            if ($this->getGroupByType($option->getType())==self::OPTION_GROUP_SELECT) {
                $values = $option->getValues();
                if (count($values)==0) continue;
                foreach ($values as $value) {
                    // add images to optionValue
                    $value->setImages($this->getOptionValueImages($value->getOptionTypeId()));
                    
                    // link data (price, special_price, group_prices, tier_prices) by sku -> and skip special_prices, tier_prices from option
                    if (!$helper->applyLinkedBySkuDataToOption($value, $value->getSku(), $product->getStore(), $product->getTaxClassId())) {
                        // add special_prices
                        if ($specialPriceEnabled) $value->setSpecials($this->getOptionValueSpecialPrices($value->getOptionTypePriceId()));
                        // add tier_prices
                        if ($tierPriceEnabled) $value->setTiers($this->getOptionValueTierPrices($value->getOptionTypePriceId()));
                    }
                    $helper->calculateOptionSpecialPrice($value, $product, $specialPriceEnabled);
                    $value->setIsPriceCalculated(true);
                }
            } else {
                if ($helper->applyLinkedBySkuDataToOption($option, $option->getSku(), $product->getStore(), $product->getTaxClassId())) {
                    $helper->calculateOptionSpecialPrice($option, $product, false);
                }
                $option->setIsPriceCalculated(true);
            }
            
        }
        
        return $collection;
    }
    
    public function checkDependentInventory($collection, $checkedValue, $product, $loop=1) {
        if ($loop>10) return true;
        $dependentIds = $checkedValue->getDependentIds();
        if (!$dependentIds) return true;
        $helper = Mage::helper('customoptions');
        $dependentIds = explode(',', $dependentIds);
        $result = true;
        
        foreach ($collection as $option) {
            if (!$option->getIsRequire(true) || $this->getGroupByType($option->getType())!=self::OPTION_GROUP_SELECT || count($option->getValues())==0) continue;
            foreach ($option->getValues() as $value) {
                if (!in_array($value->getInGroupId(), $dependentIds)) continue;
                $customoptionsQty = $helper->getCustomoptionsQty($value->getCustomoptionsQty(), $value->getSku(), $product->getId(), $value);
                if ($customoptionsQty!==0) {
                    if (!$this->checkDependentInventory($collection, $value, $product, $loop+1)) continue;                                
                    return true;
                } else {
                    $result = false;
                }
            }
        }
        if (!$result) $checkedValue->setIsOutOfStock(true);
        return $result;
    }

    public function getValueById($valueId) {
        if (isset($this->_values[$valueId])) {
            $value = $this->_values[$valueId];
            if (!$value->getIsPriceCalculated()) {
                $product = $this->getProduct();
                if ($product) {
                    $helper = Mage::helper('customoptions');
                    if (!$helper->applyLinkedBySkuDataToOption($value, $value->getSku(), $product->getStore(), $product->getTaxClassId())) {
                        // add special_prices
                        if ($helper->isSpecialPriceEnabled()) $value->setSpecials($this->getOptionValueSpecialPrices($value->getOptionTypePriceId()));
                        // add tier_prices
                        if ($helper->isTierPriceEnabled()) $value->setTiers($this->getOptionValueTierPrices($value->getOptionTypePriceId()));
                    }

                    $helper->calculateOptionSpecialPrice($value, $product, $helper->isSpecialPriceEnabled());
                    $value->setIsPriceCalculated(true);
                }
            }
            return $value;
        }
        return null;
    }
    
    public function getOptionValue($valueId) {        
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();

        $select = $connection->select()->from($tablePrefix . 'catalog_product_option_type_value')->where('option_id = ' . intval($this->getId()) . ' AND option_type_id = ' . intval($valueId));
        $row = $connection->fetchRow($select);
        return $row;
    }
    
    public function getOptionValueTierPrices($optionTypePriceId) {             
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $select = $connection->select()->from($tablePrefix . 'custom_options_option_type_tier_price')->where('option_type_price_id = ' . intval($optionTypePriceId));
        $tiers = $connection->fetchAll($select);
        if ($tiers) usort($tiers, array(Mage::helper('customoptions'), '_sortPrices'));
        return $tiers;
    }
    
    public function getOptionValueSpecialPrices($optionTypePriceId) {             
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $select = $connection->select()->from($tablePrefix . 'custom_options_option_type_special_price')->where('option_type_price_id = ' . intval($optionTypePriceId));
        $specials = $connection->fetchAll($select);
        if ($specials) usort($specials, array(Mage::helper('customoptions'), '_sortPrices'));
        return $specials;
    }
    
    public function getOptionValueImages($optionTypeId) {             
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $select = $connection->select()->from($tablePrefix . 'custom_options_option_type_image')->where('option_type_id = ' . intval($optionTypeId))->order('sort_order');
        return $connection->fetchAll($select);
    }
    
    
    public function duplicate($oldProductId, $newProductId) {        
        if ($oldProductId>0 && $newProductId>0) {
            
            // standart magento duplicate options:
            $this->getResource()->duplicate($this, $oldProductId, $newProductId); 
                        
            // set relation template
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $tablePrefix = (string) Mage::getConfig()->getTablePrefix();

            // transfer relation
            $select = $connection->select()->from($tablePrefix . 'catalog_product_option', array('option_id', 'in_group_id'))->where('product_id = '.$newProductId.' AND in_group_id > 65535');
            $newOptions = $connection->fetchAll($select);                
            if ($newOptions) {
                foreach ($newOptions as $option) {
                    $connection->insert($tablePrefix . 'custom_options_relation', array('option_id' => $option['option_id'], 'group_id' => floor((intval($option['in_group_id'])-1)/65535), 'product_id' => $newProductId));
                }
            }
        }
        
        return $this;
    }
    
    public function getGroupByType($type = null) {
        if (is_null($type)) {
            $type = $this->getType();
        }
        $optionGroupsToTypes = array(
            self::OPTION_TYPE_FIELD => self::OPTION_GROUP_TEXT,
            self::OPTION_TYPE_AREA => self::OPTION_GROUP_TEXT,
            self::OPTION_TYPE_FILE => self::OPTION_GROUP_FILE,
            self::OPTION_TYPE_DROP_DOWN => self::OPTION_GROUP_SELECT,
            self::OPTION_TYPE_SWATCH => self::OPTION_GROUP_SELECT,
            self::OPTION_TYPE_MULTISWATCH => self::OPTION_GROUP_SELECT,
            self::OPTION_TYPE_HIDDEN => self::OPTION_GROUP_SELECT,
            self::OPTION_TYPE_RADIO => self::OPTION_GROUP_SELECT,
            self::OPTION_TYPE_CHECKBOX => self::OPTION_GROUP_SELECT,
            self::OPTION_TYPE_MULTIPLE => self::OPTION_GROUP_SELECT,
            self::OPTION_TYPE_DATE => self::OPTION_GROUP_DATE,
            self::OPTION_TYPE_DATE_TIME => self::OPTION_GROUP_DATE,
            self::OPTION_TYPE_TIME => self::OPTION_GROUP_DATE,
        );

        return isset($optionGroupsToTypes[$type])?$optionGroupsToTypes[$type]:'';
    }
    
    // $isProductPage = false - is checkout
    public function getIsRequire($isProductPage = false) {
        if ($isProductPage) return $this->getData('is_require');        
        $helper = Mage::helper('customoptions');
        
        // ckeck CustomerGroups
        if ($helper->isCustomerGroupsEnabled()) {
            $customerGroupId = $helper->getCustomerGroupId();
            
            $groups = $this->getCustomerGroups();
            if ($groups!=='' && !in_array($customerGroupId, explode(',', $groups))) {                        
                return 0;
            }
        }
        
        if (($this->getViewMode()==1 || (Mage::app()->getStore()->isAdmin() && $this->getViewMode()==3)) && !$this->getIsDependent()) {
            return $this->getData('is_require');
        } else {
            return 0;
        }
    }               

}