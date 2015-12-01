<?php

$installer = $this;

$installer->startSetup();

if(Mage::helper('wsacommon')->getVersion() == 1.6){
	$installer->run("
	
	select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';
	
	insert ignore into {$this->getTable('eav_attribute')}
	    set entity_type_id 	= @entity_type_id,
	    	attribute_code 	= 'split_product',
	    	backend_type	= 'int',
	    	frontend_input	= 'boolean',
	      	is_user_defined	= 1,
	   		used_in_product_listing = 0,
	   		is_required	= 0,
	    	is_filterable_in_search	= 0,
		    frontend_label	= 'Product can be split into multiple boxes';
    	
");

} else{
	$installer->run("

	select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';
	
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
	    	is_visible 	= 0,
	    	used_in_product_listing	= 0,
	    	is_filterable_in_search	= 0;

	");
	
};

$entityTypeId = $installer->getEntityTypeId('catalog_product');

$attributeSetArr = $installer->getConnection()->fetchAll("SELECT attribute_set_id FROM {$this->getTable('eav_attribute_set')} WHERE entity_type_id={$entityTypeId}");

$attributeIdArry = array();
$attributeIdArry[] = $installer->getAttributeId($entityTypeId,'split_product');


foreach( $attributeIdArry as $attributeId) {
	
	foreach( $attributeSetArr as $attr)
	{	
		$attributeSetId= $attr['attribute_set_id'];
				
		$attributeGroupId = $installer->getAttributeGroupId($entityTypeId,$attributeSetId,'Shipping');
		
		if ($attributeGroupId!='' && is_numeric($attributeGroupId)) {
			$installer->addAttributeToGroup($entityTypeId,$attributeSetId,$attributeGroupId,$attributeId,'99');	
		}
	
	};
};
   

$installer->endSetup();








