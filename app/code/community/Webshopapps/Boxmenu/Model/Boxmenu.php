<?php
/*
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/
class Webshopapps_Boxmenu_Model_Boxmenu extends Mage_Core_Model_Abstract
{

	static protected $_boxmenuGroups;

    public function _construct()
    {
        parent::_construct();

        $this->_init('boxmenu/boxmenu');
        $this->setIdFieldName('boxmenu_id');
    }


     /**
     * Retrieve option array excluding USPS flat boxes
     *
     * @return array
     */
    static public function getOptionArray()
    {
        $options = array();
        foreach(self::getBoxmenuGroups() as $boxmenuId=>$boxmenuGroup) {
            $boxType = $boxmenuGroup->getBoxType();
            if($boxType == 4 || $boxType == 0) { //include not defined to cater for upgrades where sql may not run.
                $options[$boxmenuId] = $boxmenuGroup['title'];
            }
        }
        return $options;
    }



	public function toOptionArray()
    {
        $arr = array();
        foreach(self::getBoxmenuGroups() as $boxmenuId=>$boxmenuGroup) {
        	$arr[] = array('value'=>$boxmenuId, 'label'=>$boxmenuGroup['title']);
        }
        return $arr;
    }

    static public function getBoxmenuGroups()
    {
        if (is_null(self::$_boxmenuGroups)) {
            self::$_boxmenuGroups = Mage::getModel('boxmenu/boxmenu')->getCollection();
        }

        return self::$_boxmenuGroups;
    }




    /**
     * Get the USPS boxes only
     *
     * @return array
     */
    static public function getAllUSPSOptions()
    {
        $options = array();

        foreach(self::getBoxmenuGroups() as $boxmenuId=>$boxmenuGroup) {
            $boxType = $boxmenuGroup->getBoxType();

            if ($boxType!=4 && $boxType!=0) {
                $options[$boxmenuId] = $boxmenuGroup['title'];
            }
        }

        return $options;
    }

    /**
     * Retrieve all standard options (not USPS)
     *
     * @internal param bool $uspsBoxes
     * @return   array
     * @bug      parameter always set to true for packing box attribute by Mage_Adminhtml_Block_Widget_Form::_setFieldset
     */
    static public function getAllOptions()
    {
        $res = array();
        $res[] = array('value'=>'', 'label'=> Mage::helper('catalog')->__('-- Custom --'));
        foreach (self::getOptionArray() as $index => $value) {
            $res[] = array(
                'value' => $index,
                'label' => $value
            );
        }
        return $res;
    }

    /**
     * Retrieve option text
     *
     * @param int $optionId
     * @return string
     */
    static public function getOptionText($optionId)
    {
        $optionString='';
        $next=false;
        $options = self::getOptionArray();
        $explodedOptionsId = explode(',',$optionId);
        foreach($explodedOptionsId as $indOption) {
            if ($next) {
                $optionString.=',';
            }
            $next=true;
            $optionString.= isset($options[$indOption]) ? $options[$indOption] : null;
        }
        return $optionString;
    }

    /**
     * Get Column(s) names for flat data building
     *
     * @return array
     */
    public function getFlatColums()
    {
        $columns = array();
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $isMulti = $this->getAttribute()->getFrontend()->getInputType() == 'multiselect';
        $compatible = false;

        if(Mage::helper('wsacommon')->getNewVersion() < 11){
            $compatible = true;
        } else if (method_exists(Mage::helper('core'), 'useDbCompatibleMode')) {
            if (Mage::helper('core')->useDbCompatibleMode())
            {
                $compatible = true;
            }
        }

        if ($compatible) {
            if($isMulti){
                $columns[$attributeCode] = array(
                    'type'      => 'varchar(255)',
                    'unsigned'  => false,
                    'is_null'   => true,
                    'default'   => null,
                    'extra'     => null
                );
            }
            else {
                $columns[$attributeCode] = array(
                    'type'      => 'int',
                    'unsigned'  => false,
                    'is_null'   => true,
                    'default'   => null,
                    'extra'     => null
                );
            }
        } else {
            //if(Mage::helper('wsacommon')->getNewVersion() >= 11) {
            $type = ($isMulti) ? Varien_Db_Ddl_Table::TYPE_TEXT : Varien_Db_Ddl_Table::TYPE_INTEGER;
            //} else {
            //        $type = ($isMulti) ? Varien_Db_Ddl_Table::TYPE_VARCHAR : Varien_Db_Ddl_Table::TYPE_INTEGER;
            //}
            if($isMulti){
                $columns[$attributeCode] = array(
                    'type'      => $type,
                    'length'    => '255',
                    'unsigned'  => false,
                    'nullable'  => true,
                    'default'   => null,
                    'extra'     => null,
                    'comment'   => $attributeCode . ' column'
                );
            }
            if (!$isMulti) {
                $columns[$attributeCode] = array(
                    'type'      => $type,
                    'length'    => null,
                    'unsigned'  => false,
                    'nullable'  => true,
                    'default'   => null,
                    'extra'     => null,
                    'comment'   => $attributeCode . ' column'
                );
            }
        }

        return $columns;
    }

    /**
     * Retrieve Select for update Attribute value in flat table
     *
     * @param   int $store
     * @return  Varien_Db_Select|null
     */
    public function getFlatUpdateSelect($store)
    {
        return Mage::getResourceModel('eav/entity_attribute_option')
            ->getFlatUpdateSelect($this->getAttribute(), $store, false);
    }

}