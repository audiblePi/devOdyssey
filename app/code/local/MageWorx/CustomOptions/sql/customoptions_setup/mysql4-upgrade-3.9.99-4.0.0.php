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

/* @var $installer MageWorx_CustomOptions_Model_Mysql4_Setup */

@set_time_limit(0);
$installer = $this;
$installer->startSetup();

if (!$installer->getConnection()->tableColumnExists($installer->getTable('catalog/product_option'), 'view_mode') && $installer->getConnection()->tableColumnExists($installer->getTable('catalog/product_option'), 'is_enabled')) {
    $installer->run("ALTER TABLE `{$installer->getTable('catalog/product_option')}` CHANGE `is_enabled` `view_mode` TINYINT(1) NOT NULL DEFAULT '1';");
}

$installer->run("
-- DROP TABLE IF EXISTS `{$installer->getTable('customoptions/option_type_tier_price')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('customoptions/option_type_tier_price')}` (
  `option_type_tier_price_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `option_type_price_id` int(10) unsigned NOT NULL DEFAULT '0',
  `qty` int(10) unsigned NOT NULL DEFAULT '0',
  `price` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `price_type` enum('fixed','percent') NOT NULL DEFAULT 'fixed',
  PRIMARY KEY (`option_type_tier_price_id`),
  UNIQUE KEY `option_type_price_id+qty` (`option_type_price_id`,`qty`),
  CONSTRAINT `FK_MAGEWORX_CUSTOM_OPTIONS_INDEX_OPTION_TYPE_TIER_PRICE` FOREIGN KEY (`option_type_price_id`) REFERENCES `{$installer->getTable('catalog/product_option_type_price')}` (`option_type_price_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- DROP TABLE IF EXISTS `{$installer->getTable('customoptions/option_type_image')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('customoptions/option_type_image')}` (
  `option_type_image_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `option_type_id` int(10) unsigned NOT NULL DEFAULT '0',
  `image_file` varchar (255) default '',
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `source` tinyint(3) NOT NULL DEFAULT '1' COMMENT '1-file,2-color,3-gallery',
  PRIMARY KEY (`option_type_image_id`),
  CONSTRAINT `FK_MAGEWORX_CUSTOM_OPTIONS_INDEX_OPTION_TYPE_IMAGE` FOREIGN KEY (`option_type_id`) REFERENCES `{$installer->getTable('catalog/product_option_type_value')}` (`option_type_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- DROP TABLE IF EXISTS `{$installer->getTable('customoptions/option_view_mode')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('customoptions/option_view_mode')}` (
  `view_mode_id` int(10) unsigned NOT NULL auto_increment,
  `option_id` int(10) unsigned NOT NULL default '0',
  `store_id` smallint(5) unsigned NOT NULL default '0',
  `view_mode` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`view_mode_id`),
  UNIQUE KEY `option_id+store_id` (`option_id`,`store_id`),
  KEY `store_id` (`store_id`),
  CONSTRAINT `FK_MAGEWORX_CUSTOM_OPTIONS_VIEW_MODE_OPTION` FOREIGN KEY (`option_id`) REFERENCES `{$installer->getTable('catalog/product_option')}` (`option_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_MAGEWORX_CUSTOM_OPTIONS_VIEW_MODE_STORE` FOREIGN KEY (`store_id`) REFERENCES `{$installer->getTable('core/store')}` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
");

if ($installer->getConnection()->tableColumnExists($installer->getTable('catalog/product_option'), 'view_mode')) {
    $installer->run("INSERT IGNORE INTO `{$installer->getTable('customoptions/option_view_mode')}` (`option_id`, `store_id`, `view_mode`) SELECT  `option_id`, 0 AS `store_id`, `view_mode` FROM  `{$installer->getTable('catalog/product_option')}`;");
    $installer->getConnection()->dropColumn(
        $installer->getTable('catalog/product_option'),
        'view_mode'
    );
}  

if (!$installer->getConnection()->tableColumnExists($installer->getTable('catalog/product_option'), 'sku_policy')) {
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog/product_option'),
        'sku_policy',
        "TINYINT( 1 ) NOT NULL DEFAULT '0'"
    );
}

//if (!$installer->getConnection()->tableColumnExists($installer->getTable('catalog/product_option_type_price'), 'special_price')) {
//    $installer->getConnection()->addColumn(
//        $installer->getTable('catalog/product_option_type_price'),
//        'special_price',
//        "DECIMAL(12, 4) NULL DEFAULT NULL"
//    );    
//    $installer->getConnection()->addColumn(
//        $installer->getTable('catalog/product_option_type_price'),
//        'special_comment',
//        "VARCHAR(255) NOT NULL DEFAULT ''"
//    );
//}

if (!$installer->getConnection()->tableColumnExists($installer->getTable('catalog/product_option'), 'image_mode')) {
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog/product_option'),
        'image_mode',
        "TINYINT(1) NOT NULL DEFAULT '1'"
    );
    
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog/product_option'),
        'exclude_first_image',
        "TINYINT(1) NOT NULL DEFAULT '0'"
    );
}

// fill image table
if ($installer->getConnection()->tableColumnExists($installer->getTable('catalog/product_option_type_value'), 'image_path')) {
    $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
    $helper = Mage::helper('customoptions');
    $select = $connection->select()->from($installer->getTable('catalog/product_option_type_value'), array('option_type_id', 'image_path'))->where("image_path <> '' AND image_path IS NOT NULL");
    $allOptionValueImages = $connection->fetchAll($select);
    
    $installer->run('LOCK TABLES '. $connection->quoteIdentifier($installer->getTable('customoptions/option_type_image'), true) .' WRITE;');
    foreach($allOptionValueImages as $opValImg) {
        $result = $helper->getCheckImgPath($opValImg['image_path']);
        if ($result) {
            list($imagePath, $fileName) = $result;
            $imageFile = $imagePath . $fileName;
            $connection->insert($installer->getTable('customoptions/option_type_image'), array('option_type_id'=>$opValImg['option_type_id'],'image_file'=>$imageFile));
        }
    }
    $installer->run('UNLOCK TABLES;');
    
    $installer->getConnection()->dropColumn($installer->getTable('catalog/product_option_type_value'), 'image_path');
}

$installer->endSetup();