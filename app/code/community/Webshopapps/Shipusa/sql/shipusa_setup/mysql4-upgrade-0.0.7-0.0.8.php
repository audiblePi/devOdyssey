<?php

$installer = $this;

$installer->startSetup();

$installer->run("

delete from {$this->getTable('core_config_data')} where path like 'carriers/fedexsoap%';

");

$installer->endSetup();








