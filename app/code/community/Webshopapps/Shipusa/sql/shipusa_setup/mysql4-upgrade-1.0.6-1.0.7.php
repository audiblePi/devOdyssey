<?php

$installer = $this;

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('shipusa_packages')}
 ADD COLUMN `price` DECIMAL(12,4) NOT NULL DEFAULT '0.0000' AFTER `qty` ;

ALTER TABLE {$this->getTable('shipusa_order_packages')}
 ADD COLUMN `price` DECIMAL(12,4) NOT NULL DEFAULT '0.0000' AFTER `qty` ;


");


$installer->endSetup();
