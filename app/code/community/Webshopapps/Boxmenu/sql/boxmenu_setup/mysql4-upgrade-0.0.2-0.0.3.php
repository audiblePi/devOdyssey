<?php

$installer = $this;

$installer->startSetup();

$weightAttr = array(
	'type'    	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
	'comment' 	=> 'Max Weight',
	'length'  	=> '10',
	'nullable' 	=> 'false',
	'default' 	=> '-1');

$installer->getConnection()->addColumn($installer->getTable('boxmenu'),'max_weight',$weightAttr);	

$installer->endSetup();


