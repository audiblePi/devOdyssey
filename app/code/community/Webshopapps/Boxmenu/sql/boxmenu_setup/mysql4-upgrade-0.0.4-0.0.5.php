<?php

$installer = $this;

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('boxmenu')} ADD COLUMN `box_type` int(1) unsigned NOT NULL default '4';


");

$installer->endSetup();