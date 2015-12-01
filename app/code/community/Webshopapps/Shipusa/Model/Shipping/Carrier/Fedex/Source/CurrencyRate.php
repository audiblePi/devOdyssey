<?php

/*
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt
 * @author     Karen Baker <sales@webshopapps.com>
*/
class Webshopapps_Shipusa_Model_Shipping_Carrier_Fedex_Source_CurrencyRate
{
    public function toOptionArray()
    {
        $arr = $this->getCode('currency_rate');
        return $arr;
    }

    public function getCode($type, $code = '')
    {
        $codes = array(

            'currency_rate' => array(
                'PAYOR' => Mage::helper('shipping')->__("Payor's Rate Table"),
                'RATED' => Mage::helper('shipping')->__('Origin Country'),
            ),

        );

        if (!isset($codes[$type])) {
            throw Mage::exception('Mage_Shipping', Mage::helper('shipping')->__('Invalid Currency Rate: %s', $type));
        }

        if ('' === $code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            throw Mage::exception('Mage_Shipping', Mage::helper('shipping')->__('Invalid Currency Rate %s: %s', $type, $code));
        }

        return $codes[$type][$code];
    }
}
