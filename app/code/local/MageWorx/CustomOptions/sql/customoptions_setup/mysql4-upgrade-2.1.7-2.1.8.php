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
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@mageworx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 * or send an email to sales@mageworx.com
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @copyright  Copyright (c) 2011 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Custom Options extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */

/* @var $installer MageWorx_CustomOptions_Model_Mysql4_Setup */
$installer = $this;
$installer->startSetup();

//$installer->run("ALTER TABLE `{$installer->getTable('customoptions/group')}` CHANGE `group_id` `group_id` SMALLINT UNSIGNED NOT NULL auto_increment;
//ALTER TABLE `{$installer->getTable('customoptions/relation')}` CHANGE `group_id` `group_id` SMALLINT UNSIGNED NOT NULL;");

//if (!$installer->getConnection()->tableColumnExists($installer->getTable('catalog/product_option'), 'view_mode')) {
//    $installer->getConnection()->addColumn(
//        $installer->getTable('catalog/product_option'),
//        'view_mode',
//        "tinyint(1) NOT NULL DEFAULT '1'"
//    );
//}

if (!$installer->getConnection()->tableColumnExists($installer->getTable('catalog/product_option'), 'in_group_id')) {
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog/product_option'),
        'in_group_id',
        "SMALLINT UNSIGNED NOT NULL DEFAULT '0'"
    );
}

if (!$installer->getConnection()->tableColumnExists($installer->getTable('catalog/product_option_type_value'), 'in_group_id')) {
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog/product_option_type_value'),
        'in_group_id',
        "SMALLINT UNSIGNED NOT NULL DEFAULT '0'"
    );
}    


if ($installer->getConnection()->tableColumnExists($installer->getTable('catalog/product_option'), 'customoptions_status')) {
    $installer->getConnection()->dropColumn(
        $installer->getTable('catalog/product_option'),
        'customoptions_status'
    );
}    

$installer->endSetup();