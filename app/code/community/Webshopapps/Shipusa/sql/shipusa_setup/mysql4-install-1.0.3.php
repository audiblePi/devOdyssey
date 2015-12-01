<?php

$installer = $this;

$installer->startSetup();

$installer->run("

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
    	attribute_code 	= 'ship_separately',
    	backend_type	= 'int',
    	frontend_input	= 'boolean',
      	is_user_defined	= 1,
	   	is_required	= 0,
    	frontend_label	= 'Ship Separately';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='ship_separately';

insert ignore into {$this->getTable('catalog_eav_attribute')}
    set attribute_id 	= @attribute_id,
    	is_visible 	= 1,
    	used_in_product_listing	= 1,
    	is_filterable_in_search	= 0;


insert ignore into {$this->getTable('eav_attribute')}
	    set entity_type_id 	= @entity_type_id,
	    	attribute_code 	= 'split_product',
	    	backend_type	= 'int',
	    	frontend_input	= 'boolean',
	    	is_required	= 0,
	    	is_user_defined	= 1,
	    	frontend_label	= 'Product can be split into multiple boxes';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='split_product';

	insert ignore into {$this->getTable('catalog_eav_attribute')}
	    set attribute_id 	= @attribute_id,
	    	is_visible 	= 1,
	    	used_in_product_listing	= 0,
	    	is_filterable_in_search	= 0;

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

	insert ignore into {$this->getTable('eav_attribute')}
	    set entity_type_id 	= @entity_type_id,
	    	attribute_code 	= 'ship_length',
	    	backend_type	= 'decimal',
	    	frontend_input	= 'text',
	    	is_required	= 0,
	    	is_user_defined	= 1,
	    	frontend_label	= 'Dimension Length';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='ship_length';


	insert ignore into {$this->getTable('catalog_eav_attribute')}
	    set attribute_id 	= @attribute_id,
	    	is_visible 	= 1,
	    	used_in_product_listing	= 0,
	    	is_filterable_in_search	= 0;

	insert ignore into {$this->getTable('eav_attribute')}
	    set entity_type_id 	= @entity_type_id,
			attribute_code 	= 'ship_width',
			backend_type	= 'decimal',
			frontend_input	= 'text',
			is_required	= 0,
			is_user_defined	= 1,
			frontend_label	= 'Dimension Width';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='ship_width';

	insert ignore into {$this->getTable('catalog_eav_attribute')}
	    set attribute_id 	= @attribute_id,
	    	is_visible 	= 1,
	    	used_in_product_listing	= 0,
	    	is_filterable_in_search	= 0;

	insert ignore into {$this->getTable('eav_attribute')}
	    set entity_type_id 	= @entity_type_id,
	    	attribute_code 	= 'ship_height',
	    	backend_type	= 'decimal',
	    	frontend_input	= 'text',
	    	is_required	= 0,
	    	is_user_defined	= 1,
	    	frontend_label	= 'Dimension Height';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='ship_height';

	insert ignore into {$this->getTable('catalog_eav_attribute')}
	    set attribute_id 	= @attribute_id,
	    	is_visible 	= 1,
	    	used_in_product_listing	= 0,
	    	is_filterable_in_search	= 0;

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

	insert ignore into {$this->getTable('eav_attribute')}
	    set entity_type_id  = @entity_type_id,
  		attribute_code  = 'ship_box',
  		backend_type    = 'int',
  		frontend_input  = 'select',
	    source_model   = 'boxmenu/boxmenu',
	  	is_required     = 0,
	  	frontend_label  = 'Packing Box';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='ship_box';

	insert ignore into {$this->getTable('catalog_eav_attribute')}
	    set attribute_id    = @attribute_id,
  		is_visible      = 1,
  		used_in_product_listing = 1,
  		is_filterable_in_search = 0;

CREATE TABLE IF NOT EXISTS {$this->getTable('shipusa_shipboxes')} (
  `shipboxes_id` int(11) unsigned NOT NULL auto_increment,
  `product_id` int(10) unsigned NOT NULL,
  `length` decimal(12,4) NOT NULL default '-1' ,
  `width` decimal(12,4) NOT NULL default '-1',
  `height` decimal(12,4) NOT NULL default '-1',
  `weight` decimal(12,4) NOT NULL default '1',
  `declared_value` decimal(12,4) NOT NULL default '1',
  `quantity` int(10) NOT NULL default '1',
  `num_boxes` int(10) NOT NULL default '1',
  PRIMARY KEY (`shipboxes_id`),
  UNIQUE `IDX_shipbox_product_unique` (`shipboxes_id`, `product_id`),
  KEY `FK_shipusa_shipbox_product_entity` (`product_id`),
  CONSTRAINT `FK_shipusa_shipbox_product_entity` FOREIGN KEY (`product_id`) REFERENCES `{$this->getTable('catalog_product_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS {$this->getTable('shipusa_singleboxes')} (
  `singleboxes_id` int(11) unsigned NOT NULL auto_increment,
  `product_id` int(10) unsigned NOT NULL,
  `box_id` int(10) unsigned NOT NULL,
  `length` decimal(12,4) NOT NULL default -1 ,
  `width` decimal(12,4) NOT NULL default -1,
  `height` decimal(12,4) NOT NULL default -1,
  `max_box` int(10) NOT NULL default '-1',
  `min_qty` int(10) NOT NULL default '0',
  `max_qty` int(10) NOT NULL default '-1',
  `box_volume` decimal(12,4) NOT NULL default -1,
  `item_volume` decimal(12,4) NOT NULL default -1,
  PRIMARY KEY (`singleboxes_id`),
  UNIQUE `IDX_shipbox_product_unique` (`singleboxes_id`, `product_id`),
  KEY `FK_shipusa_singlebox_product_entity` (`product_id`),
  CONSTRAINT `FK_shipusa_singlebox_product_entity` FOREIGN KEY (`product_id`) REFERENCES `{$this->getTable('catalog_product_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

delete from {$this->getTable('core_config_data')} where path like 'carriers/fedexsoap%';


CREATE TABLE IF NOT EXISTS {$this->getTable('shipusa_packages')} (
  `package_id` int(10) unsigned NOT NULL auto_increment,
  `quote_id` int(10) unsigned NOT NULL DEFAULT '0',
  `length` decimal(12,4) NOT NULL default -1 ,
  `width` decimal(12,4) NOT NULL default -1,
  `height` decimal(12,4) NOT NULL default -1,
  `weight` decimal(12,4) DEFAULT NULL,
  `qty` decimal(12,4) NOT NULL DEFAULT '0.0000',
  UNIQUE `IDX_shipusa_package_unique` (`package_id`, `quote_id`),
  KEY `FK_shipusa_packages` (`quote_id`),
  CONSTRAINT `FK_shipusa_packages` FOREIGN KEY (`quote_id`) REFERENCES `{$this->getTable('sales_flat_quote')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

  CREATE TABLE IF NOT EXISTS {$this->getTable('shipusa_order_packages')} (
  `package_id` int(10) unsigned NOT NULL auto_increment,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `length` decimal(12,4) NOT NULL default -1 ,
  `width` decimal(12,4) NOT NULL default -1,
  `height` decimal(12,4) NOT NULL default -1,
  `weight` decimal(12,4) DEFAULT NULL,
  `qty` decimal(12,4) NOT NULL DEFAULT '0.0000',
  UNIQUE `IDX_shipusa_order_package_unique` (`package_id`, `order_id`),
  KEY `FK_shipusa_order_packages` (`order_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


");


$entityTypeId = $installer->getEntityTypeId('catalog_product');

$attributeSetArr = $installer->getConnection()->fetchAll("SELECT attribute_set_id FROM {$this->getTable('eav_attribute_set')} WHERE entity_type_id={$entityTypeId}");

$attributeIdArry = array();
$attributeIdArry[] = $installer->getAttributeId($entityTypeId,'split_product');
$attributeIdArry[] = $installer->getAttributeId($entityTypeId,'ship_separately');


foreach( $attributeIdArry as $attributeId) {

	foreach( $attributeSetArr as $attr)
	{
		$attributeSetId= $attr['attribute_set_id'];

		$installer->addAttributeGroup($entityTypeId,$attributeSetId,'Shipping','99');

		$attributeGroupId = $installer->getAttributeGroupId($entityTypeId,$attributeSetId,'Shipping');

		$installer->addAttributeToGroup($entityTypeId,$attributeSetId,$attributeGroupId,$attributeId,'99');

	};
};

$installer->endSetup();


