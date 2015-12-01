<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Usa
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/* UsaShipping
 *
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */

class Webshopapps_Shipusa_Model_Shipping_Carrier_Fedex
    extends Webshopapps_Shipusa_Model_Shipping_Carrier_Fedexbase
{
    protected $_numBoxes = 1;
    protected $_applyHandlingPackage = FALSE;


    /**
     * Collect and get rates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result|bool|null
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!Mage::getStoreConfig('shipping/shipusa/active')) {
            return Mage_Usa_Model_Shipping_Carrier_Fedex::collectRates($request);
        }
        $this->_applyHandlingPackage = Mage::getStoreConfigFlag('shipping/shipusa/handling_product_fee');

        $this->setRequest($request);

        $this->_result = $this->_getQuotes();

        $this->_updateFreeMethodQuote($request);

        return $this->getResult();
    }

    public function setRequest(Mage_Shipping_Model_Rate_Request $request)
    {

        if (!Mage::getStoreConfig('shipping/shipusa/active')) {
            return Mage_Usa_Model_Shipping_Carrier_Fedex::setRequest($request);
        }
        parent::setRequest($request);

        $r = $this->_rawRequest;

        if ($request->getUpsDestType()) {
            if ($request->getUpsDestType() == "RES") {
                $r->setDestType(1);
            } else {
                $r->setDestType(0);
            }
        } else {
            $r->setDestType($this->getConfigData('residence_delivery'));
        }

        /* WSA change */
        if ($request->getFedexSoapKey() != '') {
            $r->setKey($request->getFedexSoapKey());
        } else {
            $r->setKey($this->getConfigData('key'));
        }

        if ($request->getFedexPassword() != '') {
            $r->setPassword($request->getFedexPassword());
        } else {
            $r->setPassword($this->getConfigData('password'));
        }

        if ($request->getFedexMeterNumber() != '') {
            $r->setMeterNumber($request->getFedexMeterNumber());
        } else {
            $r->setMeterNumber($this->getConfigData('meter_number'));
        }

        $r->setMaxPackageWeight($this->getConfigData('max_package_weight'));

        $r->setUnitMeasure($this->getConfigData('unit_of_measure'));

        return $this;
    }


    protected function _formRateRequest($purpose)
    {
        if (!Mage::getStoreConfig('shipping/shipusa/active')) {
            return Mage_Usa_Model_Shipping_Carrier_Fedex::_formRateRequest($purpose);
        }
        $r = $this->_rawRequest;

        $date = date('c');

        if (!$this->getConfigData('saturday_pickup')) {
            if (date('w') == 6) {
                $date = date('c', time() + 172800); //adds 2 days if it's a Saturday.
                if ($this->getDebugFlag()) {
                    Mage::helper('wsalogger/log')->postInfo('usashipping', 'Date modified to ', $date);
                }
            } else if (date('w') == 0) {
                $date = date('c', time() + 86400); //adds 1 day if it's a Sunday.
                if ($this->getDebugFlag()) {
                    Mage::helper('wsalogger/log')->postInfo('usashipping', 'Date modified to ', $date);
                }
            }
        }

        if (!Mage::helper('wsacommon')->checkItems('c2hpcHBpbmcvc2hpcHVzYS9zaGlwX29uY2U=',
            'aWdsb29tZQ==', 'c2hpcHBpbmcvc2hpcHVzYS9zZXJpYWw=')
        ) {
            $message = base64_decode('U2VyaWFsIEtleSBJcyBOT1QgVmFsaWQgZm9yIFdlYlNob3BBcHBzIERpbWVuc2lvbmFsIFNoaXBwaW5n');
            Mage::helper('wsalogger/log')->postCritical('usashipping','Fatal Error',$message);
            Mage::log($message);

            return Mage::getModel('shipping/rate_result');
        }


        $fedReq['Version'] = $this->getVersionInfo();
        $displayTransitTime = $this->getConfigData('display_transit_time');
        if ($displayTransitTime) {
            $fedReq['ReturnTransitAndCommit'] = true;
        } else {
            $fedReq['ReturnTransitAndCommit'] = false;
        }

        $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        if (($r->getOrigCountry() == self::USA_COUNTRY_ID && $r->getDestCountry() == self::CANADA_COUNTRY_ID) ||
            ($r->getOrigCountry() == self::CANADA_COUNTRY_ID && $r->getDestCountry() == self::USA_COUNTRY_ID))
        {
            $fedReq['RequestedShipment']['CustomsClearanceDetail'] = array(
                'CustomsValue' => array('Amount'   => $r->getValue(),
                                        'Currency' => $currentCurrencyCode
                )
            );
        }

        $fedReq['RequestedShipment']['DropoffType'] = $r->getDropoffType(); // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
        $fedReq['RequestedShipment']['ShipTimestamp'] = $date;
        $fedReq['RequestedShipment']['ServiceType'] = $r->getService(); // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...

        $fedReq['RequestedShipment']['PackagingType'] = $r->getPackaging(); // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
        if ($this->getConfigFlag('monetary_value')) {
            $currencyCode = $r->getOrigCountry() == 'CA' ? 'CAD' : 'USD';
            $fedReq['RequestedShipment']['TotalInsuredValue'] = array('Amount' => $r->getValue(), 'Currency' => $currencyCode);
        }
        $fedReq['RequestedShipment']['Shipper'] = array(
            'Address' => array(
                //'StreetLines' => array('Address Line 1'),
                // 'City' => 'Los Angeles',
                //  'StateOrProvinceCode' => 'CA',
                'PostalCode' => $r->getOrigPostal(),
                'CountryCode' => $r->getOrigCountry(),
                'Residential' => $r->getDestType())
        );
        $fedReq['RequestedShipment']['Recipient'] = array(
            'Address' => array(
                //'StreetLines' => array('Address Line 1'),
                //  'City' => 'Richmond',
                //  'StateOrProvinceCode' => 'BC',
                'PostalCode' => $r->getDestPostal(),
                'CountryCode' => $r->getDestCountry(),
                'Residential' => $r->getDestType())
        );
        //	$fedReq['RequestedShipment']['ShippingChargesPayment'] = array('PaymentType' => 'SENDER',
        //	                                                        'Payor' => array('AccountNumber' => '510087704',
        //	                                                                     'CountryCode' => 'US'));


        $fedReq['RequestedShipment']['RateRequestTypes'] = $this->getConfigData('request_type');
        $fedReq['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES'; //  Or PACKAGE_SUMMARY

        $boxes = Mage::helper('shipusa')->getStdBoxes($this->_request->getAllItems(),
            $r->getIgnoreFreeItems());

        if (is_null($boxes)) {
            return Mage::getModel('shipping/rate_result');
        }

        //$fedReq['RequestedShipment']['PackageCount'] = count($boxes); Can't be caluclated here. May be modified below by split shipments.
        $this->_numBoxes = count($boxes);

        $splitIndPackage = $this->getConfigData('break_multiples');
        $splitMaxWeight = $this->getConfigData('max_multiple_weight');
        $maxPackageWeight = $r->getMaxPackageWeight();
        $unitMeasure = $r->getUnitMeasure();

        // here for historic reasons & compatibility with older magento versions
        if ($unitMeasure!='KG') {
            $unitMeasure = 'LB';
        }

        $handProdFee = 0;
        foreach ($boxes as $box) {
            $billableWeight = $box['weight'];
            $dimDetails = array();
            $dimDetails['GroupPackageCount'] = 1;
            if ($purpose == self::RATE_REQUEST_GENERAL && $this->getConfigFlag('monetary_value')) {
                $dimDetails['InsuredValue'] = array(
                    'Currency' => $currencyCode,
                    'Amount' => $box['price'],
                );
            }
            if ($splitIndPackage && is_numeric($splitMaxWeight) && $splitMaxWeight > $maxPackageWeight &&
                $billableWeight < $splitMaxWeight
            ) {
                for ($remainingWeight = $billableWeight; $remainingWeight > 0;) {
                    if ($remainingWeight - $maxPackageWeight < 0) {
                        $billableWeight = $remainingWeight;
                        $remainingWeight = 0;
                    } else {
                        $billableWeight = $maxPackageWeight;
                        $remainingWeight -= $maxPackageWeight;
                    }

                    if ($this->getDebugFlag()) {
                        Mage::helper('wsalogger/log')->postInfo('usashipping',
                            'Adjusting Box Weight for FedEx Original Weight/New Weight',
                            $box['weight'] . ' / ' . $billableWeight);
                    }
                    $dimDetails['Weight'] = array(
                        'Value' => $billableWeight,
                        'Units' => $unitMeasure);
                    if ($box['length'] != 0 && $box['width'] != 0 && $box['height'] != 0) {
                        $dimDetails['Dimensions'] = array(
                            'Length' => $box['length'],
                            'Width' => $box['width'],
                            'Height' => $box['height'],
                            'Units' => 'IN');
                    }
                    $fedReq['RequestedShipment']['RequestedPackageLineItems'][] = $dimDetails;

                    if (!$this->_applyHandlingPackage && $box['handling_fee'] > $handProdFee) {
                        $handProdFee = $box['handling_fee'];
                    } else if ($this->_applyHandlingPackage) {
                        $handProdFee += $box['handling_fee'];
                    }
                }
            } else {
                $dimDetails['Weight'] = array(
                    'Value' => $box['weight'],
                    'Units' => $unitMeasure);
                if ($box['length'] != 0 && $box['width'] != 0 && $box['height'] != 0) {
                    $dimDetails['Dimensions'] = array(
                        'Length' => $box['length'],
                        'Width' => $box['width'],
                        'Height' => $box['height'],
                        'Units' => 'IN');
                }
                $fedReq['RequestedShipment']['RequestedPackageLineItems'][] = $dimDetails;

                if (!$this->_applyHandlingPackage && $box['handling_fee'] > $handProdFee) {
                    $handProdFee = $box['handling_fee'];
                } else if ($this->_applyHandlingPackage) {
                    $handProdFee += $box['handling_fee'];
                }
            }
        }

        if ($purpose == self::RATE_REQUEST_SMARTPOST) {
            $fedReq['RequestedShipment']['ServiceType'] = self::RATE_REQUEST_SMARTPOST;
            $fedReq['RequestedShipment']['SmartPostDetail'] = array(
                'Indicia' => ((float)$r->getWeight() >= 1) ? 'PARCEL_SELECT' : 'PRESORTED_STANDARD',
                'HubId' => $this->getConfigData('smartpost_hubid')
            );
        }

        if (array_key_exists('RequestedPackageLineItems',$fedReq['RequestedShipment'])) {
            $fedReq['RequestedShipment']['PackageCount'] = count($fedReq['RequestedShipment']['RequestedPackageLineItems']);
        }

        $this->getDebugFlag() ? Mage::helper('wsalogger/log')->postInfo('usashipping',
            'Overall HandlingProduct Fee Applied', $handProdFee) : false;


        $fedReq['WebAuthenticationDetail'] = array('UserCredential' =>
        array('Key' => $r->getKey(), 'Password' => $r->getPassword()));
        $fedReq['ClientDetail'] = array('AccountNumber' => $r->getAccount(), 'MeterNumber' => $r->getMeterNumber());

        $this->_handlingProductFee = $handProdFee;

        return $fedReq;

    }

    /**
     * Process the response from FedEx
     * @param $response
     * @return false|Mage_Core_Model_Abstract
     */
    protected function _parseDimXmlResponse($response)
    {
        $displayTransitTime = $this->getConfigData('display_transit_time');
        $rateTypePost = $this->getConfigData('request_type');
        $rateTypePre = $this->getConfigData('currency_rate');
        // Note I've seen with RATED_LIST_SHIPMENT it still returns in USD for GBP origin
        $matchRateType = array($rateTypePre.'_'.$rateTypePost);
        $this->getDebugFlag() ? Mage::helper('wsalogger/log')->postDebug('usashipping','Rate Type',$matchRateType) :
                false;
        $costArr = array();
        $priceArr = array();
        $timeArr = array();
        $origArr = array();

        // Order is important
        $ratesOrder = array(
            'RATED_'.$rateTypePost.'_PACKAGE',
            'PAYOR_'.$rateTypePost.'_PACKAGE',
            'RATED_'.$rateTypePost.'_SHIPMENT',
            'PAYOR_'.$rateTypePost.'_SHIPMENT',
        );


        if (is_object($response)) {
            if ($response->HighestSeverity == 'FAILURE' || $response->HighestSeverity == 'ERROR') {
                if (is_array($response->Notifications)) {
                    $notification = array_pop($response->Notifications);
                    $errorTitle = (string)$notification->Message;
                } else {
                    $errorTitle = (string)$response->Notifications->Message;
                }
            } elseif (isset($response->RateReplyDetails)) {
                /* WSA change */

                if (Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Dropcommon','carriers/dropship/active') ||
                    Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Dropship','carriers/dropship/active') ) {
                    $allowedMethods = $this->_request->getFedexsoapAllowedMethods();

                    if ($allowedMethods == null) {
                        $allowedMethods = explode(",", $this->getConfigData('allowed_methods'));
                    }
                } else {
                    $allowedMethods = explode(",", $this->getConfigData('allowed_methods'));
                }

                if (is_array($response->RateReplyDetails)) {
                    foreach ($response->RateReplyDetails as $rate) {
                        $serviceName = (string)$rate->ServiceType;
                        if (in_array($serviceName, $allowedMethods)) {
                            $this->_getRateBasePackage($rate, $serviceName, $displayTransitTime,
                                $rate, $ratesOrder,$costArr, $priceArr, $origArr, $timeArr);
                        }
                    }
                    asort($priceArr);
                } else { //SMART POST
                    $rate = $response->RateReplyDetails;
                    $serviceName = (string)$rate->ServiceType;
                    if (in_array($serviceName, $allowedMethods)) {
                        $this->_getRateBasePackage($rate, $serviceName, $displayTransitTime,
                            $rate, $ratesOrder, $costArr, $priceArr, $origArr, $timeArr);
                    }
                }
                asort($priceArr);
            }
        }

        $this->getDebugFlag() ? Mage::helper('wsalogger/log')->postDebug('usashipping','Original Prices from FedEx',
            $origArr) : false;


        $result = Mage::getModel('shipping/rate_result');
        if (empty($priceArr)) {
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            //$error->setErrorMessage($errorTitle);
            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
            $result->append($error);
            Mage::helper('wsalogger/log')->postInfo('usashipping', 'Fedex No rates found', '');
        } else {
            foreach ($priceArr as $method => $price) {
                $rate = Mage::getModel('shipping/rate_result_method');
                $rate->setCarrier($this->_code);
                $rate->setCarrierTitle($this->getConfigData('title'));
                $rate->setMethod($method);
                if ($this->getConfigFlag('home_ground') && $this->getCode('method', $method) == 'Home Delivery') {
                    $rate->setMethodTitle('Ground');
                    $rate->setMethod('FEDEX_GROUND');
                } else {
                    $rate->setMethodTitle($this->getCode('method', $method));
                }
                if ($displayTransitTime && $timeArr[$method] != '') {
                    $now = strtotime(date("Y-m-d"));
                    $deliveryDate = substr($timeArr[$method], 0, 10);
                    $end = strtotime($deliveryDate);
                    $days = $end - $now;
                    $days = ceil($days / 86400);

                    if (strpos($deliveryDate, 'DAY') != false) {
                        $days = $this->daysSwitch($deliveryDate);
                    }

                    $transitDays = $this->getCode('method', $method) . ' ( ';
                    $transitDays .= $days;
                    if ($days == 1) {
                        $transitDays .= ' ' . "Day )";
                    } else {
                        $transitDays .= ' ' . "Days )";
                    }
                    $rate->setMethodTitle($transitDays);
                    $rate->setShip($timeArr[$method]);
                }
                $rate->setCost($costArr[$method]);
                $rate->setPrice($price + $this->_handlingProductFee);
                $result->append($rate);
            }
        }

        return $result;
    }


    protected function daysSwitch($deliveryDate)
    {

        switch ($deliveryDate) {

            case "ONE_DAY":
                $day = 1;
                break;
            case "TWO_DAYS":
                $day = 2;
                break;
            case "THREE_DAYS":
                $day = 3;
                break;
            case "FOUR_DAYS":
                $day = 4;
                break;
            case "FIVE_DAYS":
                $day = 5;
                break;
            case "SIX_DAYS":
                $day = 6;
                break;
            case "SEVEN_DAYS":
                $day = 7;
                break;
        }
        return $day;
    }


    /**
     * Get origin based amount form response of rate estimation
     *
     * @param stdClass $rate
     * @param          $serviceName
     * @param          $displayTransitTime
     * @param          $entry
     * @param          $ratesOrder
     * @param          $costArr
     * @param          $priceArr
     * @param          $origArr
     * @param          $timeArr
     * @return null|float
     */
    protected function _getRateBasePackage($rate, $serviceName, $displayTransitTime, $entry,
                                           $ratesOrder, &$costArr, &$priceArr, &$origArr, &$timeArr)
    {
        $amount = null;
        $rateTypeAmounts = array();

        if (is_object($rate)) {
            // The "RATED..." rates are expressed in the currency of the origin country
            foreach ($rate->RatedShipmentDetails as $ratedShipmentDetail) {
                if(is_array($ratedShipmentDetail)) {
                    foreach ($ratedShipmentDetail as $innerRatedDetail) {
                        if(property_exists($innerRatedDetail, 'TotalNetCharge')) {//DIMSHIP-117
                            $currencyCode = (string)$innerRatedDetail->TotalNetCharge->Currency;
                            $netAmount = (string)$innerRatedDetail->TotalNetCharge->Amount;
                            $rateType = (string)$innerRatedDetail->RateType;
                            $rateTypeAmounts[$rateType] = $netAmount;
                        } elseif(property_exists($innerRatedDetail, 'ShipmentRateDetail')) {//DIMSHIP-117
                            $currencyCode = (string)$innerRatedDetail->ShipmentRateDetail->TotalNetCharge->Currency;
                            $netAmount = (string)$innerRatedDetail->ShipmentRateDetail->TotalNetCharge->Amount;
                            $rateType = (string)$innerRatedDetail->ShipmentRateDetail->RateType;
                            $rateTypeAmounts[$rateType] = $netAmount;
                        }
                    }
                } else {
                    if(property_exists($ratedShipmentDetail, 'TotalNetCharge')) {//DIMSHIP-117
                        $currencyCode = (string)$ratedShipmentDetail->TotalNetCharge->Currency;
                        $netAmount = (string)$ratedShipmentDetail->TotalNetCharge->Amount;
                        $rateType = (string)$ratedShipmentDetail->RateType;
                        $rateTypeAmounts[$rateType] = $netAmount;
                    } elseif(property_exists($ratedShipmentDetail, 'ShipmentRateDetail')) {//DIMSHIP-117
                        $currencyCode = (string)$ratedShipmentDetail->ShipmentRateDetail->TotalNetCharge->Currency;
                        $netAmount = (string)$ratedShipmentDetail->ShipmentRateDetail->TotalNetCharge->Amount;
                        $rateType = (string)$ratedShipmentDetail->ShipmentRateDetail->RateType;
                        $rateTypeAmounts[$rateType] = $netAmount;
                    }
                }
            }

            foreach ($ratesOrder as $rateType) {
                if (!empty($rateTypeAmounts[$rateType])) {
                    $amount = $rateTypeAmounts[$rateType];
                    break;
                }
            }

            if (is_null($amount)) {
                foreach ($rate->RatedShipmentDetails as $ratedShipmentDetail) {
                    if(property_exists($ratedShipmentDetail, 'TotalNetCharge')) {//DIMSHIP-117
                        $currencyCode = (string)$ratedShipmentDetail->TotalNetCharge->Currency;
                        $amount = (string)$ratedShipmentDetail->TotalNetCharge->Amount;
                    } elseif(property_exists($ratedShipmentDetail, 'ShipmentRateDetail')) {//DIMSHIP-117
                        $currencyCode = (string)$ratedShipmentDetail->ShipmentRateDetail->TotalNetCharge->Currency;
                        $amount = (string)$ratedShipmentDetail->ShipmentRateDetail->TotalNetCharge->Amount;
                    }
                    break;
                }
            }
            $this->_convertRateCurrency($amount, $serviceName, $displayTransitTime,
                                    $entry, $currencyCode, $costArr, $priceArr, $origArr, $timeArr) ;
        }


    }

    protected function _convertRateCurrency($totalNetCharge, $serviceName, $displayTransitTime, $entry,
                                            $currencyCode, &$costArr, &$priceArr, &$origArr, &$timeArr) {

        $cost = $totalNetCharge;
        //convert price with Origin country currency code to base currency code
            $responseCurrencyCode = $currencyCode == 'UKL' ? 'GBP' : $currencyCode;
        if ($responseCurrencyCode) {
            $allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
            if (in_array($responseCurrencyCode, $allowedCurrencies)) {
                $cost = (float) $cost * $this->_getBaseCurrencyRate($responseCurrencyCode);
            } else {
                $this->getDebugFlag() ? Mage::helper('wsalogger/log')->postDebug('usashipping',
                    'Can\'t convert rate from '.$responseCurrencyCode,
                    $this->_request->getPackageCurrency()->getCode()) : false;

                // just use normal rates - lets not fail here as currency codes in Fedex dont always match Magento
            }
        }

        $costArr[$serviceName] = $cost;
        $priceArr[$serviceName] = $this->getMethodPrice(floatval($cost),$serviceName);
        $origArr[$serviceName] = $totalNetCharge;

        if ($displayTransitTime && isset($entry->TransitTime)) {
            $timeArr[$serviceName] = $entry->TransitTime;
        }
        if ($displayTransitTime && isset($entry->DeliveryTimestamp)) {
            $timeArr[$serviceName] = $entry->DeliveryTimestamp;
        }
    }

    /**
     * Get base currency rate -- Taken from Magento UPS.php
     *
     * @param string $code
     * @return double
     */
    protected function _getBaseCurrencyRate($code)
    {
        if (!$this->_baseCurrencyRate) {
            $this->_baseCurrencyRate = Mage::getModel('directory/currency')
                ->load($code)
                ->getAnyRate($this->_request->getBaseCurrency()->getCode());
        }

        return $this->_baseCurrencyRate;
    }

    /**
     * WebShopApps - Changed to support Free Shipping using either Ground or Home Delivery
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return null
     */
    protected function _updateFreeMethodQuote($request)
    {
        if ($request->getFreeMethodWeight() == $request->getPackageWeight() || !$request->hasFreeMethodWeight()) {
            return;
        }

        $freeCode = Mage::helper('wsacommon')->getNewVersion() < 12 ? 'free_method' : $this->_freeMethod;

        $freeMethod = $this->getConfigData($freeCode);
        if (!$freeMethod) {
            return;
        }
        $freeRateId = false;

        $matchFedexGround = false;
        if ($this->getConfigData('free_both_ground')) {
            if ($freeMethod == 'GROUND_HOME_DELIVERY') {
                $altMethod = 'FEDEX_GROUND';
                $matchFedexGround = true;
            } else if ($freeMethod == 'FEDEX_GROUND') {
                // could match on either, need to check
                $altMethod = 'GROUND_HOME_DELIVERY';
                $matchFedexGround = true;
            }
        }
        if (is_object($this->_result)) {
            foreach ($this->_result->getAllRates() as $i => $item) {
                if ($item->getMethod() == $freeMethod ||
                    ($matchFedexGround && $item->getMethod() == $altMethod)
                ) {
                    $freeRateId = $i;
                    break;
                }
            }
        }

        if ($freeRateId === false) {
            return;
        }
        $price = null;
        if ($request->getFreeMethodWeight() > 0) {
            $this->_setFreeMethodRequest($freeMethod);

            $result = $this->_getQuotes();
            if ($result && ($rates = $result->getAllRates()) && count($rates) > 0) {
                if ((count($rates) == 1) && ($rates[0] instanceof Mage_Shipping_Model_Rate_Result_Method)) {
                    $price = $rates[0]->getPrice();
                }
                if (count($rates) > 1) {
                    foreach ($rates as $rate) {
                        if ($rate instanceof Mage_Shipping_Model_Rate_Result_Method
                            && ($rate->getMethod() == $freeMethod ||
                                ($matchFedexGround && $item->getMethod() == $altMethod))
                        ) {
                            $price = $rate->getPrice();
                        }
                    }
                }
            }
        } else {
            /**
             * if we can apply free shipping for all order we should force price
             * to $0.00 for shipping with out sending second request to carrier
             */
            $price = 0;
        }

        /**
         * if we did not get our free shipping method in response we must use its old price
         */
        if (!is_null($price)) {
            $this->_result->getRateById($freeRateId)->setPrice($price);
        }
    }


    /*****************************************************************
     * COMMON CODE - If change here change in UPS,USPS
     */


    protected function _setFreeMethodRequest($freeMethod)
    {
        if (!Mage::getStoreConfig('shipping/shipusa/active')) {
            return Mage_Usa_Model_Shipping_Carrier_Fedex::_setFreeMethodRequest($freeMethod);
        }
        parent::_setFreeMethodRequest($freeMethod);
        $this->_rawRequest->setIgnoreFreeItems(true);


    }

    public function getTotalNumOfBoxes($weight)
    {
        if (!Mage::getStoreConfig('shipping/shipusa/active')) {
            return Mage_Usa_Model_Shipping_Carrier_Fedex::getTotalNumOfBoxes($weight);
        }
        $this->_numBoxes = 1; // now set up with dimensional weights
        $weight = $this->convertWeightToLbs($weight);
        return $weight;
    }

    public function getFinalPriceWithHandlingFee($cost)
    {

        if (!Mage::getStoreConfig('shipping/shipusa/active')) {
            return parent::getFinalPriceWithHandlingFee($cost);
        }
        $handlingFee = $this->getConfigData('handling_fee');
        if (!is_numeric($handlingFee) || $handlingFee <= 0) {
            return $cost;
        }

        $finalMethodPrice = 0;
        $handlingType = $this->getConfigData('handling_type');
        if (!$handlingType) {
            $handlingType = self::HANDLING_TYPE_FIXED;
        }
        $handlingAction = $this->getConfigData('handling_action');
        if (!$handlingAction) {
            $handlingAction = self::HANDLING_ACTION_PERORDER;
        }

        if ($handlingAction == self::HANDLING_ACTION_PERPACKAGE) {
            if ($handlingType == self::HANDLING_TYPE_PERCENT) {
                $finalMethodPrice = $cost + ($cost * $handlingFee / 100);
            } else {
                $finalMethodPrice = $cost + ($handlingFee * $this->_numBoxes);
            }
        } else {
            if ($handlingType == self::HANDLING_TYPE_PERCENT) {
                $finalMethodPrice = $cost + ($cost * $handlingFee / 100);
            } else {
                $finalMethodPrice = $cost + $handlingFee;
            }

        }
        $finalMethodPrice = ceil($finalMethodPrice*100) / 100;
        if ($this->getDebugFlag()) {
            Mage::helper('wsalogger/log')->postInfo('usashipping', 'Inbuilt Fedex Handling Fee', $finalMethodPrice - $cost);
        }
        return $finalMethodPrice;
    }


    /**
     * Processing additional validation to check is carrier applicable.
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Carrier_Abstract|Mage_Shipping_Model_Rate_Result_Error|boolean
     */
    public function proccessAdditionalValidation(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!Mage::getStoreConfig('shipping/shipusa/active')) {
            return Mage_Usa_Model_Shipping_Carrier_Fedex::proccessAdditionalValidation($request);
        }
        //Skip by item validation if there is no items in request
        if (!count($request->getAllItems())) {
            return $this;
        }

        //  $maxAllowedWeight = (float) $this->getConfigData('max_package_weight');
        $errorMsg = '';
        $configErrorMsg = $this->getConfigData('specificerrmsg');
        $defaultErrorMsg = Mage::helper('shipping')->__('The shipping module is not available.');
        $showMethod = $this->getConfigData('showmethod');

        /*  foreach ($request->getAllItems() as $item) {
              if ($item->getProduct() && $item->getProduct()->getId()) {
                  if ($item->getProduct()->getWeight() > $maxAllowedWeight) {
                      $errorMsg = ($configErrorMsg) ? $configErrorMsg : $defaultErrorMsg;
                      break;
                  }
              }
          } */

        if (!$errorMsg && !$request->getDestPostcode() && $this->isZipCodeRequired($request->getDestCountryId())) {
            $errorMsg = Mage::helper('shipping')->__('This shipping method is not available, please specify ZIP-code');
        }

        if ($errorMsg && $showMethod) {
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($errorMsg);
            return $error;
        } elseif ($errorMsg) {
            return false;
        }
        return $this;
    }


}

