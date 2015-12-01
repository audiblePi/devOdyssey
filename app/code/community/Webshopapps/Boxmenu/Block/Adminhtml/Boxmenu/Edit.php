<?php
/*
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/
class Webshopapps_Boxmenu_Block_Adminhtml_Boxmenu_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'boxmenu';
        $this->_controller = 'adminhtml_boxmenu';

        $this->_updateButton('save', 'label', Mage::helper('boxmenu')->__('Save Box'));
        $this->_updateButton('delete', 'label', Mage::helper('boxmenu')->__('Delete Box'));

        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('boxmenu_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'boxmenu_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'boxmenu_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('boxmenu_data') && Mage::registry('boxmenu_data')->getId() ) {
            return Mage::helper('boxmenu')->__("Edit Box Definition '%s'", $this->htmlEscape(Mage::registry('boxmenu_data')->getTitle()));
        } else {
            return Mage::helper('boxmenu')->__('Add Box Definition');
        }
    }
}