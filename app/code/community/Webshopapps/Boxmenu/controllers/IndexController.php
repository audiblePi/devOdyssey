<?php
class Webshopapps_Boxmenu_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {

    	/*
    	 * Load an object by id
    	 * Request looking like:
    	 * http://site.com/insurance?id=15
    	 *  or
    	 * http://site.com/insurance/id/15
    	 */
    	/*
		$boxmenu_id = $this->getRequest()->getParam('id');

  		if($boxmenu_id != null && $boxmenu_id != '')	{
			$boxmenu = Mage::getModel('destgroup/boxmenu')->load($boxmenu_id)->getData();
		} else {
			$boxmenu = null;
		}
		*/

		 /*
    	 * If no param we load a the last created item
    	 */
    	/*
    	if($boxmenu == null) {
			$resource = Mage::getSingleton('core/resource');
			$read= $resource->getConnection('core_read');
			$shipGroupTable = $resource->getTableName('boxmenu');

			$select = $read->select()
			   ->from($shipGroupTable,array('boxmenu_id','title','content','status'))
			   ->where('status',1)
			   ->order('created_time DESC') ;

			$boxmenu = $read->fetchRow($select);
		}
		Mage::register('boxmenu', $boxmenu);
		*/


		$this->loadLayout();
		$this->renderLayout();
    }
}