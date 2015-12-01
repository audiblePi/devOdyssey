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
$installer = $this;
$installer->startSetup();

if (!$installer->getConnection()->tableColumnExists($installer->getTable('catalog/product'), 'sku_policy')) {
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog/product'),
        'sku_policy',
        "TINYINT (1) NOT NULL DEFAULT 0"
    );
    
    $installer->run("
        UPDATE `{$installer->getTable('catalog_product_entity')}` AS t1 SET t1.`sku_policy` = 3 WHERE (SELECT COUNT(*) FROM `{$installer->getTable('catalog_product_option')}` WHERE `product_id` = t1.`entity_id` AND `sku_policy` = 3) > 0;
        UPDATE `{$installer->getTable('catalog_product_option')}` SET `sku_policy` = 0 WHERE `sku_policy` = 3;
    ");
}

if (!$installer->getConnection()->tableColumnExists($installer->getTable('customoptions/group'), 'sku_policy')) {    
    $installer->getConnection()->addColumn(
        $installer->getTable('customoptions/group'),
        'sku_policy',
        "TINYINT (1) NOT NULL DEFAULT 0"
    );
}

$installer->endSetup();