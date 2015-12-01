<?php

/*
 * @category   Webshopapps
 * @package    Webshopapps_Boxmenu
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/
class Webshopapps_Boxmenu_Model_System_Config_Source_Flatbox
{

    public function getCode($type, $code='')
    {
        $codes = array(

            'usps_box'=>array(
                '4' => Mage::helper('shipping')->__('Custom Box'),
                '1' => Mage::helper('shipping')->__('Priority Mail Small Flat Rate Box'),
                '2' => Mage::helper('shipping')->__('Priority Mail Medium Flat Rate Box'),
                '3' => Mage::helper('shipping')->__('Priority Mail Large Flat Rate Box'),
                '5' => Mage::helper('shipping')->__('Priority Mail Padded Flat Rate Envelope'),
                '6' => Mage::helper('shipping')->__('Priority Mail Small Flat Rate Envelope'),
                '7' => Mage::helper('shipping')->__('Priority Mail Legal Flat Rate Envelope'),
                '8' => Mage::helper('shipping')->__('Priority Mail Flat Rate Envelope'),
            ),

            'usps_box_int'=>array(
                //'4' => Mage::helper('shipping')->__('Custom Box'),
                '1' => Mage::helper('shipping')->__('Priority Mail International Small Flat Rate Box'),
                '2' => Mage::helper('shipping')->__('Priority Mail International Medium Flat Rate Box'),
                '3' => Mage::helper('shipping')->__('Priority Mail International Large Flat Rate Box'),
                '5' => Mage::helper('shipping')->__('Priority Mail International Padded Flat Rate Envelope'),
                '6' => Mage::helper('shipping')->__('Priority Mail International Small Flat Rate Envelope'),
                '7' => Mage::helper('shipping')->__('Priority Mail International Legal Flat Rate Envelope'),
                '8' => Mage::helper('shipping')->__('Priority Mail International Flat Rate Envelope'),
            ),

            'usps_coded_box'=>array(
                '1' => Mage::helper('shipping')->__('SM FLAT RATE BOX'),
                '2' => Mage::helper('shipping')->__('MD FLAT RATE BOX'),
                '3' => Mage::helper('shipping')->__('LG FLAT RATE BOX'),
              //'4' => Mage::helper('shipping')->__('Custom Box'), Reserved
                '5' => Mage::helper('shipping')->__('PADDED FLAT RATE ENVELOPE'),
                '6' => Mage::helper('shipping')->__('SM FLAT RATE ENVELOPE'),
                '7' => Mage::helper('shipping')->__('LEGAL FLAT RATE ENVELOPE'),
                '8' => Mage::helper('shipping')->__('FLAT RATE ENVELOPE'),
            ),

            'usps_box_type'=>array(
                'SM FLAT RATE BOX' => 1,
                'MD FLAT RATE BOX' => 2,
                'LG FLAT RATE BOX' => 3,
                //'CUSTOM BOX' => 4, Reserved
                'PADDED FLAT RATE ENVELOPE' => 5,
                'SM FLAT RATE ENVELOPE' => 6,
                'LEGAL FLAT RATE ENVELOPE' => 7,
                'FLAT RATE ENVELOPE' => 8,
            ),
        );

        if (!isset($codes[$type])) {
            Mage::helper('wsalogger/log')->postCritical('boxmenu','Invalid Flat Box Code',$code);
        }

        if (''===$code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            Mage::helper('wsalogger/log')->postCritical('boxmenu','Invalid Flat Box Code',$code);
        }

        return $codes[$type][$code];
    }
}
