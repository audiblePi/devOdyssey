<?php

$installer = $this;

$installer->startSetup();

$installer->run("

    SELECT @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';
    SELECT @attribute_id:=attribute_id FROM {$this->getTable('eav_attribute')} WHERE attribute_code='ship_possible_boxes' AND entity_type_id = @entity_type_id;

    UPDATE {$this->getTable('eav_attribute')} SET
        backend_model = 'eav/entity_attribute_backend_array',
        frontend_input = 'multiselect',
        backend_type = 'varchar',
	    source_model    = 'boxmenu/boxmenu'
    WHERE attribute_id = @attribute_id;

    INSERT IGNORE into {$this->getTable('catalog_product_entity_varchar')} (entity_type_id, attribute_id, store_id, entity_id, value)
        SELECT entity_type_id, attribute_id, store_id, entity_id, value
        FROM catalog_product_entity_int
        WHERE attribute_id = @attribute_id ;

    DELETE FROM {$this->getTable('catalog_product_entity_int')} WHERE attribute_id =  @attribute_id;

");


$installer->endSetup();
