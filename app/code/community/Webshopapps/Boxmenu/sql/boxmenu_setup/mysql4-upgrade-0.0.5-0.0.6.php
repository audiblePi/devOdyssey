<?php

$installer = $this;

$installer->startSetup();

$installer->run("

UPDATE {$this->getTable('boxmenu')} SET box_type='4' WHERE box_type IS NULL or box_type='';

");

$installer->endSetup();