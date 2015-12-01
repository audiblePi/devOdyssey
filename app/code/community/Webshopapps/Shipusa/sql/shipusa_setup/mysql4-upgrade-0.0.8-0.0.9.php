<?php

$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('shipusa_shipboxes')} (
  `shipboxes_id` int(11) unsigned NOT NULL auto_increment,
  `product_id` int(10) unsigned NOT NULL,
  `length` decimal(12,4) NOT NULL default -1 ,
  `width` decimal(12,4) NOT NULL default -1,
  `height` decimal(12,4) NOT NULL default -1,
  `weight` decimal(12,4) NOT NULL default '1',
  `declared_value` decimal(12,4) NOT NULL default '1',
  `quantity` int(10) NOT NULL default '1',
  `num_boxes` int(10) NOT NULL default '1',
  PRIMARY KEY (`shipboxes_id`),
  UNIQUE `IDX_shipbox_product_unique` (`shipboxes_id`, `product_id`),
  KEY `FK_shipusa_shipbox_product_entity` (`product_id`),
  CONSTRAINT `FK_shipusa_shipbox_product_entity` FOREIGN KEY (`product_id`) REFERENCES `{$this->getTable('catalog_product_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    	
");

$installer->endSetup();








