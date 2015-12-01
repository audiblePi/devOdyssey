<?php

$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE {$this->getTable('boxmenu')} (
  `boxmenu_id` int(11) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `length` varchar(12) NOT NULL default '' ,
  `width` varchar(12) NOT NULL default '',
  `height` varchar(12) NOT NULL default '',
  `multiplier` int(10) NOT NULL default '1',
  PRIMARY KEY (`boxmenu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


");

$installer->endSetup();


