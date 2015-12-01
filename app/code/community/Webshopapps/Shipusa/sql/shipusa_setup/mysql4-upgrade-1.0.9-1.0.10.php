<?php

$installer = $this;

$installer->startSetup();

$installer->run("

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
    	attribute_code 	= 'ship_case_quantity',
    	backend_type	= 'int',
    	frontend_input	= 'text',
      	is_user_defined	= 1,
	   	is_required	= 0,
        note           =  'Note: If set will divide the item quantity by this to get the ship quantity to use. Ignore if unsure ',
   	    frontend_label	= 'Shipping Divider';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='ship_case_quantity';

insert ignore into {$this->getTable('catalog_eav_attribute')}
    set attribute_id 	= @attribute_id,
    	is_visible 	= 1,
    	used_in_product_listing	= 0,
    	is_filterable_in_search	= 0;



");


$installer->endSetup();
