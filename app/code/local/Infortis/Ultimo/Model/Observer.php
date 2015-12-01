<?php

class Infortis_Ultimo_Model_Observer
{
	/**
	 * After any system config is saved
	 */
	public function hookTo_controllerActionPostdispatchAdminhtmlSystemConfigSave()
	{
		$section = Mage::app()->getRequest()->getParam('section');
		if ($section == 'ultimo_layout')
		{
			$websiteCode = Mage::app()->getRequest()->getParam('website');
			$storeCode = Mage::app()->getRequest()->getParam('store');

			$cg = Mage::getSingleton('ultimo/cssgen_generator');
			$cg->generateCss('grid',   $websiteCode, $storeCode);
			$cg->generateCss('layout', $websiteCode, $storeCode);
		}
		elseif ($section == 'ultimo_design')
		{
			$websiteCode = Mage::app()->getRequest()->getParam('website');
			$storeCode = Mage::app()->getRequest()->getParam('store');
			
			Mage::getSingleton('ultimo/cssgen_generator')->generateCss('design', $websiteCode, $storeCode);
		}
		elseif ($section == 'ultimo')
		{
			$websiteCode = Mage::app()->getRequest()->getParam('website');
			$storeCode = Mage::app()->getRequest()->getParam('store');
			
			Mage::getSingleton('ultimo/cssgen_generator')->generateCss('layout', $websiteCode, $storeCode);
		}
	}
	
	/**
	 * After store view is saved
	 */
	public function hookTo_storeEdit(Varien_Event_Observer $observer)
	{
		$store = $observer->getEvent()->getStore();
		if ($store->getIsActive())
		{
			$this->_onStoreChange($store);
		}
	}

	/**
	 * After store view is added
	 */
	public function hookTo_storeAdd(Varien_Event_Observer $observer)
	{
		$store = $observer->getEvent()->getStore();
		if ($store->getIsActive())
		{
			$this->_onStoreChange($store);
		}
	}

	/**
	 * On store view changed
	 */
	protected function _onStoreChange($store)
	{
		$storeCode = $store->getCode();
		$websiteCode = $store->getWebsite()->getCode();
		
		$cg = Mage::getSingleton('ultimo/cssgen_generator');
		$cg->generateCss('grid',   $websiteCode, $storeCode);
		$cg->generateCss('layout', $websiteCode, $storeCode);
		$cg->generateCss('design', $websiteCode, $storeCode);
	}

	/**
	 * After config import
	 */
	public function hookTo_DataporterCfgporterImportAfter(Varien_Event_Observer $observer)
	{
		$event = $observer->getEvent();
		$websiteCode 	= '';
		$storeCode 		= '';
		$scope = $event->getData('portScope');
		$scopeId = $event->getData('portScopeId');
		switch ($scope) {
			case 'websites':
				$websiteCode 	= Mage::app()->getWebsite($scopeId)->getCode();
				break;
			case 'stores':
				$storeCode 		= Mage::app()->getStore($scopeId)->getCode();
				$websiteCode 	= Mage::app()->getStore($scopeId)->getWebsite()->getCode();
				break;
		}
		
		Mage::app()->getConfig()->reinit();
		$cg = Mage::getSingleton('ultimo/cssgen_generator');
		$cg->generateCss('grid',   $websiteCode, $storeCode);
		$cg->generateCss('layout', $websiteCode, $storeCode);
		$cg->generateCss('design', $websiteCode, $storeCode);
	}
}
