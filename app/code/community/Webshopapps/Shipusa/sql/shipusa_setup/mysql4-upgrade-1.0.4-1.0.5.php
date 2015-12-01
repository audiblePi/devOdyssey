<?php

$installer = $this;

$installer->startSetup();

$installer->run("

ALTER TABLE  {$this->getTable('shipusa_singleboxes')}  CHANGE  `max_box`
`max_box` DECIMAL( 12, 4 ) NOT NULL DEFAULT  '-1';

ALTER TABLE  {$this->getTable('shipusa_shipboxes')}  CHANGE  `quantity`
`quantity` DECIMAL( 12, 4 ) NOT NULL DEFAULT  '-1';


");

$installer->endSetup();