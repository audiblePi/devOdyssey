<?php

$installer = $this;

$installer->startSetup();

$installer->run("

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id  = @entity_type_id,
    attribute_code  = 'ship_possible_boxes',
    backend_type    = 'varchar',
    backend_model  = 'eav/entity_attribute_backend_array',
    frontend_input  = 'multiselect',
    source_model   = 'boxmenu/boxmenu',
    is_required     = 0,
    frontend_label  = 'Possible Packing Boxes',
    note            = 'If selected then you must enter Dimensions for this to work';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='ship_possible_boxes';

insert ignore into {$this->getTable('catalog_eav_attribute')}
    set attribute_id    = @attribute_id,
    is_visible      = 1,
    used_in_product_listing = 0,
    is_filterable_in_search = 0;


");


$installer->endSetup();
