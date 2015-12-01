<?php

$installer = $this;

$installer->startSetup();

if(Mage::helper('wsacommon')->getVersion() == 1.6){
$installer->run("

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';



insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
	attribute_code 	= 'ship_alternate_box',
	backend_type	= 'int',
	frontend_input	= 'select',
    source_model   = 'boxmenu/boxmenu',
    is_required	= 0,
	is_visible 	= 1,
    used_in_product_listing	= 1,
    is_filterable_in_search	= 0,
	frontend_label	= 'Alternative Packing Box';

    	

");
}
else{

$installer->run("

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';


insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
    	attribute_code 	= 'ship_alternate_box',
     	backend_type	= 'int',
		frontend_input	= 'select',
    	source_model   = 'boxmenu/boxmenu',
      	is_user_defined	= 1,
	   	is_required	= 0,
		frontend_label	= 'Alternative Packing Box';
	   	
select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='ship_alternate_box';


insert ignore into {$this->getTable('catalog_eav_attribute')}
    set attribute_id 	= @attribute_id,
    	is_visible 	= 1,
    	used_in_product_listing	= 1,
    	is_filterable_in_search	= 0;


");
}

$installer->endSetup();


