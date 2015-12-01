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
 * @category   Mage
 * @package    Mage_Usa
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * UPS shipping rates estimation
 *
 * @category   Mage
 * @package    Mage_Usa
 * @author      Magento Core Team <core@magentocommerce.com>
 */
/* UsaShipping
 *
 * @category   Webshopapps
 * @package    Webshopapps_UsaShipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */

class Webshopapps_Shipusa_Model_Shipping_Carrier_Ups
    extends Mage_Usa_Model_Shipping_Carrier_Ups
    implements Mage_Shipping_Model_Carrier_Interface
{

    protected $_numBoxes = 1;

    protected static $_upsCalendarDebug = null;

    /**
     * Ups Date Shipping Variables
     */
    protected $_earliestDispatchDate = null;
    protected $_earliestDeliveryDate = null;
    protected $_saturdayCodes = array(01, 14);



    /**
     * @var Webshopapps_Upscalendar_Model_Usa_Shipping_Upstransit|Webshopapps_UPSDateShipping_Model_Usa_Shipping_Carrier_Ups|null
     */
    protected $_upsTransitModel = null;


    /**
     * This is only used if UPS Calendar or UPS DateShipHelper are installed
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return bool|Mage_Shipping_Model_Rate_Result|mixed|null
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {

        if (!Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_DateShipHelper',
                                                        'shipping/webshopapps_dateshiphelper/active')) {
            return parent::collectRates($request);
        }

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        if(!Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_UPSDateShipping', 'shipping/webshopapps_dateshiphelper/active')) {
            if (!Mage::helper('calendarbase')->useUPSRates()) {
                return parent::collectRates($request);
            }
        }

        self::$_upsCalendarDebug = Mage::helper('wsalogger')->isDebug('Webshopapps_DateShipHelper');

        $this->setRequest($request);
        $this->setXMLAccessRequest();

        if(!Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_UPSDateShipping', 'shipping/webshopapps_dateshiphelper/active')) {
            $this->_upsTransitModel = Mage::getModel('upscalendar/usa_shipping_upstransit');
            $this->_upsTransitModel->_populateTimeInTransitValues($request, $this->_rawRequest,
                                                                  $this->_xmlAccessRequest,
                                                                  self::$_upsCalendarDebug);
        }

        $dateFormat 				= Mage::helper('webshopapps_dateshiphelper')->getDateFormat();
        $dayCount                   = 0;
        $dispatchDate               = '';
        $this->setRequest($request);
        $this->setXMLAccessRequest();

        Mage::helper('webshopapps_dateshiphelper')->getEarliestDispatchDay($dayCount,$dispatchDate,0,-1,'Ymd');

        if (self::$_upsCalendarDebug) {
            Mage::helper('wsalogger/log')->postInfo('webshopapps_upsdateshipping','Earliest Dispatch Date',$dispatchDate);
        }

        $this->_upsTransitModel = Mage::getSingleton('webshopapps_upsdateshipping/usa_shipping_transit');
        $this->_upsTransitModel->getTimeInTransitArr($this->_rawRequest,
                                                     $this->_xmlAccessRequest,
                                                     $dispatchDate);

        $this->_earliestDispatchDate = date($dateFormat,strtotime($dispatchDate));

        $this->_result = $this->_getQuotes();
        $this->_updateFreeMethodQuote($request);

        return $this->getResult();
    }

    public function setRequest(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!Mage::getStoreConfig('shipping/shipusa/active')) {
            return parent::setRequest($request);
        }

        parent::setRequest($request);

        $this->_setShipUsaRequest($request);

        return $this;

    }

    protected function _setShipUsaRequest($request)
    {
        $r = $this->_rawRequest;
        $r->setIgnoreFreeItems(false);

        $maxWeight = $request->getMaxPackageWeight();
        if (empty($maxWeight)) {
            $maxWeight = $this->getConfigData('max_package_weight');
        }

        $r->setMaxPackageWeight($maxWeight);

        return $this;
    }


    protected function _getXmlQuotes()
    {
        if (!Mage::getStoreConfig('shipping/shipusa/active')) {
            return parent::_getXmlQuotes();
        }

        $debugData = '';
        $handProdFee = 0;
        $saturdayRates = false;

        $rates = $this->doRatesRequest(false, $debugData, $handProdFee);

        if(is_object($this->_upsTransitModel) && $this->_upsTransitModel->isSaturday()) {
            $saturdayRates = $this->doRatesRequest(true, $debugData, $handProdFee);
        }

        return $this->_parseDimResponse($debugData, $rates, $handProdFee, $saturdayRates);
    }

    protected function doRatesRequest($includeSaturday, &$debugData, &$handProdFee)
    {
        $url = $this->getConfigData('gateway_xml_url');

        $this->setXMLAccessRequest();
        $xmlRequest = $this->_xmlAccessRequest;

        $r = $this->_rawRequest;

        $params = array(
            'accept_UPS_license_agreement' => 'yes',
            '10_action' => $r->getAction(),
            '13_product' => $r->getProduct(),
            '14_origCountry' => $r->getOrigCountry(),
            '15_origPostal' => $r->getOrigPostal(),
            'origCity' => $r->getOrigCity(),
            'origRegionCode' => $r->getOrigRegionCode(),
            '19_destPostal' => Mage_Usa_Model_Shipping_Carrier_Abstract::USA_COUNTRY_ID == $r->getDestCountry() ?
                    substr($r->getDestPostal(), 0, 5) :
                    $r->getDestPostal(),
            '22_destCountry' => $r->getDestCountry(),
            'destRegionCode' => $r->getDestRegionCode(),
            '23_weight' => $r->getWeight(),
            '25_length' => $r->getLength(),
            '26_width' => $r->getWidth(),
            '27_height' => $r->getHeight(),
            '47_rate_chart' => $r->getPickup(),
            '48_container' => $r->getContainer(),
            '49_residential' => $r->getDestType(),
        );
        if ($params['10_action'] == '4') {
            $params['10_action'] = 'Shop';
            $serviceCode = null; // Service code is not relevant when we're asking ALL possible services' rates
        } else {
            $params['10_action'] = 'Rate';
            $serviceCode = $r->getProduct() ? $r->getProduct() : '';
        }
        $serviceDescription = $serviceCode ? $this->getShipmentByCode($serviceCode) : '';

        $xml = new SimpleXMLElement('<?xml version = "1.0"?><RatingServiceSelectionRequest xml:lang="en-US"/>');

        $request = $xml->addChild('Request');
        $transReference = $request->addChild('TransactionReference');
        $transReference->addChild('CustomerContext', 'Rating and Service');
        $transReference->addChild('XpciVersion', '1.0');
        $request->addChild('RequestAction', 'Rate');
        $request->addChild('RequestOption', $params['10_action']);

        $pickupType = $xml->addChild('PickupType');
        $pickupType->addChild('Code', $params['47_rate_chart']['code']);
        $pickupType->addChild('Description', $params['47_rate_chart']['label']);

        $shipment = $xml->addChild('Shipment');

        // UPS Date shipping addition
        if ($includeSaturday) {
            $shipmentServiceOptions = $shipment->addChild('ShipmentServiceOptions');
            $shipmentServiceOptions->addChild('SaturdayDeliveryIndicator');
        }

        if ($serviceCode !== null) {
            $service = $shipment->addChild('Service');
            $service->addChild('Code', $serviceCode);
            $service->addChild('Description', $serviceDescription);
        }

        if (!Mage::helper('wsacommon')->checkItems('c2hpcHBpbmcvc2hpcHVzYS9zaGlwX29uY2U=',
                                                   'aWdsb29tZQ==',
                                                   'c2hpcHBpbmcvc2hpcHVzYS9zZXJpYWw=')) {
            $message = base64_decode('U2VyaWFsIEtleSBJcyBOT1QgVmFsaWQgZm9yIFdlYlNob3BBcHBzIERpbWVuc2lvbmFsIFNoaXBwaW5n');
            Mage::helper('wsalogger/log')->postCritical('usashipping', 'Fatal Error', $message);
            Mage::log($message);

            return Mage::getModel('shipping/rate_result');
        }

        $shipper = $shipment->addChild('Shipper');

        // WSA CHANGE
        if ($this->_request->getUpsShipperNumber()) {
            $shipperNum = $this->_request->getUpsShipperNumber();
        } else {
            $shipperNum = $this->getConfigData('shipper_number');
        }

        if ($this->getConfigFlag('negotiated_active') && ($shipperNum != '')) {
            $shipper->addChild('ShipperNumber', $shipperNum);
        }
        $address = $shipper->addChild('Address');
        $address->addChild('City', $params['origCity']);
        $address->addChild('PostalCode', $params['15_origPostal']);
        $address->addChild('CountryCode', $params['14_origCountry']);
        $address->addChild('StateProvinceCode', $params['origRegionCode']);

        $shipTo = $shipment->addChild('ShipTo');
        $address = $shipTo->addChild('Address');
        $address->addChild('PostalCode', $params['19_destPostal']);
        $address->addChild('CountryCode', $params['22_destCountry']);
        $address->addChild('ResidentialAddress', $params['49_residential']);
        $address->addChild('StateProvinceCode', $params['destRegionCode']);
        if ($params['49_residential'] === '01') {
            $address->addChild('ResidentialAddressIndicator', $params['49_residential']);
        }

        $shipFrom = $shipment->addChild('ShipFrom');
        $address = $shipFrom->addChild('Address');
        $address->addChild('PostalCode', $params['15_origPostal']);
        $address->addChild('CountryCode', $params['14_origCountry']);
        $address->addChild('StateProvinceCode', $params['origRegionCode']);

        $handProdFee = 0;
        $this->_addAllPackages($shipment, $handProdFee);

        if ($this->getConfigFlag('negotiated_active')) {
            $rateInfo = $shipment->addChild('RateInformation');
            $rateInfo->addChild('NegotiatedRatesIndicator');
        }

        $debugData = array('request' => Mage::helper('shipusa')->formatXML($xml->asXML()),
                           'handling_fee' => $handProdFee);

        $xmlRequest .= $xml->asXML();

        $xmlResponse = $this->_getCachedQuotes($xmlRequest);
        if ($xmlResponse === null) {

            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (boolean)$this->getConfigFlag('mode_xml'));
                $xmlResponse = curl_exec($ch);
                $this->_setCachedQuotes($xmlRequest, Mage::helper('shipusa')->formatXML($xmlResponse));
                $debugData['result'] = Mage::helper('shipusa')->formatXML($xmlResponse);
            } catch (Exception $e) {
                $debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
                $xmlResponse = '';
                if ($this->getDebugFlag()) {
                    Mage::helper('wsalogger/log')->postWarning('usashipping', 'UPS Exception Raised', $debugData);
                }
            }
        } else {
            $debugData['result'] = $xmlResponse;
            $debugData['cached'] = 'true';
        }
        $this->_debug($debugData);
        if ($this->getDebugFlag()) {
            Mage::helper('wsalogger/log')->postInfo('usashipping', 'UPS Request/Response', $debugData);
        }

        return $xmlResponse;
    }

    /**
     * Add all packages to the XML Request
     * @param $shipment
     * @param $handProdFee
     */
    protected function _addAllPackages(&$shipment, &$handProdFee)
    {

        $r = $this->_rawRequest;
        $applyHandlingPackage = Mage::getStoreConfigFlag('shipping/shipusa/handling_product_fee');


        $boxes = Mage::helper('shipusa')->getStdBoxes($this->_request->getAllItems(),
            $r->getIgnoreFreeItems());

        if (is_null($boxes)) {
            return Mage::getModel('shipping/rate_result');
        }

        $this->_numBoxes = count($boxes);

        $unitOfMeasure = $r->getUnitMeasure();

        if ($r->getUnitMeasure() == 'CONVERT_LBS_KGS') {
            $unitOfMeasure = 'KGS';
        }

        if (Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Dropcommon', 'carriers/dropship/active') ||
            Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Dropship', 'carriers/dropship/active')
        ) {
            if ($this->_request->getUpsUnitOfMeasure() != '') {
                $unitOfMeasure = $this->_request->getUpsUnitOfMeasure();
            }
        }

        if ($unitOfMeasure == 'KGS') {
            $lengthUnit = 'CM';
        } else {
            $lengthUnit = 'IN';
        }


        $splitIndPackage = $this->getConfigData('break_multiples');
        $splitMaxWeight = $this->getConfigData('max_multiple_weight');
        $maxPackageWeight = $r->getMaxPackageWeight();
        if (Mage::getStoreConfig('shipping/shipusa/diff_outer_ship')) {
            if ($r->getDestCountry() == 'US' && ($r->getDestRegionCode() == 'AK'
                    || $r->getDestRegionCode() == 'HI' || $r->getDestRegionCode() == 'PR')
            ) {
                $splitMaxWeight = 500;
            }
        }

        foreach ($boxes as $box) {

            if ($r->getUnitMeasure() == 'CONVERT_LBS_KGS') {
                $box['weight'] = $box['weight'] * 0.4536;
            }
            // catchall here
            $box['length'] = Mage::helper('shipusa')->toTwoDecimals($box['length']);
            $box['width'] = Mage::helper('shipusa')->toTwoDecimals($box['width']);
            $box['height'] = Mage::helper('shipusa')->toTwoDecimals($box['height']);

            // Note:: if max number of packages > 50 then wont return response
            $billableWeight = $this->_getCorrectWeight($box['weight']);
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

                    $this->_addPackage($shipment, $handProdFee, $applyHandlingPackage,
                        $r, $box, $unitOfMeasure, $billableWeight, $lengthUnit);
                }

            } else {

                $this->_addPackage($shipment, $handProdFee, $applyHandlingPackage,
                    $r, $box, $unitOfMeasure, $billableWeight, $lengthUnit);
            }
        }
    }

    /**
     * Add individual package
     * @param $shipment
     * @param $handProdFee
     * @param $applyHandlingPackage
     * @param $r
     * @param $box
     * @param $unitOfMeasure
     * @param $billableWeight
     * @param $lengthUnit
     */
    protected function _addPackage(&$shipment, &$handProdFee, $applyHandlingPackage,
                                   $r, $box, $unitOfMeasure, $billableWeight, $lengthUnit)
    {

        $package = $shipment->addChild('Package');
        if ($this->getConfigFlag('monetary_value')) {
            $packageServiceOptions = $package->addChild('PackageServiceOptions');
            $insuredValue = $packageServiceOptions->addChild('InsuredValue');
            $insuredValue->addChild('MonetaryValue', number_format($box['price'], 2, '.', ''));
        }

        $packagingType = $package->addChild('PackagingType');
        $packagingType->addChild('Code', $r->getContainer());
        if ($box['length'] > 0) {
            $dimensions = $package->addChild('Dimensions');
            $unitOfMeasurement = $dimensions->addChild('UnitOfMeasurement');
            $unitOfMeasurement->addChild('Code', $lengthUnit);
            $dimensions->addChild('Length', $box['length']);
            $dimensions->addChild('Width', $box['width']);
            $dimensions->addChild('Height', $box['height']);
        }

        $packageWeight = $package->addChild('PackageWeight');
        $unitOfMeasurement = $packageWeight->addChild('UnitOfMeasurement');
        $unitOfMeasurement->addChild('Code', $unitOfMeasure);
        $packageWeight->addChild('Weight', $billableWeight);

        if (!$applyHandlingPackage && $box['handling_fee'] > $handProdFee) {
            $handProdFee = $box['handling_fee'];
        } else if ($applyHandlingPackage) $handProdFee += $box['handling_fee'];

    }

    protected function generatePriceArr($xmlResponse, &$costArr, &$priceArr)
    {

        if (strlen(trim($xmlResponse)) > 0) {
            $xml = new Varien_Simplexml_Config();
            $xml->loadString($xmlResponse);
            $arr = $xml->getXpath("//RatingServiceSelectionResponse/Response/ResponseStatusCode/text()");
            $success = (int)$arr[0];
            if ($success === 1) {
                $arr = $xml->getXpath("//RatingServiceSelectionResponse/RatedShipment");
                // WSA change for Dropship
                if (Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Dropcommon', 'carriers/dropship/active') ||
                    Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Dropship', 'carriers/dropship/active')
                ) {
                    $allowedMethods = $this->_request->getUpsAllowedMethods();
                    if ($allowedMethods == null) {
                        $allowedMethods = explode(",", $this->getConfigData('allowed_methods'));
                    }
                } else {
                    $allowedMethods = explode(",", $this->getConfigData('allowed_methods'));
                }

                // Negotiated rates
                $negotiatedArr = $xml->getXpath("//RatingServiceSelectionResponse/RatedShipment/NegotiatedRates");
                $negotiatedActive = $this->getConfigFlag('negotiated_active')
                    && $this->getConfigData('shipper_number')
                    && !empty($negotiatedArr);

                foreach ($arr as $shipElement) {
                    $code = (string)$shipElement->Service->Code;
                    if (in_array($code, $allowedMethods)) {

                        if ($negotiatedActive) {
                            $cost = $shipElement->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue;
                        } else {
                            $cost = $shipElement->TotalCharges->MonetaryValue;
                        }

                        //convert price with Origin country currency code to base currency code
                        $successConversion = true;
                        $responseCurrencyCode = (string)$shipElement->TotalCharges->CurrencyCode;
                        if ($responseCurrencyCode) {
                            $allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
                            if (in_array($responseCurrencyCode, $allowedCurrencies) && $this->_getBaseCurrencyRate($responseCurrencyCode)>0) {
                                $cost = (float)$cost * $this->_getBaseCurrencyRate($responseCurrencyCode);
                            } else {
                                $errorTitle = Mage::helper('directory')
                                    ->__('Can\'t convert rate from "%s-%s".',
                                         $responseCurrencyCode,
                                         $this->_request->getPackageCurrency()->getCode());
                                $error = Mage::getModel('shipping/rate_result_error');
                                $error->setCarrier('ups');
                                $error->setCarrierTitle($this->getConfigData('title'));
                                $error->setErrorMessage($errorTitle);
                                $successConversion = false;
                            }
                        }

                        if ($successConversion) {
                            $costArr[$code] = $cost;
                            $priceArr[$code] = $this->getMethodPrice(floatval($cost), $code);
                        }
                    }
                }
            } else {
                $arr = $xml->getXpath("//RatingServiceSelectionResponse/Response/Error/ErrorDescription/text()");
                $errorTitle = (string)$arr[0][0];
                $error = Mage::getModel('shipping/rate_result_error');
                $error->setCarrier('ups');
                $error->setCarrierTitle($this->getConfigData('title'));
                //$error->setErrorMessage($errorTitle);
                $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                if ($this->getDebugFlag()) {
                    Mage::helper('wsalogger/log')->postWarning('usashipping', 'UPS Error Raised', '');
                }
            }
        }

        return $priceArr;
    }

    protected function _parseDimResponse($debugData, $xmlResponse, $handProdFee, $saturdayRates)
    {
        $costArr = array();
        $priceArr = array();

        $this->generatePriceArr($xmlResponse, $costArr, $priceArr);

        if($saturdayRates) {
            $this->generatePriceArr($saturdayRates, $costArr, $priceArr);
        }

        if ($this->getDebugFlag()) {
            Mage::helper('wsalogger/log')->postInfo('usashipping', 'UPS Response Prices', $priceArr);
        }

        if (is_object($this->_upsTransitModel)) {
            // let the date module deal with processing the results
            return $this->_upsTransitModel->processRateResults($priceArr);
        }

        $result = Mage::getModel('shipping/rate_result');
        if (empty($priceArr)) {
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier('ups');
            $error->setCarrierTitle($this->getConfigData('title'));
            if (!isset($errorTitle)) {
                $errorTitle = Mage::helper('usa')->__('Cannot retrieve shipping rates');
            }
            //$error->setErrorMessage($errorTitle);
            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
            $result->append($error);
            Mage::helper('wsalogger/log')->postCritical('usashipping', 'No rates found', $debugData);
        } else {
            if (is_object($this->_upsTransitModel)) {
                return $this->_upsTransitModel->processRateResults($priceArr);
            }

            foreach ($priceArr as $method => $price) {
                $rate = Mage::getModel('shipping/rate_result_method');
                $rate->setCarrier('ups');
                $rate->setCarrierTitle($this->getConfigData('title'));
                $rate->setMethod($method);
                $method_arr = $this->getShipmentByCode($method);
                $rate->setMethodTitle($method_arr);
                $rate->setCost($costArr[$method]);
                $rate->setPrice($price + $handProdFee); // WSA Change
                $result->append($rate);
            }
        }
        return $result;
    }


    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        $allowed = explode(',', $this->getConfigData('allowed_methods'));
        $arr = array();
        $isByCode = $this->getConfigData('type') == 'UPS_XML';
        foreach ($allowed as $k) {
            $arr[$k] = $isByCode ? $this->getShipmentByCode($k) : $this->getCode('method', $k);
        }
        return $arr;
    }


    /*****************************************************************
     * COMMON CODE- If change here change in Fedex
     */

    public function getTotalNumOfBoxes($weight)
    {
        if (!Mage::getStoreConfig('shipping/shipusa/active')) {
            return parent::getTotalNumOfBoxes($weight);
        }

        $this->_numBoxes = 1; // now set up with dimensional weights
        $weight = $this->convertWeightToLbs($weight);
        return $weight;
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
            return parent::proccessAdditionalValidation($request);
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
                $finalMethodPrice = $cost + (($cost * $handlingFee / 100));
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
        $finalMethodPrice = ceil($finalMethodPrice * 100) / 100;
        if ($this->getDebugFlag()) {
            Mage::helper('wsalogger/log')->postInfo('usashipping', 'Inbuilt UPS Handling Fee', $finalMethodPrice - $cost);
        }
        return $finalMethodPrice;
    }


    protected function _setFreeMethodRequest($freeMethod)
    {
        parent::_setFreeMethodRequest($freeMethod);
        $this->_rawRequest->setIgnoreFreeItems(true);


    }

    public function getCode($type, $code = '')
    {
        if (!Mage::getStoreConfig('shipping/shipusa/active')) {
            return parent::getCode($type, $code);
        }

        $codes = array(
            'action' => array(
                'single' => '3',
                'all' => '4',
            ),

            'originShipment' => array(
                // United States Domestic Shipments
                'United States Domestic Shipments' => array(
                    '01' => Mage::helper('usa')->__('UPS Next Day Air'),
                    '02' => Mage::helper('usa')->__('UPS Second Day Air'),
                    '03' => Mage::helper('usa')->__('UPS Ground'),
                    '07' => Mage::helper('usa')->__('UPS Worldwide Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '12' => Mage::helper('usa')->__('UPS Three-Day Select'),
                    '13' => Mage::helper('usa')->__('UPS Next Day Air Saver'),
                    '14' => Mage::helper('usa')->__('UPS Next Day Air Early A.M.'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '59' => Mage::helper('usa')->__('UPS Second Day Air A.M.'),
                    '65' => Mage::helper('usa')->__('UPS Saver'),
                ),
                // Shipments Originating in United States
                'Shipments Originating in United States' => array(
                    '01' => Mage::helper('usa')->__('UPS Next Day Air'),
                    '02' => Mage::helper('usa')->__('UPS Second Day Air'),
                    '03' => Mage::helper('usa')->__('UPS Ground'),
                    '07' => Mage::helper('usa')->__('UPS Worldwide Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '12' => Mage::helper('usa')->__('UPS Three-Day Select'),
                    '14' => Mage::helper('usa')->__('UPS Next Day Air Early A.M.'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '59' => Mage::helper('usa')->__('UPS Second Day Air A.M.'),
                    '65' => Mage::helper('usa')->__('UPS Saver'),
                ),
                // Shipments Originating in Canada
                'Shipments Originating in Canada' => array(
                    '01' => Mage::helper('usa')->__('UPS Express'),
                    '02' => Mage::helper('usa')->__('UPS Expedited'),
                    '07' => Mage::helper('usa')->__('UPS Worldwide Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '12' => Mage::helper('usa')->__('UPS Three-Day Select'),
                    '14' => Mage::helper('usa')->__('UPS Express Early A.M.'),
                    '65' => Mage::helper('usa')->__('UPS Saver'),
                ),
                // Shipments Originating in the European Union
                'Shipments Originating in the European Union' => array(
                    '07' => Mage::helper('usa')->__('UPS Express'),
                    '08' => Mage::helper('usa')->__('UPS Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express PlusSM'),
                    '65' => Mage::helper('usa')->__('UPS Saver'),
                ),
                // Polish Domestic Shipments
                'Polish Domestic Shipments' => array(
                    '07' => Mage::helper('usa')->__('UPS Express'),
                    '08' => Mage::helper('usa')->__('UPS Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Saver'),
                    '82' => Mage::helper('usa')->__('UPS Today Standard'),
                    '83' => Mage::helper('usa')->__('UPS Today Dedicated Courrier'),
                    '84' => Mage::helper('usa')->__('UPS Today Intercity'),
                    '85' => Mage::helper('usa')->__('UPS Today Express'),
                    '86' => Mage::helper('usa')->__('UPS Today Express Saver'),
                ),
                // Puerto Rico Origin
                'Puerto Rico Origin' => array(
                    '01' => Mage::helper('usa')->__('UPS Next Day Air'),
                    '02' => Mage::helper('usa')->__('UPS Second Day Air'),
                    '03' => Mage::helper('usa')->__('UPS Ground'),
                    '07' => Mage::helper('usa')->__('UPS Worldwide Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '14' => Mage::helper('usa')->__('UPS Next Day Air Early A.M.'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Saver'),
                ),
                // Shipments Originating in Mexico
                'Shipments Originating in Mexico' => array(
                    '07' => Mage::helper('usa')->__('UPS Express'),
                    '08' => Mage::helper('usa')->__('UPS Expedited'),
                    '54' => Mage::helper('usa')->__('UPS Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Saver'),
                ),
                // Shipments Originating in Other Countries
                'Shipments Originating in Other Countries' => array(
                    '07' => Mage::helper('usa')->__('UPS Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Saver')
                )
            ),

            'method' => array(
                '1DM' => Mage::helper('usa')->__('Next Day Air Early AM'),
                '1DML' => Mage::helper('usa')->__('Next Day Air Early AM Letter'),
                '1DA' => Mage::helper('usa')->__('Next Day Air'),
                '1DAL' => Mage::helper('usa')->__('Next Day Air Letter'),
                '1DAPI' => Mage::helper('usa')->__('Next Day Air Intra (Puerto Rico)'),
                '1DP' => Mage::helper('usa')->__('Next Day Air Saver'),
                '1DPL' => Mage::helper('usa')->__('Next Day Air Saver Letter'),
                '2DM' => Mage::helper('usa')->__('2nd Day Air AM'),
                '2DML' => Mage::helper('usa')->__('2nd Day Air AM Letter'),
                '2DA' => Mage::helper('usa')->__('2nd Day Air'),
                '2DAL' => Mage::helper('usa')->__('2nd Day Air Letter'),
                '3DS' => Mage::helper('usa')->__('3 Day Select'),
                'GND' => Mage::helper('usa')->__('Ground'),
                'GNDCOM' => Mage::helper('usa')->__('Ground Commercial'),
                'GNDRES' => Mage::helper('usa')->__('Ground Residential'),
                'STD' => Mage::helper('usa')->__('Canada Standard'),
                'XPR' => Mage::helper('usa')->__('Worldwide Express'),
                'WXS' => Mage::helper('usa')->__('Worldwide Express Saver'),
                'XPRL' => Mage::helper('usa')->__('Worldwide Express Letter'),
                'XDM' => Mage::helper('usa')->__('Worldwide Express Plus'),
                'XDML' => Mage::helper('usa')->__('Worldwide Express Plus Letter'),
                'XPD' => Mage::helper('usa')->__('Worldwide Expedited'),
            ),

            'pickup' => array(
                'RDP' => array("label" => 'Regular Daily Pickup', "code" => "01"),
                'OCA' => array("label" => 'On Call Air', "code" => "07"),
                'OTP' => array("label" => 'One Time Pickup', "code" => "06"),
                'LC' => array("label" => 'Letter Center', "code" => "19"),
                'CC' => array("label" => 'Customer Counter', "code" => "03"),
            ),

            'container' => array(
                'CP' => '00', // Customer Packaging
                'ULE' => '01', // UPS Letter Envelope
                'CSP' => '02', // Customer Supplied Package
                'UT' => '03', // UPS Tube
                'PAK' => '04', // PAK
                'UEB' => '21', // UPS Express Box
                'UW25' => '24', // UPS Worldwide 25 kilo
                'UW10' => '25', // UPS Worldwide 10 kilo
                'PLT' => '30', // Pallet
                'SEB' => '2a', // Small Express Box
                'MEB' => '2b', // Medium Express Box
                'LEB' => '2c', // Large Express Box
            ),

            'container_description' => array(
                'CP' => Mage::helper('usa')->__('Customer Packaging'),
                'ULE' => Mage::helper('usa')->__('UPS Letter Envelope'),
                'CSP' => Mage::helper('usa')->__('Customer Supplied Package'),
                'UT' => Mage::helper('usa')->__('UPS Tube'),
                'PAK' => Mage::helper('usa')->__('PAK'),
                'UEB' => Mage::helper('usa')->__('UPS Express Box'),
                'UW25' => Mage::helper('usa')->__('UPS Worldwide 25 kilo'),
                'UW10' => Mage::helper('usa')->__('UPS Worldwide 10 kilo'),
                'PLT' => Mage::helper('usa')->__('Pallet'),
                'SEB' => Mage::helper('usa')->__('Small Express Box'),
                'MEB' => Mage::helper('usa')->__('Medium Express Box'),
                'LEB' => Mage::helper('usa')->__('Large Express Box'),
            ),

            'dest_type' => array(
                'RES' => '01', // Residential
                'COM' => '02', // Commercial
            ),

            'dest_type_description' => array(
                'RES' => Mage::helper('usa')->__('Residential'),
                'COM' => Mage::helper('usa')->__('Commercial'),
            ),

            'unit_of_measure' => array(
                'LBS' => Mage::helper('usa')->__('Pounds'),
                'KGS' => Mage::helper('usa')->__('Kilograms'),
                'CONVERT_LBS_KGS' => Mage::helper('usa')->__('Convert Lbs to Kgs'),
            ),
            'containers_filter' => array(
                array(
                    'containers' => array('00'), // Customer Packaging
                    'filters' => array(
                        'within_us' => array(
                            'method' => array(
                                '01', // Next Day Air
                                '13', // Next Day Air Saver
                                '12', // 3 Day Select
                                '59', // 2nd Day Air AM
                                '03', // Ground
                                '14', // Next Day Air Early AM
                                '02', // 2nd Day Air
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                '07', // Worldwide Express
                                '54', // Worldwide Express Plus
                                '08', // Worldwide Expedited
                                '65', // Worldwide Saver
                                '11', // Standard
                            )
                        )
                    )
                ),
                array(
                    // Small Express Box, Medium Express Box, Large Express Box, UPS Tube
                    'containers' => array('2a', '2b', '2c', '03'),
                    'filters' => array(
                        'within_us' => array(
                            'method' => array(
                                '01', // Next Day Air
                                '13', // Next Day Air Saver
                                '14', // Next Day Air Early AM
                                '02', // 2nd Day Air
                                '59', // 2nd Day Air AM
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                '07', // Worldwide Express
                                '54', // Worldwide Express Plus
                                '08', // Worldwide Expedited
                                '65', // Worldwide Saver
                            )
                        )
                    )
                ),
                array(
                    'containers' => array('24', '25'), // UPS Worldwide 25 kilo, UPS Worldwide 10 kilo
                    'filters' => array(
                        'within_us' => array(
                            'method' => array()
                        ),
                        'from_us' => array(
                            'method' => array(
                                '07', // Worldwide Express
                                '54', // Worldwide Express Plus
                                '65', // Worldwide Saver
                            )
                        )
                    )
                ),
                array(
                    'containers' => array('01', '04'), // UPS Letter, UPS PAK
                    'filters' => array(
                        'within_us' => array(
                            'method' => array(
                                '01', // Next Day Air
                                '14', // Next Day Air Early AM
                                '02', // 2nd Day Air
                                '59', // 2nd Day Air AM
                                '13', // Next Day Air Saver
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                '07', // Worldwide Express
                                '54', // Worldwide Express Plus
                                '65', // Worldwide Saver
                            )
                        )
                    )
                ),
                array(
                    'containers' => array('04'), // UPS PAK
                    'filters' => array(
                        'within_us' => array(
                            'method' => array()
                        ),
                        'from_us' => array(
                            'method' => array(
                                '08', // Worldwide Expedited
                            )
                        )
                    )
                ),
            )
        );

        if (!isset($codes[$type])) {
//            throw Mage::exception('Mage_Shipping', Mage::helper('usa')->__('Invalid UPS CGI code type: %s', $type));
            return false;
        } elseif ('' === $code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
//            throw Mage::exception('Mage_Shipping', Mage::helper('usa')->__('Invalid UPS CGI code for type %s: %s', $type, $code));
            return false;
        } else {
            return $codes[$type][$code];
        }
    }

    /**
     * Determine whether zip-code is required for the country of destination
     *
     * @param string|null $countryId
     * @return bool
     */
    public function isZipCodeRequired($countryId = null)
    {
        if ($countryId != null) {
            return !Mage::helper('directory')->isZipCodeOptional($countryId);
        }
        return true;
    }

    /*********************************************************************************************
     * Required for Dropship
     *********************************************************************************************/

    protected function setXMLAccessRequest()
    {
        if (is_object($this->_request) && $this->_request->getUpsUserId()) {
            $userid = $this->_request->getUpsUserId();
        } else {
            $userid = $this->getConfigData('username');
        }

        if (is_object($this->_request) && $this->_request->getUpsPassword()) {
            $userid_pass = $this->_request->getUpsPassword();
        } else {
            $userid_pass = $this->getConfigData('password');
        }

        if (is_object($this->_request) && $this->_request->getUpsAccessLicenseNumber()) {
            $access_key = $this->_request->getUpsAccessLicenseNumber();
        } else {
            $access_key = $this->getConfigData('access_license_number');
        }


        $this->_xmlAccessRequest = <<<XMLAuth
<?xml version="1.0"?>
<AccessRequest xml:lang="en-US">
  <AccessLicenseNumber>$access_key</AccessLicenseNumber>
  <UserId>$userid</UserId>
  <Password>$userid_pass</Password>
</AccessRequest>
XMLAuth;
    }


    // required by Dropship only
    public function getShipmentByCode($code, $origin = null)
    {
        if (!Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Dropship', 'carriers/dropship/active') &&
            !Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Dropcommon')
        ) {
            return parent::getShipmentByCode($code, $origin);
        }
        if (!is_object($this->_request) || !$this->_request->getUpsShippingOrigin()) {
            return parent::getShipmentByCode($code, $origin);
        }

        if ($origin === null) {
            $origin = $this->_request->getUpsShippingOrigin();
            if ($origin == null) {
                $origin = $this->getConfigData('origin_shipment');
            }
        }
        $arr = $this->getCode('originShipment', $origin);
        if (isset($arr[$code]))
            return $arr[$code];
        else
            return false;
    }


    /*********************************************************************************************
     * ALL METHODS BELOW ARE REQUIRED FOR 1.4.1.1 ONLY - SEE ORIGINAL UPS FILE
     *********************************************************************************************/

    protected static $_quotesCache = array();
    protected $_baseCurrencyRate;


    /**
     * Returns cache key for some request to carrier quotes service
     *
     * @param string|array $requestParams
     * @return string
     */
    protected function _getQuotesCacheKey($requestParams)
    {
        if (is_array($requestParams)) {
            $requestParams = implode(',', array_merge(array($this->getCarrierCode()), array_keys($requestParams), $requestParams));
        }
        return crc32($requestParams);
    }

    /**
     * Checks whether some request to rates have already been done, so we have cache for it
     * Used to reduce number of same requests done to carrier service during one session
     *
     * Returns cached response or null
     *
     * @param string|array $requestParams
     * @return null|string
     */
    protected function _getCachedQuotes($requestParams)
    {
        $key = $this->_getQuotesCacheKey($requestParams);
        return isset(self::$_quotesCache[$key]) ? self::$_quotesCache[$key] : null;
    }

    /**
     * Sets received carrier quotes to cache
     *
     * @param string|array $requestParams
     * @param string $response
     * @return Mage_Usa_Model_Shipping_Carrier_Abstract
     */
    protected function _setCachedQuotes($requestParams, $response)
    {
        $key = $this->_getQuotesCacheKey($requestParams);
        self::$_quotesCache[$key] = $response;
        return $this;
    }

    protected function _getBaseCurrencyRate($code)
    {
        if (is_object($this->_request->getBaseCurrency())) {
            $baseCurrencyCode = $this->_request->getBaseCurrency()->getCode();
        } else {
            $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();
        }
        if (!$this->_baseCurrencyRate) {
            $this->_baseCurrencyRate = Mage::getModel('directory/currency')
                ->load($code)
                ->getAnyRate($baseCurrencyCode);
        }

        return $this->_baseCurrencyRate;
    }


}
