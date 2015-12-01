<?php

$installer = $this;

$installer->startSetup();

$installer->run("
ALTER IGNORE TABLE {$this->getTable('shipusa_shipboxes')}
  DROP KEY `IDX_shipbox_sku_unique`,
  DROP KEY `shipusa_shipbox_sku_entity`,
  ADD UNIQUE `IDX_shipbox_sku_unique` (`sku`,`length`,`width`,`height`,`weight`,`declared_value`,`quantity`,`num_boxes`),
  ADD KEY `shipusa_shipbox_sku_entity` (`sku`,`length`,`width`,`height`,`weight`,`declared_value`,`quantity`,`num_boxes`);

  ALTER IGNORE TABLE {$this->getTable('shipusa_singleboxes')}
  DROP KEY `IDX_singlebox_sku_unique`,
  DROP KEY `shipusa_singlebox_sku_entity`,
  ADD UNIQUE `IDX_singlebox_sku_unique` (`sku`,`box_id`,`length`,`width`,`height`,`max_box`,`min_qty`,`max_qty`),
  ADD KEY `shipusa_singlebox_sku_entity` (`sku`,`box_id`,`length`,`width`,`height`,`max_box`,`min_qty`,`max_qty`);

  ALTER IGNORE TABLE {$this->getTable('shipusa_flatboxes')}
  DROP KEY `IDX_flatbox_sku_unique`,
  DROP KEY `shipusa_flatbox_sku_entity`,
  ADD UNIQUE `IDX_flatbox_sku_unique` (`sku`,`box_id`,`length`,`width`,`height`,`max_box`,`min_qty`,`max_qty`),
  ADD KEY `shipusa_flatbox_sku_entity` (`sku`,`box_id`,`length`,`width`,`height`,`max_box`,`min_qty`,`max_qty`);
");


$installer->endSetup();