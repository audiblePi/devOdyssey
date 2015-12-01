<?php

$installer = $this;

$installer->startSetup();

$installer->run("


CREATE TABLE IF NOT EXISTS {$this->getTable('shipusa_packages')} (
  `package_id` int(10) unsigned NOT NULL auto_increment,
  `quote_id` int(10) unsigned NOT NULL DEFAULT '0',
  `length` decimal(12,4) NOT NULL default -1 ,
  `width` decimal(12,4) NOT NULL default -1,
  `height` decimal(12,4) NOT NULL default -1,
  `weight` decimal(12,4) DEFAULT NULL,
  `qty` decimal(12,4) NOT NULL DEFAULT '0.0000',
  UNIQUE `IDX_shipbox_product_unique` (`package_id`, `quote_id`),
  KEY `IDX_shipusa_package_unique` (`quote_id`),
  CONSTRAINT `FK_shipusa_packages` FOREIGN KEY (`quote_id`) REFERENCES `{$this->getTable('sales_flat_quote')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
  
  CREATE TABLE IF NOT EXISTS {$this->getTable('shipusa_order_packages')} (
  `package_id` int(10) unsigned NOT NULL auto_increment,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `length` decimal(12,4) NOT NULL default -1 ,
  `width` decimal(12,4) NOT NULL default -1,
  `height` decimal(12,4) NOT NULL default -1,
  `weight` decimal(12,4) DEFAULT NULL,
  `qty` decimal(12,4) NOT NULL DEFAULT '0.0000',
  UNIQUE `IDX_shipusa_order_package_unique` (`package_id`, `order_id`),
  KEY `FK_shipusa_order_packages` (`order_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
  
UPDATE {$this->getTable('eav_attribute')} SET `is_user_defined` = '1' WHERE 'attribute_code'='ship_box';  
UPDATE {$this->getTable('eav_attribute')} SET `is_user_defined` = '1' WHERE 'attribute_code'='ship_alternate_box';  

");

$installer->endSetup();








