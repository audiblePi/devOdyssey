<?php

$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('boxmenu')} (
  `boxmenu_id` 		int(11) unsigned NOT NULL auto_increment,
  `title` 			varchar(255) NOT NULL default '',
  `length` 			decimal(12,4) NOT NULL default '-1' ,
  `width` 			decimal(12,4) NOT NULL default '-1',
  `height` 			decimal(12,4) NOT NULL default '-1',
  `multiplier` 		int(10) NOT NULL default '-1',
  `max_weight` 		decimal(12,4) NOT NULL default '-1',
  `packing_weight` 	decimal(12,4) NOT NULL default '0',
  `volume` 			decimal(12,4) NOT NULL default '-1',
  PRIMARY KEY (`boxmenu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


");

$installer->endSetup();


