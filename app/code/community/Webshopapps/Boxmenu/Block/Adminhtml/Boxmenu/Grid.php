<?php
/*
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/
class Webshopapps_Boxmenu_Block_Adminhtml_Boxmenu_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('boxmenuGrid');
      $this->setDefaultSort('boxmenu_id');
      $this->setDefaultDir('DESC');
      $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      $collection = Mage::getModel('boxmenu/boxmenu')->getCollection();
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
      $this->addColumn('boxmenu_id', array(
          'header'    => Mage::helper('boxmenu')->__('ID'),
          'align'     => 'right',
          'width'     => '50px',
          'index'     => 'boxmenu_id',
      ));

      $this->addColumn('box_type', array(
          'header'    => Mage::helper('boxmenu')->__('Box Type'),
          'align'     => 'left',
          'index'     => 'box_type',
          'type'      => 'options',
          'options'   => Mage::getModel('boxmenu/system_config_source_flatbox')->getCode('usps_box'),
      ));

      $this->addColumn('title', array(
          'header'    => Mage::helper('boxmenu')->__('Title'),
          'align'     => 'left',
          'index'     => 'title',
      ));

      $this->addColumn('length', array(
          'header'    => Mage::helper('boxmenu')->__('Length'),
          'align'     => 'left',
          'index'     => 'length',
      ));


      $this->addColumn('width', array(
          'header'    => Mage::helper('boxmenu')->__('Width'),
          'align'     => 'left',
          'index'     => 'width',
      ));


      $this->addColumn('height', array(
          'header'    => Mage::helper('boxmenu')->__('Height'),
          'align'     => 'left',
          'index'     => 'height',
      ));

      $this->addColumn('multiplier', array(
          'header'    => Mage::helper('boxmenu')->__('Qty Per Box'),
          'align'     => 'left',
          'index'     => 'multiplier',
      ));

      $this->addColumn('max_weight', array(
          'header'    => Mage::helper('boxmenu')->__('Max Weight Per Box'),
          'align'     => 'left',
          'index'     => 'max_weight',
      ));

        $this->addColumn('packing_weight', array(
          'header'    => Mage::helper('boxmenu')->__('Packing Weight Per Box'),
          'align'     => 'left',
          'index'     => 'packing_weight',
      ));



        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('boxmenu')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('boxmenu')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));

		//$this->addExportType('*/*/exportCsv', Mage::helper('boxmenu')->__('CSV'));
		//$this->addExportType('*/*/exportXml', Mage::helper('boxmenu')->__('XML'));

      return parent::_prepareColumns();
  }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('boxmenu_id');
        $this->getMassactionBlock()->setFormFieldName('boxmenu');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('boxmenu')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('boxmenu')->__('Are you sure?')
        ));

        $statuses = Mage::getSingleton('boxmenu/status')->getOptionArray();

        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('boxmenu')->__('Change status'),
             'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
             'additional' => array(
                    'visibility' => array(
                         'name' => 'status',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => Mage::helper('boxmenu')->__('Status'),
                         'values' => $statuses
                     )
             )
        ));
        return $this;
    }

  public function getRowUrl($row)
  {
      return $this->getUrl('*/*/edit', array('id' => $row->getId()));
  }

}