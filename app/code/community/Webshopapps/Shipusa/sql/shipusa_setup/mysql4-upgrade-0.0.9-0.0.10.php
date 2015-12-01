<?php

$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('shipusa_singleboxes')} (
  `singleboxes_id` int(11) unsigned NOT NULL auto_increment,
  `product_id` int(10) unsigned NOT NULL,
  `box_id` int(10) unsigned NOT NULL,
  `length` decimal(12,4) NOT NULL default -1 ,
  `width` decimal(12,4) NOT NULL default -1,
  `height` decimal(12,4) NOT NULL default -1,
  `max_box` int(10) NOT NULL default '-1',
  `min_qty` int(10) NOT NULL default '0',
  `max_qty` int(10) NOT NULL default '-1',
  `box_volume` decimal(12,4) NOT NULL default -1,
  `item_volume` decimal(12,4) NOT NULL default -1,
  PRIMARY KEY (`singleboxes_id`),
  UNIQUE `IDX_shipbox_product_unique` (`singleboxes_id`, `product_id`),
  KEY `FK_shipusa_singlebox_product_entity` (`product_id`),
  CONSTRAINT `FK_shipusa_singlebox_product_entity` FOREIGN KEY (`product_id`) REFERENCES `{$this->getTable('catalog_product_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;  
    	
");

$installer->endSetup();








