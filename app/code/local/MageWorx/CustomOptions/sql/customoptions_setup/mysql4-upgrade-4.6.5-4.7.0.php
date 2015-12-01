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

/* @var $installer MageWorx_CustomOptions_Model_Mysql4_Setup */
$installer = $this;
$installer->startSetup();


$installer->run("
-- DROP TABLE IF EXISTS `{$installer->getTable('customoptions/option_type_special_price')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('customoptions/option_type_special_price')}` (
  `option_type_special_price_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `option_type_price_id` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_group_id` smallint(3) unsigned NOT NULL DEFAULT '32000' COMMENT '32000 - All Groups',
  `price` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `price_type` enum('fixed','percent') NOT NULL DEFAULT 'fixed',
  `comment` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`option_type_special_price_id`),
  UNIQUE KEY `option_type_price_id+customer_group_id` (`option_type_price_id`,`customer_group_id`),
  CONSTRAINT `FK_MAGEWORX_CUSTOM_OPTIONS_INDEX_OPTION_TYPE_SPECIAL_PRICE` FOREIGN KEY (`option_type_price_id`) REFERENCES `{$installer->getTable('catalog/product_option_type_price')}` (`option_type_price_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

if ($installer->getConnection()->tableColumnExists($installer->getTable('catalog/product_option_type_price'), 'special_price')) {
    $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
    $select = $connection->select()->from($installer->getTable('catalog/product_option_type_price'), array('option_type_price_id', 'price_type', 'special_price', 'special_comment'))->where("special_price IS NOT NULL AND special_price > 0");
    $allSpecialPrices = $connection->fetchAll($select);
    
    $installer->run('LOCK TABLES '. $connection->quoteIdentifier($installer->getTable('customoptions/option_type_special_price'), true) .' WRITE;');
    foreach($allSpecialPrices as $value) {
        $connection->insert(
                $installer->getTable('customoptions/option_type_special_price'), 
                array('option_type_price_id'=>$value['option_type_price_id'], 'price'=>$value['special_price'], 'price_type'=>$value['price_type'], 'comment'=>$value['special_comment'])
            );
    }
    $installer->run('UNLOCK TABLES;');
    
    $installer->getConnection()->dropColumn($installer->getTable('catalog/product_option_type_price'), 'special_comment');
    $installer->getConnection()->dropColumn($installer->getTable('catalog/product_option_type_price'), 'special_price');
}
  

if (!$installer->getConnection()->tableColumnExists($installer->getTable('customoptions/option_type_tier_price'), 'customer_group_id')) {
    $installer->getConnection()->addColumn(
        $installer->getTable('customoptions/option_type_tier_price'),
        'customer_group_id',
        "smallint(3) unsigned NOT NULL DEFAULT '32000' COMMENT '32000 - All Groups' AFTER `option_type_price_id`"
    );

    $installer->run("ALTER TABLE `{$installer->getTable('customoptions/option_type_tier_price')}` 
        DROP INDEX `option_type_price_id+qty`,
        ADD UNIQUE `option_type_price_id+customer_group_id+qty` (`option_type_price_id` , `customer_group_id`, `qty`);");
}

$installer->endSetup();