<?php

$installer = $this;

$installer->startSetup();

$shipBoxTable = $installer->getTable('shipusa_shipboxes');
$singleBoxTable = $installer->getTable('shipusa_singleboxes');
$flatBoxTable = $installer->getTable('shipusa_flatboxes');
$catalogProductEntityTable = $installer->getTable('catalog_product_entity');

// add sku to table
// add unique index
if  (Mage::helper('wsalogger')->getNewVersion() > 10 ) {

    $skuDetails =  array(
        'type'    	=> Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 64,
        'comment' 	=> 'SKU',
        'nullable' 	=> 'true',
    );

    $installer->getConnection()->addColumn($shipBoxTable,'sku', $skuDetails );
    $installer->getConnection()->addColumn($singleBoxTable,'sku', $skuDetails );
    $installer->getConnection()->addColumn($flatBoxTable,'sku', $skuDetails );

} else {

    $installer->getConnection()->addColumn($shipBoxTable,'sku',
            "VARCHAR(64) NULL AFTER `shipboxes_id`" );
    $installer->getConnection()->addColumn($singleBoxTable,'sku',
            "VARCHAR(64) NULL AFTER `singleboxes_id`" );
    $installer->getConnection()->addColumn($flatBoxTable,'sku',
            "VARCHAR(64) NULL AFTER `flatboxes_id`" );

}

// lets add the data we need

// for every element in table, look up sku and update the table with it catalog_product_entity

if ($installer->getConnection()->tableColumnExists($singleBoxTable, 'product_id')) {
    $select =  $installer->getConnection()->select()
        ->reset()
        ->join(array('relation_table' => $catalogProductEntityTable),
        'relation_table.entity_id = main_table.product_id',
        array(
            'sku' => 'sku'
        )
    );

    $installer->getConnection()->query(
        $select->crossUpdateFromSelect(array('main_table' => $singleBoxTable))
    );
}

if ($installer->getConnection()->tableColumnExists($shipBoxTable, 'product_id')) {

    $select =  $installer->getConnection()->select()
        ->reset()
        ->join(array('relation_table' => $catalogProductEntityTable),
            'relation_table.entity_id = main_table.product_id',
            array(
                'sku' => 'sku'
            )
        );

    $installer->getConnection()->query(
        $select->crossUpdateFromSelect(array('main_table' => $shipBoxTable))
    );
}

if ($installer->getConnection()->tableColumnExists($flatBoxTable, 'product_id')) {

    $select =  $installer->getConnection()->select()
        ->reset()
        ->join(array('relation_table' => $catalogProductEntityTable),
            'relation_table.entity_id = main_table.product_id',
            array(
                'sku' => 'sku'
            )
        );

    $installer->getConnection()->query(
        $select->crossUpdateFromSelect(array('main_table' => $flatBoxTable))
    );
}



$installer->getConnection()->dropColumn($flatBoxTable,'item_volume' );
$installer->getConnection()->dropColumn($singleBoxTable,'item_volume' );
$installer->getConnection()->dropColumn($flatBoxTable,'box_volume' );
$installer->getConnection()->dropColumn($singleBoxTable,'box_volume' );
$installer->getConnection()->dropColumn($shipBoxTable,'product_id' );
$installer->getConnection()->dropColumn($flatBoxTable,'product_id' );
$installer->getConnection()->dropColumn($singleBoxTable,'product_id' );



$indexList = $installer->getConnection()->getIndexList($shipBoxTable);

foreach ($indexList as $key=>$value) {
    if ($key =='PRIMARY') { continue;}
    $installer->run("
       ALTER TABLE {$shipBoxTable}
          DROP INDEX $key;
    ");
}

$indexList = $installer->getConnection()->getIndexList($singleBoxTable);

foreach ($indexList as $key=>$value) {
    if ($key =='PRIMARY') { continue;}
    $installer->run("
       ALTER TABLE {$singleBoxTable}
          DROP INDEX $key;
    ");
}
$indexList = $installer->getConnection()->getIndexList($flatBoxTable);

foreach ($indexList as $key=>$value) {
    if ($key =='PRIMARY') { continue;}
    $installer->run("
       ALTER TABLE {$flatBoxTable}
          DROP INDEX $key;
    ");
}


$installer->getConnection()->addKey(
    $shipBoxTable,
    'shipusa_shipbox_sku_entity',
    array('sku')
);


$installer->getConnection()->addKey(
    $singleBoxTable,
    'shipusa_singlebox_sku_entity',
    array( 'sku')
);


$installer->getConnection()->addKey(
    $flatBoxTable,
    'shipusa_flatbox_sku_entity',
    array( 'sku')
);




if  (Mage::helper('wsalogger')->getNewVersion() > 10 ) {

    // add unique key on sku, ship box id

    $installer->getConnection()->addIndex(
        $shipBoxTable,
        $installer->getIdxName(
            'shipusa_shipboxes',
            array('shipboxes_id', 'sku'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('shipboxes_id', 'sku'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    );


    $installer->getConnection()->addIndex(
        $singleBoxTable,
        $installer->getIdxName(
            'shipusa_singleboxes',
            array('singleboxes_id', 'sku'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('singleboxes_id', 'sku'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    );


    $installer->getConnection()->addIndex(
        $flatBoxTable,
        $installer->getIdxName(
            'shipusa_flatboxes',
            array('flatboxes_id', 'sku'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('flatboxes_id', 'sku'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    );

} else {

    $installer->getConnection()->addKey(
        $shipBoxTable,
        'IDX_shipbox_sku_unique',
        array('shipboxes_id', 'sku'),
        'unique'
    );


    $installer->getConnection()->addKey(
        $singleBoxTable,
        'IDX_shipbox_sku_unique',
        array('singleboxes_id', 'sku'),
        'unique'
    );


    $installer->getConnection()->addKey(
        $flatBoxTable,
        'IDX_flatbox_sku_unique',
        array('flatboxes_id', 'sku'),
        'unique'
    );

};



$installer->getConnection()->addConstraint(
    'FK_shipusa_shipbox_sku_entity',
    $shipBoxTable,
    'sku',
    $catalogProductEntityTable,
    'sku'
);




$installer->getConnection()->addConstraint(
    'FK_shipusa_singlebox_sku_entity',
    $singleBoxTable,
    'sku',
    $catalogProductEntityTable,
    'sku'
);



$installer->getConnection()->addConstraint(
    'FK_shipusa_flatbox_sku_entity',
    $flatBoxTable,
    'sku',
    $catalogProductEntityTable,
    'sku'
);



$installer->endSetup();