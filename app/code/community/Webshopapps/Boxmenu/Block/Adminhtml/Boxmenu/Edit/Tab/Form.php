<?php
/*
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/
class Webshopapps_Boxmenu_Block_Adminhtml_Boxmenu_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('boxmenu_form', array('legend'=>Mage::helper('boxmenu')->__('WebShopApps Box Definition')));

      $fieldset->addField('box_type', 'select', array(
          'label'     => Mage::helper('boxmenu')->__('Box Type'),
          'class'     => 'required-entry',
          'required'  => true,
          'values'    => Mage::getModel('boxmenu/system_config_source_flatbox')->getCode('usps_box'),
          'name'      => 'box_type',
          'note'	  => Mage::helper('boxmenu')->__('Set to custom unless using USPS Flat Rate Boxes'),
      ));

      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('boxmenu')->__('Box Name'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));

      $fieldset->addField('length', 'text', array(
          'label'     => Mage::helper('boxmenu')->__('Box Length'),
          'required'  => true,
          'name'      => 'length',
      	  'note'	  => Mage::helper('boxmenu')->__('Use 0 for no dimensions'),
      ));


      $fieldset->addField('width', 'text', array(
          'label'     => Mage::helper('boxmenu')->__('Box Width'),
          'required'  => true,
          'name'      => 'width',
      	  'note'	  => Mage::helper('boxmenu')->__('Use 0 for no dimensions'),
      ));


      $fieldset->addField('height', 'text', array(
          'label'     => Mage::helper('boxmenu')->__('Box Height'),
          'required'  => true,
          'name'      => 'height',
      	  'note'	  => Mage::helper('boxmenu')->__('Use 0 for no dimensions'),
      ));

      $fieldset->addField('multiplier', 'text', array(
          'label'     => Mage::helper('boxmenu')->__('Maximum Quantity per Box'),
          'required'  => false,
          'name'      => 'multiplier',
      	  'note'	  => Mage::helper('boxmenu')->__('Use -1 (minus 1) for no maximum'),
      ));

      $fieldset->addField('max_weight', 'text', array(
          'label'     => Mage::helper('boxmenu')->__('Maximum Weight per Box'),
          'required'  => false,
          'name'      => 'max_weight',
      	  'note'	  => Mage::helper('boxmenu')->__('Use -1 (minus 1) for no maximum'),
      ));

      $fieldset->addField('packing_weight', 'text', array(
          'label'     => Mage::helper('boxmenu')->__('Packing Weight'),
          'required'  => false,
          'name'      => 'packing_weight',
      	  'note'	  => Mage::helper('boxmenu')->__('Use 0 for no additional packing weight'),
      ));

      if ( Mage::getSingleton('adminhtml/session')->getBoxmenuData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getBoxmenuData());
          Mage::getSingleton('adminhtml/session')->setBoxmenuData(null);
      } elseif ( Mage::registry('boxmenu_data') ) {
          $form->setValues(Mage::registry('boxmenu_data')->getData());
      }
      return parent::_prepareForm();
  }

}