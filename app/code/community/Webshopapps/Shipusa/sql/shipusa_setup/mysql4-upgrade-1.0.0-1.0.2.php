<?php

$installer = $this;

$installer->startSetup();

$installer->run("

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
		
");

$installer->endSetup();








