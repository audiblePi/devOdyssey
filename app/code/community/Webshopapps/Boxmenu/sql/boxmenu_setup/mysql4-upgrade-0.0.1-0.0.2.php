<?php

$installer = $this;

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('boxmenu')} ADD COLUMN `multiplier` int(10) NOT NULL default '-1';


");

$installer->endSetup();


