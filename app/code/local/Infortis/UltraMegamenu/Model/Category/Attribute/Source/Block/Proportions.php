<?php

class Infortis_UltraMegamenu_Model_Category_Attribute_Source_Block_Proportions
	extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{	
	/**
     * Get list of available block column proportions
     */
	public function getAllOptions()
	{
		if (!$this->_options)
		{
			$this->_options = array(
				array('value' => '',		'label' => ' '),
                array('value' => '5/1',		'label' => '5/1'),
				array('value' => '4/2',		'label' => '4/2'),
				array('value' => '3/3',		'label' => '3/3'),
				array('value' => '2/4',		'label' => '2/4'),
				array('value' => '1/5',		'label' => '1/5'),
				
				array('value' => '3/1',		'label' => '3/1'),
				array('value' => '2/2',		'label' => '2/2'),
				array('value' => '1/3',		'label' => '1/3'),
				
				array('value' => '2/1',		'label' => '2/1'),
				array('value' => '1/2',		'label' => '1/2'),
				
				array('value' => '1/1',		'label' => '1/1')
			);
        }
		return $this->_options;
    }
}
