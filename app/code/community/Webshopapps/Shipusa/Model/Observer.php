<?php

/*
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/
class Webshopapps_Shipusa_Model_Observer extends Mage_Core_Model_Abstract
{

	public function catalogProductPrepareSave($observer)
	{
		$request = $observer->getEvent()->getRequest();
		$product = $observer->getEvent()->getProduct();

		$this->prepareShipBoxes($request,$product);
		$this->prepareSingleBoxes($request,$product);
        $this->prepareFlatBoxes($request,$product);
    }


	private function prepareShipBoxes($request,$product) {

		$newShipBoxes = $request->getParam('shipusa_shipboxes');
		if (!$newShipBoxes || !is_array($newShipBoxes)) {
			return;
		}
		unset($newShipBoxes['$ROW']);

		$existingDataIn = Mage::getModel('shipusa/shipboxes')->getCollection()
		->addProductFilter($product->getSku());

		$existingShipBoxes = array();
		foreach ($existingDataIn as $box) {
			$existingShipBoxes[$box['shipboxes_id']] = $box;
		}

		$insert = array();
		$update = array();
		$delete = array();

		$boxUpdates = array();
		foreach ($newShipBoxes as $box) {
			if (!empty($box['shipboxes_id'])) {
				$boxUpdates[$box['shipboxes_id']]=$box;
			}
		}

		foreach ($existingShipBoxes as $id=>$shipBox) {
			if (empty($boxUpdates[$id])) {
				// is a delete
				$delete[] = $id;
			}
		}
		foreach ($newShipBoxes as $shipBox) {
			if (empty($shipBox['shipboxes_id'])) {
				// is an insert
				$insert[] = $shipBox;
			} else {
				// is a update
				$existingShipBox = $existingShipBoxes[$shipBox['shipboxes_id']];
				if ($shipBox['length']!=$existingShipBox['length']||
				$shipBox['width']!=$existingShipBox['width']||
				$shipBox['height']!=$existingShipBox['height']||
				$shipBox['weight']!=$existingShipBox['weight']||
				$shipBox['declared_value']!=$existingShipBox['declared_value']||
				$shipBox['num_boxes']!=$existingShipBox['num_boxes']||
				$shipBox['quantity']!=$existingShipBox['quantity']) {
					$update[$shipBox['shipboxes_id']] = $shipBox;
				}
			}
		}

		$data = compact('insert', 'update', 'delete');
		$product->setUpdateShipBoxes($data);
	}

        private function prepareSingleBoxes($request,$product) {

            $newSingleBoxes = $request->getParam('shipusa_singleboxes');
            if (!$newSingleBoxes || !is_array($newSingleBoxes)) {
                return;
            }
            unset($newSingleBoxes['$ROW']);

            $existingDataIn = Mage::getModel('shipusa/singleboxes')->getCollection()
                ->addProductFilter($product->getSku());

            $existingSingleBoxes = array();
            foreach ($existingDataIn as $box) {
                $existingSingleBoxes[$box['singleboxes_id']] = $box;
            }

            $insert = array();
            $update = array();
            $delete = array();

            $boxUpdates = array();
            foreach ($newSingleBoxes as $box) {
                if (!empty($box['singleboxes_id'])) {
                    $boxUpdates[$box['singleboxes_id']]=$box;
                }
            }

            foreach ($existingSingleBoxes as $id=>$shipBox) {
                if (empty($boxUpdates[$id])) {
                    // is a delete
                    $delete[] = $id;
                }
            }
            foreach ($newSingleBoxes as $shipBox) {
                if (empty($shipBox['singleboxes_id'])) {
                    // is an insert
                    $this->updateVolumes($shipBox);
                    $insert[] = $shipBox;
                } else {
                    // is a update
                    $existingShipBox = $existingSingleBoxes[$shipBox['singleboxes_id']];
                    if ($shipBox['length']!=$existingShipBox['length']||
                        $shipBox['width']!=$existingShipBox['width']||
                        $shipBox['height']!=$existingShipBox['height']||
                        $shipBox['weight']!=$existingShipBox['weight']||
                        $shipBox['box_id']!=$existingShipBox['box_id']||
                        $shipBox['min_qty']!=$existingShipBox['min_qty']||
                        $shipBox['max_box']!=$existingShipBox['max_box']||
                        $shipBox['max_qty']!=$existingShipBox['max_qty']) {
                        $this->updateVolumes($shipBox);
                        $update[$shipBox['singleboxes_id']] = $shipBox;
                    }
                }
            }

            $data = compact('insert', 'update', 'delete');
            $product->setUpdateSingleBoxes($data);
        }

    private function prepareFlatBoxes($request,$product) {

        $newFlatBoxes = $request->getParam('shipusa_flatboxes');
        if (!$newFlatBoxes || !is_array($newFlatBoxes)) {
            return;
        }
        unset($newFlatBoxes['$ROW']);

        $existingDataIn = Mage::getModel('shipusa/flatboxes')->getCollection()
            ->addProductFilter($product->getSku());

        $existingSingleBoxes = array();
        foreach ($existingDataIn as $box) {
            $existingSingleBoxes[$box['flatboxes_id']] = $box;
        }

        $insert = array();
        $update = array();
        $delete = array();

        $boxUpdates = array();
        foreach ($newFlatBoxes as $box) {
            if (!empty($box['flatboxes_id'])) {
                $boxUpdates[$box['flatboxes_id']]=$box;
            }
        }

        foreach ($existingSingleBoxes as $id=>$shipBox) {
            if (empty($boxUpdates[$id])) {
                // is a delete
                $delete[] = $id;
            }
        }
        foreach ($newFlatBoxes as $shipBox) {
            if (empty($shipBox['flatboxes_id'])) {
                // is an insert
                $this->updateVolumes($shipBox);
                $insert[] = $shipBox;
            } else {
                // is a update
                $existingShipBox = $existingSingleBoxes[$shipBox['flatboxes_id']];
                if ($shipBox['length']!=$existingShipBox['length']||
                    $shipBox['width']!=$existingShipBox['width']||
                    $shipBox['height']!=$existingShipBox['height']||
                    $shipBox['weight']!=$existingShipBox['weight']||
                    $shipBox['box_id']!=$existingShipBox['box_id']||
                    $shipBox['min_qty']!=$existingShipBox['min_qty']||
                    $shipBox['max_box']!=$existingShipBox['max_box']||
                    $shipBox['max_qty']!=$existingShipBox['max_qty']) {
                    $this->updateVolumes($shipBox);
                    $update[$shipBox['flatboxes_id']] = $shipBox;
                }
            }
        }

        $data = compact('insert', 'update', 'delete');
        $product->setUpdateFlatBoxes($data);
    }

    public function postError($observer) {
		if (!Mage::helper('wsacommon')->checkItems('c2hpcHBpbmcvc2hpcHVzYS9zaGlwX29uY2U=',
			'aWdsb29tZQ==','c2hpcHBpbmcvc2hpcHVzYS9zZXJpYWw=')) {
		$session = Mage::getSingleton('adminhtml/session');
		$session->addError(Mage::helper('adminhtml')->__(base64_decode('U2VyaWFsIEtleSBJcyBOT1QgVmFsaWQgZm9yIFdlYlNob3BBcHBzIERpbWVuc2lvbmFsIFNoaXBwaW5n')))  ;
			}
	}

	private function updateVolumes(&$shipBox) {
		if ( $shipBox['max_box']== '' ||  $shipBox['max_box']==0) {
			$shipBox['max_box'] = -1;
		}
		if ( $shipBox['max_qty']== '' ||  $shipBox['max_qty']==0) {
			$shipBox['max_qty'] = -1;
        }

	}


	public function catalogProductSaveAfter($observer)
	{
		$product = $observer->getEvent()->getProduct();

		$this->saveShipBoxes($product);
		$this->saveSingleBoxes($product);
        $this->saveFlatBoxes($product);
    }


	private function saveShipBoxes($product) {

		$data = $product->getUpdateShipBoxes();
		if (!$data) {
			return;
		}

		$model = Mage::getModel('shipusa/shipboxes');

		$res = Mage::getSingleton('core/resource');
		$write = $res->getConnection('shipusa_write');
		$table = $res->getTableName('shipusa/shipboxes');
		if (!empty($data['delete'])) {
			$write->delete($table, $write->quoteInto('shipboxes_id in (?)', $data['delete']));
		}

        if (!empty($data['insert'])) {
            foreach ($data['insert'] as $shipBox) {
                $shipBox['sku'] = $product->getSku();
                $write->insertOnDuplicate($table, $shipBox);
            }
		}

		if (!empty($data['update'])) {
			foreach ($data['update'] as $id=>$shipBox) {
				$write->update($table, $shipBox, 'shipboxes_id='.(int)$id);
			}
		}
	}

	private function saveSingleBoxes($product) {

		$data = $product->getUpdateSingleBoxes();
		if (!$data) {
			return;
		}

		$model = Mage::getModel('shipusa/singleboxes');

		$res = Mage::getSingleton('core/resource');
		$write = $res->getConnection('shipusa_write');
		$table = $res->getTableName('shipusa/singleboxes');
		if (!empty($data['delete'])) {
			$write->delete($table, $write->quoteInto('singleboxes_id in (?)', $data['delete']));
		}

		if (!empty($data['insert']))  {
			foreach ($data['insert'] as $shipBox) {
                  $shipBox['sku'] = $product->getSku();
                  $write->insertOnDuplicate($table, $shipBox);
            }
        }

		if (!empty($data['update'])) {
			foreach ($data['update'] as $id=>$shipBox) {
				$write->update($table, $shipBox, 'singleboxes_id='.(int)$id);
			}
		}
	}

    private function saveFlatBoxes($product) {

        $data = $product->getUpdateFlatBoxes();

        if (!$data) {
            return;
        }

        $res = Mage::getSingleton('core/resource');
        $write = $res->getConnection('shipusa_write');
        $table = $res->getTableName('shipusa/flatboxes');
        if (!empty($data['delete'])) {
            $write->delete($table, $write->quoteInto('flatboxes_id in (?)', $data['delete']));
        }

        if (!empty($data['insert'])) {
            foreach ($data['insert'] as $shipBox) {
                $shipBox['sku'] = $product->getSku();
                $write->insertOnDuplicate($table, $shipBox);
            }
        }

        if (!empty($data['update'])) {
            foreach ($data['update'] as $id=>$shipBox) {
                $write->update($table, $shipBox, 'flatboxes_id='.(int)$id);
            }
        }
    }


	public function saveOrderAfter($observer)
    {
        try
        {
            $addCommentHistory = Mage::getStoreConfigFlag('shipping/shipusa/package_comment_history');
            $setPackingWeight = Mage::getStoreConfig('shipping/shipusa/set_order_weight');

			if ($addCommentHistory || $setPackingWeight)
            {
                $orderIds = $observer->getData('order_ids');
                foreach ($orderIds as $orderId) {
                    $order = Mage::getModel('sales/order')->load($orderId);


                    $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                    $this->storeBoxes($quote);

                    $quoteId = $quote->getId();

                    $packagesColl= Mage::getModel('shipusa/packages')->getCollection()
                        ->addQuoteFilter($quoteId);

                    foreach ($packagesColl as $box) {
                        Mage::getModel('shipusa/order_packages')
                            ->setOrderId($orderId)
                            ->setLength($box['length'])
                            ->setWidth($box['width'])
                            ->setHeight($box['height'])
                            ->setWeight($box['weight'])
                            ->setQty($box['qty'])
                            ->setPrice($box['price'])
                            ->save();
                    }



                    $packagesModel= Mage::getModel('shipusa/order_packages')->getCollection()->addOrderFilter($orderId);
                    $boxes = $packagesModel->getData();
                    $actualWeight = 0;
                    $boxText = '';

                    foreach ($boxes as $key=>$box)
                    {
                        $boxText .= '#'.($key+1);
                        $boxText .= ': ' . $box['length'];
                        $boxText .= 'x' . $box['width'] ;
                        $boxText .= 'x'. $box['height'] ;
                        $boxText .= ': W='.$box['weight'] . '#' ;
                        $boxText .= ' Price='.$box['price']  . '</br>';
                        $actualWeight += $box['weight'];
                    }

                    if($setPackingWeight && $actualWeight > 0)
                    {
                        $order->setWeight($actualWeight);
                    }

                    if($addCommentHistory)
                    {
                        $order->addStatusToHistory($order->getStatus(), $boxText, false);
                    }

                    $order->save();
                }
            }
		}
		catch (Exception $e) {
			Mage::logException($e);
		}
	}


	/**
	 * Mage::dispatchEvent('sales_convert_quote_to_order', array('order'=>$order, 'quote'=>$quote));
	 **/
	public function salesConvertQuoteToOrder($observer) {


		try {


		} catch (Exception $e) {
			Mage::logException($e);
		}
	}


	private function storeBoxes($quote) {

		$quoteId = $quote->getId();
		$packageColl = Mage::getModel('shipusa/packages')->getCollection()
		->addQuoteFilter($quoteId);
		foreach ($packageColl as $package) {
			$package->delete();
		}


		$boxes=Mage::getSingleton('shipusa/dimcalculate')->getBoxes($quote->getAllItems());

		foreach ($boxes as $box) {
			$package = Mage::getModel('shipusa/packages')
			->setQuoteId($quoteId)
			->setLength($box['length'])
			->setWidth($box['width'])
			->setHeight($box['height'])
			->setWeight($box['weight'])
			->setQty($box['qty'])
			->setPrice($box['price']);
			$packageColl->addItem($package);
		}
		$packageColl->save();
	}

}