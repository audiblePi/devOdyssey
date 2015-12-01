<?php

$installer = $this;

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('boxmenu')} ADD COLUMN `packing_weight` decimal(12,4) NOT NULL default '0';
ALTER TABLE {$this->getTable('boxmenu')} ADD COLUMN `volume` decimal(12,4) NOT NULL default '-1';

UPDATE {$this->getTable('boxmenu')}
	SET `volume` = `length` * `width` * `height`;

");



$installer->endSetup();


