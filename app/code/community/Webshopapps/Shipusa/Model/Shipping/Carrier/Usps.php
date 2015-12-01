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
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * USPS shipping rates estimation
 *
 * @link       http://www.usps.com/webtools/htm/Development-Guide-v3-0b.htm
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
class Webshopapps_Shipusa_Model_Shipping_Carrier_Usps
extends Mage_Usa_Model_Shipping_Carrier_Usps
{
    private $_flatFound = false;

	public function setRequest(Mage_Shipping_Model_Rate_Request $request)
	{
		if (!Mage::getStoreConfig('shipping/shipusa/active')) {
			return parent::setRequest($request);
		}
		parent::setRequest($request);
		$r = $this->_rawRequest;
		$r->setIgnoreFreeItems(false);
		$r->setMaxPackageWeight($this->getConfigData('max_package_weight'));

		/* WSA change */

		if ($request->getUspsUserId() != '') {
			$r->setUspsUserid($request->getUspsUserId());
		} else {
			$r->setUspsUserid($this->getConfigData('userid'));
		}

		if ($request->getUspsPassword() != '') {
			$r->setUspsPassword($request->getUspsPassword());
		} else {
			$r->setUspsPassword($this->getConfigData('password'));
		}

		return $this;
	}

	/**
	 * Build RateV4 request, send it to USPS gateway and retrieve quotes in XML format
	 *
	 * @link http://www.usps.com/webtools/htm/Rate-Calculators-v2-3.htm
	 * @return Mage_Shipping_Model_Rate_Result
	 */
	protected function _getXmlQuotes()
	{
	    if (!Mage::getStoreConfig('shipping/shipusa/active')) {
				return parent::_getXmlQuotes();
		}

		$r = $this->_rawRequest;
        $boxes = Mage::helper('shipusa')->getStdBoxes($this->_request->getAllItems(),
            $r->getIgnoreFreeItems());

        $flatBoxes = Mage::helper('shipusa')->getFlatBoxes($this->_request->getAllItems(),
            $r->getIgnoreFreeItems());


        if (is_null($boxes) && is_null($flatBoxes)) {
            return Mage::getModel('shipping/rate_result');
        }

		$this->_numBoxes=count($boxes);
		$splitIndPackage = $this->getConfigData('break_multiples');
		$splitMaxWeight = $this->getConfigData('max_multiple_weight');
		$maxPackageWeight = $r->getMaxPackageWeight();
        $this->_flatFound = count($flatBoxes) > 0;
        $largestFlatIdFound = 0;
        $flatBoxTypes = array();

	 	if (!Mage::helper('wsacommon')->checkItems('c2hpcHBpbmcvc2hpcHVzYS9zaGlwX29uY2U=',
			'aWdsb29tZQ==','c2hpcHBpbmcvc2hpcHVzYS9zZXJpYWw=')) {
            $message = base64_decode('U2VyaWFsIEtleSBJcyBOT1QgVmFsaWQgZm9yIFdlYlNob3BBcHBzIERpbWVuc2lvbmFsIFNoaXBwaW5n');
            Mage::helper('wsalogger/log')->postCritical('usashipping','Fatal Error',$message);
            Mage::log($message);

            return Mage::getModel('shipping/rate_result');
		}


		if ($this->_isUSCountry($r->getDestCountryId())) {
			$xml = new SimpleXMLElement('<?xml version = "1.0" encoding = "UTF-8"?><RateV4Request/>');

			$xml->addAttribute('USERID', $r->getUserId());
			$xml->addChild('Revision', '2');
            $xmlFlat = clone $xml;
			$boxCounter=0;
            $flatCounter=0;

			foreach ($boxes as $box) {
                $billableWeight =  $this->_getCorrectWeight($box['weight']) ;

				if ($splitIndPackage && is_numeric($splitMaxWeight) && $splitMaxWeight> $maxPackageWeight &&
				$billableWeight<$splitMaxWeight) {
					for ($remainingWeight=$billableWeight;$remainingWeight>0;) {

						if ($remainingWeight-$maxPackageWeight<0) {
							$billableWeight=$remainingWeight;
							$remainingWeight=0;
						} else {
							$billableWeight=$maxPackageWeight;
							$remainingWeight-=$maxPackageWeight;
						}
                        $this->addPackage($box,$xml,$boxCounter,$billableWeight,$r,false);
					}
				} else {
                    $this->addPackage($box,$xml,$boxCounter,$billableWeight,$r,false);
                }
			}
            if($this->_flatFound) {
                foreach ($flatBoxes as $flatBox) {
                    $billableWeight =  $this->_getCorrectWeight($flatBox['weight']) ;
                    $boxId = $flatBox['flat_type'];

                    if(!in_array($boxId, $flatBoxTypes)) {
                        $flatBoxTypes[] = $boxId;
                    }

                    $boxWeighting = Mage::helper('shipusa')->getUspsBoxWeighting($boxId);

                    $largestFlatIdFound = $boxWeighting > $largestFlatIdFound ? $boxId : $largestFlatIdFound;

                    if ($splitIndPackage && is_numeric($splitMaxWeight) && $splitMaxWeight> $maxPackageWeight &&
                        $billableWeight<$splitMaxWeight) {
                        for ($remainingWeight=$billableWeight;$remainingWeight>0;) {

                            if ($remainingWeight-$maxPackageWeight<0) {
                                $billableWeight=$remainingWeight;
                                $remainingWeight=0;
                            } else {
                                $billableWeight=$maxPackageWeight;
                                $remainingWeight-=$maxPackageWeight;
                            }
                            $this->addPackage($flatBox,$xmlFlat,$flatCounter,$billableWeight,$r,true,$boxId);
                        }
                    } else {
                        $this->addPackage($flatBox,$xmlFlat,$flatCounter,$billableWeight,$r,true,$boxId);
                    }
                }
            }
			$api = 'RateV4';

		} else {
			$xml = new SimpleXMLElement('<?xml version = "1.0" encoding = "UTF-8"?><IntlRateV2Request/>');

			$xml->addAttribute('USERID', $r->getUserId());
			$xml->addChild('Revision', '2');
            $xmlFlat = clone $xml;
            $boxCounter=0;
            $flatCounter=0;

            foreach ($boxes as $box) {
				$billableWeight =  $this->_getCorrectWeight($box['weight']);

				if ($splitIndPackage && is_numeric($splitMaxWeight) && $splitMaxWeight> $maxPackageWeight &&
					$billableWeight<$splitMaxWeight) {
					for ($remainingWeight=$billableWeight;$remainingWeight>0;) {

						if ($remainingWeight-$maxPackageWeight<0) {
							$billableWeight=$remainingWeight;
							$remainingWeight=0;
						} else {
							$billableWeight=$maxPackageWeight;
							$remainingWeight-=$maxPackageWeight;
						}

						$this->addIntPackage($box,$xml,$boxCounter,$billableWeight,$r);
					}
				} else {
			        $this->addIntPackage($box,$xml,$boxCounter,$billableWeight,$r);
				}
			}
            if ($this->_flatFound) {
                foreach ($flatBoxes as $flatBox) {
                    $billableWeight =  $this->_getCorrectWeight($flatBox['weight']);
                    $boxId = $flatBox['flat_type'];

                    if(!in_array($boxId, $flatBoxTypes)) {
                        $flatBoxTypes[] = $boxId;
                    }

                    $boxWeighting = Mage::helper('shipusa')->getUspsBoxWeighting($boxId);

                    $largestFlatIdFound = $boxWeighting > $largestFlatIdFound ? $boxId : $largestFlatIdFound;

                    if ($splitIndPackage && is_numeric($splitMaxWeight) && $splitMaxWeight> $maxPackageWeight &&
                        $billableWeight<$splitMaxWeight) {
                        for ($remainingWeight=$billableWeight;$remainingWeight>0;) {

                            if ($remainingWeight-$maxPackageWeight<0) {
                                $billableWeight=$remainingWeight;
                                $remainingWeight=0;
                            } else {
                                $billableWeight=$maxPackageWeight;
                                $remainingWeight-=$maxPackageWeight;
                            }
                            $this->addIntPackage($flatBox,$xmlFlat,$flatCounter,$billableWeight,$r,true,$boxId);
                        }
                    } else {
                        $this->addIntPackage($flatBox,$xmlFlat,$flatCounter,$billableWeight,$r,true,$boxId);
                    }
                }
            }

			$api = 'IntlRateV2';
		}

		$request = $xml->asXML();
        $flatRequest = $xmlFlat->asXML();

		$debugData = array('request' => Mage::helper('shipusa')->formatXML($request));
        $flatDebugData = array('request' => Mage::helper('shipusa')->formatXML($flatRequest));

        $responseBody = $this->_getCachedQuotes($request);
		if ($responseBody === null) {
			try {
				$url = $this->getConfigData('gateway_url');
				if (!$url) {
					$url = $this->_defaultGatewayUrl;
				}

	            $fullRequest = "API=".urlencode($api)."&XML=".urlencode($request);
	            $ch = curl_init();
	            curl_setopt($ch, CURLOPT_URL, $url);
	            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	            curl_setopt($ch, CURLOPT_HEADER, 0);
	            curl_setopt($ch, CURLOPT_POST, 2);
	            curl_setopt($ch, CURLOPT_POSTFIELDS, $fullRequest);
	            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	            $responseBody = curl_exec ($ch);

				$debugData['result'] = Mage::helper('shipusa')->formatXML($responseBody);
				$this->_setCachedQuotes($request, $responseBody);
			}
			catch (Exception $e) {
				$debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
				$responseBody = '';
			}
			$this->_debug($debugData);
			if ($this->getDebugFlag()) {
	        	Mage::helper('wsalogger/log')->postInfo('usashipping','USPS Request/Response',$debugData);
	        }
		}
        //is everything assigned to a flat usps box? If so lets show flat boxes else dont
        if($this->_flatFound) {
            $standardResult = $this->_parseWsaXmlResponse($debugData,$responseBody);
            $flatResponse = $this->executeFlatRequest($api, $flatRequest, $flatDebugData);

            $flatAllowedMethod = $this->getFlatBoxAllowedMethods($largestFlatIdFound);
            $nonApplicableMethods = array_diff($this->getFlatBoxAllowedMethods($flatBoxTypes), $flatAllowedMethod);

            return $this->_parseWsaXmlResponse($flatDebugData, $flatResponse, $standardResult, $flatAllowedMethod, $nonApplicableMethods);
        } else {
		    return $this->_parseWsaXmlResponse($debugData,$responseBody);
        }
	}

    public function getFlatBoxAllowedMethods($boxCode){

        $allowedMethods = array();
        $isUS = $this->_isUSCountry($this->_rawRequest->getDestCountryId());
        $flatSourceModel = Mage::getModel('boxmenu/system_config_source_flatbox');

        if (!is_array($boxCode)) {
            $boxCode = array($boxCode);
        }

        foreach ($boxCode as $code) {
            if($isUS) {
                if($code != 99) {
                    $allowedMethods[] = $flatSourceModel->getCode('usps_box', $code);
                } else {
                    foreach ($flatSourceModel->getCode('usps_box') as $method) {
                        $allowedMethods[] = $method;
                    }
                }
            } else {
                if($code != 99) {
                    $allowedMethods[] = $flatSourceModel->getCode('usps_box_int', $code);
                } else {
                    foreach ($flatSourceModel->getCode('usps_box_int') as $method) {
                        $allowedMethods[] = $method;
                    }
                }
            }
        }

        return $allowedMethods;
    }

    private function executeFlatRequest($api, $request, &$debugData) {
        $responseBody = $this->_getCachedQuotes($request);
        if ($responseBody === null) {
            try {
                $url = $this->getConfigData('gateway_url');
                if (!$url) {
                    $url = $this->_defaultGatewayUrl;
                }

                $fullRequest = "API=".urlencode($api)."&XML=".urlencode($request);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_POST, 2);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fullRequest);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $responseBody = curl_exec ($ch);

                $debugData['result'] = Mage::helper('shipusa')->formatXML($responseBody);
                $this->_setCachedQuotes($request, $responseBody);
            }
            catch (Exception $e) {
                $debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
                $responseBody = '';
            }
            if ($this->getDebugFlag()) {
                Mage::helper('wsalogger/log')->postInfo('usashipping','USPS Flat Rate Request/Response',$debugData);
            }
        }

        return $responseBody;
    }

	private function addIntPackage($box,&$xml,&$boxCounter,$billableWeight,$r,$flatOnly = false,$boxId = 0) {
		$weightPounds = floor($billableWeight);
		$weightOunces = round(($billableWeight-floor($billableWeight)) * 16, 1);

		$package = $xml->addChild('Package');
		$package->addAttribute('ID', $boxCounter);

		$package->addChild('Pounds', $weightPounds);
		//  $package->addChild('Ounces', $r->getWeightOunces());
		$package->addChild('Ounces', $weightOunces); // WSA round to closest lb

		$package->addChild('Machinable', $r->getMachinable());
		$package->addChild('MailType', 'All');//Changed this from package to all.

		$package->addChild('ValueOfContents',number_format($r->getValue(),2));  //Always has to be here

		$package->addChild('Country', $r->getDestCountryName());

        if ($flatOnly){
            $boxName = Mage::getModel('boxmenu/system_config_source_flatbox')->getCode('usps_coded_box',$boxId);
            $package->addChild('Container', $boxName);
            $package->addChild('Size', 'REGULAR');
        } else {
            if ($box['length']<=0) {
                if ( (strtoupper($r->getContainer()) == 'FLAT RATE ENVELOPE' ||
                strtoupper($r->getContainer()) == 'FLAT RATE BOX')) {
                    $package->addChild('Container', $r->getContainer());
                } else {
                    $package->addChild('Container', '');
                }
            }
        }

        if ($box['length']>0) {
            $package->addChild('Container', 'RECTANGULAR');
            if(!$flatOnly) {
                $package->addChild('Size', 'LARGE');
            }
            $package->addChild('Width', $box['width']);
            $package->addChild('Length', $box['length']);
            $package->addChild('Height', $box['height']);
            $package->addChild('Girth', (2* ($box['height']+$box['width'])));  //TODO Add as separate attribute
        } else {
            if(!$flatOnly) {
                $package->addChild('Size', $r->getSize());
            }
            $package->addChild('Width', '');
            $package->addChild('Length', '');
            $package->addChild('Height', '');
            $package->addChild('Girth', '');  //TODO Add as separate attribute
        }


		if ($this->getConfigFlag('monetary_value')) {
			$specialServices = $package->addChild('ExtraServices');
			$specialServices->addChild('ExtraService','1');
		}

		$boxCounter++;
	}

	private function addPackage($box,&$xml,&$boxCounter,$billableWeight,$r,$flatOnly = false,$boxId = 0) {
		$weightPounds = floor($billableWeight);
		$weightOunces = round(($billableWeight-floor($billableWeight)) * 16, 1);

		$package = $xml->addChild('Package');
		$package->addAttribute('ID', $boxCounter);

        if($flatOnly) {
            $service = 'PRIORITY';
        } else {
            if($this->getConfigData('request_type') == 'ACCOUNT') {
                $service = 'ONLINE';
            } else {
                $service = $this->getCode('service_to_code', $r->getService());
                if (!$service) {
                    $service = $r->getService();
                }
            }
        }

        $package->addChild('Service', $service);

		// no matter Letter, Flat or Parcel, use Parcel
		if ($r->getService() == 'FIRST CLASS') {
			$package->addChild('FirstClassMailType', 'PARCEL');
		}
		$package->addChild('ZipOrigination', $r->getOrigPostal());
		//only 5 chars avaialble
		$package->addChild('ZipDestination', substr($r->getDestPostal(),0,5));
		$package->addChild('Pounds',$weightPounds);
		//  $package->addChild('Ounces', $r->getWeightOunces());
		$package->addChild('Ounces', $weightOunces); // WSA round to closest lb

        if ($flatOnly){
            $boxName = Mage::getModel('boxmenu/system_config_source_flatbox')->getCode('usps_coded_box',$boxId);
            $package->addChild('Container', $boxName);
            $package->addChild('Size', 'REGULAR');
        } else {
		    // Because some methods don't accept VARIABLE and (NON)RECTANGULAR containers
            if ($box['length']<=0) {
                if ( (strtoupper($r->getContainer()) == 'FLAT RATE ENVELOPE' ||
                strtoupper($r->getContainer()) == 'FLAT RATE BOX')) {
                    $package->addChild('Container', $r->getContainer());
                } else {
                    $package->addChild('Container', '');
                }
            }

            if ($box['length']>0) {
                $package->addChild('Container', 'RECTANGULAR');
                $package->addChild('Size', 'LARGE');
                $package->addChild('Width', $box['width']);
                $package->addChild('Length', $box['length']);
                $package->addChild('Height', $box['height']);
                $package->addChild('Girth', (2* ($box['height']+$box['width'])));  //TODO Add as separate attribute
            } else {
                $package->addChild('Size', $r->getSize());
            }
        }

		if ($this->getConfigFlag('monetary_value')) {
			$package->addChild('Value',number_format($box['price'],2)); //TODO work for boxes
			$specialServices = $package->addChild('SpecialServices');
			$specialServices->addChild('SpecialService','1');
		}
		$package->addChild('Machinable', $r->getMachinable());
		$boxCounter++;
	}

    /**
     * Parse calculated rates
     *
     * @link http://www.usps.com/webtools/htm/Rate-Calculators-v2-3.htm
     * @param                                        $debugData
     * @param string                                 $response
     * @param array|\Mage_Shipping_Model_Rate_Result $existingResult
     * @param array                                  $allowedFlatMethod
     * @param array                                  $flatBoxTypes
     * @return Mage_Shipping_Model_Rate_Result
     */
	protected function _parseWsaXmlResponse($debugData,$response,$existingResult=array(),$allowedFlatMethod=array(),$flatBoxTypes=array())
	{
		if (!Mage::getStoreConfig('shipping/shipusa/active')) {
			return parent::_parseXmlResponse($response);
		}

        $onlyFlatRates = false;
        $flatSourceModel = Mage::getModel('boxmenu/system_config_source_flatbox');

		$costArr = array();
		$priceArr = array();
		$foundMethods=array();
		$requestType = $this->getConfigData('request_type');

		if (strlen(trim($response)) > 0) {
			if (strpos(trim($response), '<?xml') === 0) {
				if (preg_match('#<\?xml version="1.0"\?>#', $response)) {
					$response = str_replace('<?xml version="1.0"?>', '<?xml version="1.0" encoding="ISO-8859-1"?>', $response);
				}

				$xml = simplexml_load_string($response);
				if (is_object($xml)) {
					$r = $this->_rawRequest;

					/* WSA change */
                    if(count($existingResult)) {
                        if(!is_array($allowedFlatMethod)) {
                            $allowedMethods = array($allowedFlatMethod);
                        } else {
                            $allowedMethods = $allowedFlatMethod;
                        }
                    } else {
                        if (Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Dropcommon','carriers/dropship/active') ||
                            Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Dropship','carriers/dropship/active') ) {
                            $allowedMethods = $this->_request->getUspsAllowedMethods();

                            if ($allowedMethods == null) {
                                $allowedMethods = explode(",", $this->getConfigData('allowed_methods'));
                                if (array_key_exists(0, $allowedMethods)) {
                                    if ($allowedMethods[0] == "") {
                                        $allowedMethods = null;
                                    }
                                }

                            if ($this->_flatFound) {
                                $allAllowedMethods = explode(",", $this->getConfigData('allowed_methods'));
                                $flatMethods = $this->getFlatBoxAllowedMethods(99);
                                $allowedMethods = array_diff($allAllowedMethods, $flatMethods);

                                if (count($allowedMethods < 1)) {
                                    $onlyFlatRates = true;
                                }
                                } else {
                                    $allowedMethods = explode(",", $this->getConfigData('allowed_methods'));
                                }
                            }
                        } else {
                            if($this->_flatFound){
                                $allAllowedMethods = explode(",", $this->getConfigData('allowed_methods'));
                                $flatMethods = $this->getFlatBoxAllowedMethods(99);
                                $allowedMethods = array_diff($allAllowedMethods,$flatMethods);

                                if (count($allowedMethods < 1)) {
                                    $onlyFlatRates = true;
                                }
                            } else {
                                $allowedMethods = explode(",", $this->getConfigData('allowed_methods'));
                            }
                        }
                    }

					/*
					 * US Domestic Rates
					 */
					$firstTimeRound=true;
                    $additionalRatePrice = 0;
					if ($this->_isUSCountry($r->getDestCountryId())) {
						if (is_object($xml->Package)) {
							foreach ($xml->Package as $package) {
								reset($foundMethods);
								if (is_object($package->Postage)) {
									foreach ($package->Postage as $postage) {
                                        $basicName = $this->_filterServiceName((string)$postage->MailService);
										$postage->MailService = $basicName;

                                        $serviceName = $this->stripTimeStamp($basicName);

										if (in_array($serviceName, $allowedMethods)) {
											// now get insurance
											$insurancePrice=0;
											if ($this->getConfigData('display_insurance')!='none') {
												if (is_object($package->Postage->SpecialServices) && is_object($package->Postage->SpecialServices->SpecialService)) {
													foreach ($package->Postage->SpecialServices->SpecialService as $specialService) {
														if ($specialService->ServiceID == 1) {
															$insurancePrice = $specialService->Price;
															break;
														}
													}
													if ($this->getConfigData('display_insurance')=='optional') {
														//TODO
													}

												}
											}

											if ($requestType=='ACCOUNT' && !empty($postage->CommercialRate)) {
												$ratePrice = (string)$postage->CommercialRate;
											} else {
												$ratePrice = (string)$postage->Rate;
											}

											if ($firstTimeRound) {
												$costArr[$serviceName] = $ratePrice;
												$priceArr[$serviceName] = $this->getMethodPrice($ratePrice + $insurancePrice, $serviceName);
											} else {
												if (array_key_exists($serviceName, $priceArr)) {
													$costArr[$serviceName] += $ratePrice;
													$priceArr[$serviceName] += $this->getMethodPrice($ratePrice + $insurancePrice, $serviceName);
													$foundMethods[$serviceName]=0;
												} // else ignore
											}
										} else if (in_array($serviceName, $flatBoxTypes) && count($existingResult)) {
                                            if ($requestType=='ACCOUNT' && !empty($postage->CommercialRate)) {
                                                $additionalRatePrice += (string)$postage->CommercialRate;
                                            } else {
                                                $additionalRatePrice += (string)$postage->Rate;

                                            }
                                            foreach ($allowedMethods as $meth) {
                                                $foundMethods[$meth]=0;

                                            }
                                        }
									}
								}
								if (!$firstTimeRound) {
									$unwantedArr = array_diff_key($priceArr,$foundMethods);
									$priceArr = array_diff_key($priceArr,$unwantedArr);
									$costArr = array_diff_key($costArr,$unwantedArr);
								}

                                if(count($existingResult)) {
                                    foreach ($allowedMethods as $method) {
                                        if($additionalRatePrice > 0) {
                                            if(array_key_exists($method, $priceArr)){
                                                $priceArr[$method] += $additionalRatePrice;
                                                $costArr[$method] += $additionalRatePrice;
                                            } else {
                                                $priceArr[$method] = $additionalRatePrice;
                                                $costArr[$method] = $additionalRatePrice;
                                            }
                                            $additionalRatePrice = 0;
                                        }
                                    }
                                }

								$firstTimeRound=false;
							}
							asort($priceArr);
						}
					} else {
						/*
						 * International Rates
						 */
                        $flatError=false;
                        $insurancePrice=0;
                        $additionalRatePrice = 0;
						if (is_object($xml->Package)) {
							foreach ($xml->Package as $package) {
                                $skipCounter = 0;
								reset($foundMethods);
								if (is_object($package->Service)) {
									foreach ($package->Service as $service) {
                                        $serviceCounter = count($package->Service);
										$serviceName = $this->_filterServiceName((string)$service->SvcDescription);
										$service->SvcDescription = $serviceName;
                                        if (count($existingResult)) {
                                            //International returns ALL services E.g small flat rate for a large flat rate box. Remove what we dont need
                                            //TODO - looks like USPS dont support FR envelopes INTL
                                            $method = $this->getFlatBoxAllowedMethods($flatSourceModel->getCode('usps_box_type',(String)$service->Container));
                                            if (!in_array($serviceName, $method)) {
                                                $skipCounter++;
                                                if($serviceCounter == $skipCounter){
                                                    $flatError = true;
                                                }
                                                continue;
                                            }
                                        }
										if (in_array($serviceName, $allowedMethods)) {
											if ($firstTimeRound) {
												$costArr[$serviceName] = (string)$service->Postage;
												$priceArr[$serviceName] = $this->getMethodPrice((string)$service->Postage + $insurancePrice, $serviceName);
											} else {
												if (array_key_exists($serviceName, $priceArr)) {
													$costArr[$serviceName] += (string)$service->Postage;
													$priceArr[$serviceName] += $this->getMethodPrice((string)$service->Postage + $insurancePrice, $serviceName);
													$foundMethods[$serviceName]=0;
												}
											}
										} else if (in_array($serviceName, $flatBoxTypes) && count($existingResult)) {
                                            $additionalRatePrice += (string)$service->Postage;;

                                            foreach ($allowedMethods as $meth) {
                                                $foundMethods[$meth]=0;
                                            }
                                        }
									}
								}

								if (!$firstTimeRound) {
									$unwantedArr = array_diff_key($priceArr,$foundMethods);
									$priceArr = array_diff_key($priceArr,$unwantedArr);
									$costArr = array_diff_key($costArr,$unwantedArr);
								}

                                if(count($existingResult)) {
                                    foreach ($allowedMethods as $method) {
                                        if($additionalRatePrice > 0) {
                                            if(array_key_exists($method, $priceArr)){
                                                $priceArr[$method] += $additionalRatePrice;
                                            } else {
                                                $priceArr[$method] = $additionalRatePrice;
                                            }
                                            $additionalRatePrice = 0;
                                        }
                                    }
                                }

								$firstTimeRound=false;
							}
                            if($flatError){
                                foreach ($allowedMethods as $method) {
                                    unset($priceArr[$method]);
                                    Mage::helper('wsalogger/log')->postWarning('usashipping','No USPS Flat Rate found','Check weight does not exceed max allowed for destination.');
                                }
                            }
							asort($priceArr);
						}
					}
				}
			}
		}

        // now ensure have correct method price, rounded to 2 decimal places


		if ($this->getDebugFlag()) {
        	Mage::helper('wsalogger/log')->postInfo('usashipping','USPS Response Prices',$priceArr);
        }

        if(!is_object($existingResult)) {
		    $result = Mage::getModel('shipping/rate_result');
            $foundStandard = false;
        } else {
            $result = $existingResult;
            $foundStandard = true;
        }
		if (empty($priceArr) && !$foundStandard && !$onlyFlatRates) {
			$error = Mage::getModel('shipping/rate_result_error');
			$error->setCarrier('usps');
			$error->setCarrierTitle($this->getConfigData('title'));
			$error->setErrorMessage($this->getConfigData('specificerrmsg'));
			$result->append($error);
		  	Mage::helper('wsalogger/log')->postWarning('usashipping','No rates found',$debugData);
		} else {
            if (empty($priceArr)) {
                if ($this->getDebugFlag()) {
                    Mage::helper('wsalogger/log')->postWarning('usashipping','Error retriving Flat Box USPS Rates','No Allowed Rates Found');
                }
                return $result;
            }
			foreach ($priceArr as $method=>$price) {
				$rate = Mage::getModel('shipping/rate_result_method');
				$rate->setCarrier('usps');
				$rate->setCarrierTitle($this->getConfigData('title'));
				$rate->setMethod($method);
				$rate->setMethodTitle($method);
				$rate->setCost($costArr[$method]);
				$rate->setPrice($price);
				$result->append($rate);
			}
		}

		return $result;
	}

    protected function stripTimeStamp($name){
        $search = array(' 1-Day',' 2-Day',' 3-Day',' DPO');

        $name = str_replace($search, '', $name);

        return $name;
    }


    /**
     * Get configuration data of carrier
     *
     * @param string $type
     * @param string $code
     * @return array|bool
     */
    public function getCode($type, $code='')
    {
    	if (!Mage::getStoreConfig('shipping/shipusa/active')) {
			return parent::getCode($type, $code);
		}
        $codes = array(

            'service'=>array(
                'FIRST CLASS' => Mage::helper('usa')->__('First-Class'),
                'PRIORITY'    => Mage::helper('usa')->__('Priority Mail'),
                'EXPRESS'     => Mage::helper('usa')->__('Express Mail'),
                'BPM'         => Mage::helper('usa')->__('Bound Printed Matter'),
                'PARCEL'      => Mage::helper('usa')->__('Parcel Post'),
                'MEDIA'       => Mage::helper('usa')->__('Media Mail'),
                'LIBRARY'     => Mage::helper('usa')->__('Library'),
                'MILITARY'     => Mage::helper('usa')->__('Military'),
            ),

            'service_to_code'=>array(
                'First-Class'                                   => 'FIRST CLASS',
                'First-Class Mail International Large Envelope' => 'FIRST CLASS',
                'First-Class Mail International Letter'         => 'FIRST CLASS',
                'First-Class Mail International Package'        => 'FIRST CLASS',
                'First-Class Mail International Parcel'         => 'FIRST CLASS',
                'First-Class Mail'                              => 'FIRST CLASS',
                'First-Class Mail Flat'                         => 'FIRST CLASS',
                'First-Class Mail Large Envelope'               => 'FIRST CLASS',
                'First-Class Mail International'                => 'FIRST CLASS',
                'First-Class Mail Letter'                       => 'FIRST CLASS',
                'First-Class Mail Parcel'                       => 'FIRST CLASS',
                'First-Class Mail Package'                      => 'FIRST CLASS',
                'Standard Post'                    => 'STANDARD POST',
                'Bound Printed Matter'             => 'BPM',
                'Media Mail'                       => 'MEDIA',
                'Library Mail'                     => 'LIBRARY',
                'Priority Mail Express'                                                 => 'EXPRESS',
                'Priority Mail Express PO to PO'                                        => 'EXPRESS',
                'Priority Mail Express Flat Rate Envelope'                              => 'EXPRESS',
                'Priority Mail Express Flat-Rate Envelope Sunday/Holiday Guarantee'     => 'EXPRESS',
                'Priority Mail Express Sunday/Holiday Guarantee'                        => 'EXPRESS',
                'Priority Mail Express Flat Rate Envelope Hold For Pickup'              => 'EXPRESS',
                'Priority Mail Express Hold For Pickup'                                 => 'EXPRESS',
                'Global Express Guaranteed (GXG)'                                       => 'EXPRESS',
                'Global Express Guaranteed Non-Document Rectangular'                    => 'EXPRESS',
                'Global Express Guaranteed Non-Document Non-Rectangular'                => 'EXPRESS',
                'USPS GXG Envelopes'                                                    => 'EXPRESS',
                'Priority Mail Express International'                                   => 'EXPRESS',
                'Priority Mail Express International Flat Rate Envelope'                => 'EXPRESS',
                'Priority Mail Express International Legal Flat Rate Envelope'          => 'EXPRESS',
                'Priority Mail Express International Padded Flat Rate Envelope'         => 'EXPRESS',
                'Priority Mail Express International Flat Rate Boxes'                   => 'EXPRESS',
                'Priority Mail'                                          => 'PRIORITY',
                'Priority Mail Small Flat Rate Box'                      => 'PRIORITY',
                'Priority Mail Medium Flat Rate Box'                     => 'PRIORITY',
                'Priority Mail Large Flat Rate Box'                      => 'PRIORITY',
                'Priority Mail Flat Rate Box'                            => 'PRIORITY',
                'Priority Mail Flat Rate Envelope'                       => 'PRIORITY',
                'Priority Mail International'                            => 'PRIORITY',
                'Priority Mail International Flat Rate Envelope'         => 'PRIORITY',
                'Priority Mail International Small Flat Rate Box'        => 'PRIORITY',
                'Priority Mail International Medium Flat Rate Box'       => 'PRIORITY',
                'Priority Mail International Large Flat Rate Box'        => 'PRIORITY',
                'Priority Mail International Flat Rate Box'              => 'PRIORITY',
                'Priority Mail Military'                                 => 'MILITARY',
                'Priority Mail Military Flat Rate Envelope'              => 'MILITARY',
                'Priority Mail Military Small Flat Rate Box'             => 'MILITARY',
                'Priority Mail Military Medium Flat Rate Box'            => 'MILITARY',
                'Priority Mail Military Large Flat Rate Box'             => 'MILITARY',
                'Priority Mail Military Flat Rate Box'                   => 'MILITARY',
            ),

            'first_class_mail_type'=>array(
                'LETTER'      => Mage::helper('usa')->__('Letter'),
                'FLAT'        => Mage::helper('usa')->__('Flat'),
                'PARCEL'      => Mage::helper('usa')->__('Parcel'),
            ),

            'container'=>array(
                'VARIABLE'           => Mage::helper('usa')->__('Variable'),
                'FLAT RATE BOX'      => Mage::helper('usa')->__('Flat-Rate Box'),
                'FLAT RATE ENVELOPE' => Mage::helper('usa')->__('Flat-Rate Envelope'),
                'RECTANGULAR'        => Mage::helper('usa')->__('Rectangular'),
                'NONRECTANGULAR'     => Mage::helper('usa')->__('Non-rectangular'),
            ),

            'containers_filter' => array(
                array(
                    'containers' => array('VARIABLE'),
                    'filters'    => array(
                        'within_us' => array(
                            'method' => array(
                                'First-Class Mail Large Envelope',
                                'First-Class Mail Letter',
                                'First-Class Mail Parcel',
                                'First-Class Mail Postcards',
                                'Priority Mail',
                                'Priority Mail Express Hold For Pickup',
                                'Priority Mail Express',
                                'Standard Post',
                                'Media Mail',
                                'Library Mail',
                                'Priority Mail Express Flat Rate Envelope',
                                'First-Class Mail Large Postcards',
                                'Priority Mail Flat Rate Envelope',
                                'Priority Mail Medium Flat Rate Box',
                                'Priority Mail Large Flat Rate Box',
                                'Priority Mail Express Sunday/Holiday Delivery',
                                'Priority Mail Express Sunday/Holiday Delivery Flat Rate Envelope',
                                'Priority Mail Express Flat Rate Envelope Hold For Pickup',
                                'Priority Mail Small Flat Rate Box',
                                'Priority Mail Padded Flat Rate Envelope',
                                'Priority Mail Express Legal Flat Rate Envelope',
                                'Priority Mail Express Legal Flat Rate Envelope Hold For Pickup',
                                'Priority Mail Express Sunday/Holiday Delivery Legal Flat Rate Envelope',
                                'Priority Mail Hold For Pickup',
                                'Priority Mail Large Flat Rate Box Hold For Pickup',
                                'Priority Mail Medium Flat Rate Box Hold For Pickup',
                                'Priority Mail Small Flat Rate Box Hold For Pickup',
                                'Priority Mail Flat Rate Envelope Hold For Pickup',
                                'Priority Mail Gift Card Flat Rate Envelope',
                                'Priority Mail Gift Card Flat Rate Envelope Hold For Pickup',
                                'Priority Mail Window Flat Rate Envelope',
                                'Priority Mail Window Flat Rate Envelope Hold For Pickup',
                                'Priority Mail Small Flat Rate Envelope',
                                'Priority Mail Small Flat Rate Envelope Hold For Pickup',
                                'Priority Mail Legal Flat Rate Envelope',
                                'Priority Mail Legal Flat Rate Envelope Hold For Pickup',
                                'Priority Mail Padded Flat Rate Envelope Hold For Pickup',
                                'Priority Mail Regional Rate Box A',
                                'Priority Mail Regional Rate Box A Hold For Pickup',
                                'Priority Mail Regional Rate Box B',
                                'Priority Mail Regional Rate Box B Hold For Pickup',
                                'First-Class Package Service Hold For Pickup',
                                'Priority Mail Express Flat Rate Boxes',
                                'Priority Mail Express Flat Rate Boxes Hold For Pickup',
                                'Priority Mail Express Sunday/Holiday Delivery Flat Rate Boxes',
                                'Priority Mail Regional Rate Box C',
                                'Priority Mail Regional Rate Box C Hold For Pickup',
                                'First-Class Package Service',
                                'Priority Mail Express Padded Flat Rate Envelope',
                                'Priority Mail Express Padded Flat Rate Envelope Hold For Pickup',
                                'Priority Mail Express Sunday/Holiday Delivery Padded Flat Rate Envelope',
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                'Priority Mail International Flat Rate Envelope',
                                'Priority Mail International Large Flat Rate Box',
                                'Priority Mail International Medium Flat Rate Box',
                                'Priority Mail International Small Flat Rate Box',
                                'Global Express Guaranteed (GXG)',
                                'USPS GXG Envelopes',
                                'Priority Mail International',
                                'First-Class Mail International Package',
                                'First-Class Mail International Large Envelope',
                                'First-Class Mail International Parcel',
                                'Priority Mail Express International',
                                'Priority Mail Express International Flat Rate Envelope',
                                'Priority Mail Express International Legal Flat Rate Envelope',
                                'Priority Mail Express International Padded Flat Rate Envelope',
                                'Priority Mail Express International Flat Rate Boxes',
                                'Priority Mail Military',
                                'Priority Mail Military Flat Rate Envelope',
                                'Priority Mail Military Large Flat Rate Box',
                                'Priority Mail Military Medium Flat Rate Box',
                                'Priority Mail Military Small Flat Rate Box',
                            )
                        )
                    )
                ),
                array(
                    'containers' => array('FLAT RATE BOX'),
                    'filters'    => array(
                        'within_us' => array(
                            'method' => array(
                                'Priority Mail Large Flat Rate Box',
                                'Priority Mail Medium Flat Rate Box',
                                'Priority Mail Small Flat Rate Box',

                                'Priority Mail Regional Rate Box A',
                                'Priority Mail Regional Rate Box B',
                                'Priority Mail Regional Rate Box C',
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                'Priority Mail International Large Flat Rate Box',
                                'Priority Mail International Medium Flat Rate Box',
                                'Priority Mail International Small Flat Rate Box',
                                'Priority Mail Military Small Flat Rate Box',
                                'Priority Mail Military Medium Flat Rate Box',
                                'Priority Mail Military Large Flat Rate Box',
                            )
                        )
                    )
                ),
                array(
                    'containers' => array('FLAT RATE ENVELOPE'),
                    'filters'    => array(
                        'within_us' => array(
                            'method' => array(
                                'Priority Mail Flat Rate Envelope',
                                'Priority Mail Padded Flat Rate Envelope',
                                'Priority Mail Small Flat Rate Envelope',
                                'Priority Mail Legal Flat Rate Envelope',
                                'Priority Mail Express Flat Rate Envelope',
                                'Priority Mail Express Padded Flat Rate Envelope',
                                'Priority Mail Express Legal Flat Rate Envelope',
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                'Priority Mail Express International Flat Rate Envelope',
                                'Priority Mail International Flat Rate Envelope',
                                'Priority Mail Military Flat Rate Envelope',
                                'Priority Mail Military Padded Flat Rate Envelope',
                                'Priority Mail Military Legal Flat Rate Envelope',
                            )
                        )
                    )
                ),
                array(
                    'containers' => array('RECTANGULAR'),
                    'filters'    => array(
                        'within_us' => array(
                            'method' => array(
                                'Priority Mail Express',
                                'Priority Mail',
                                'Standard Post',
                                'Media Mail',
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                'USPS GXG Envelopes',
                                'Priority Mail Express International',
                                'Priority Mail International',
                                'First-Class Mail International Package',
                                'First-Class Mail International Parcel',
                                'First-Class Package International Service',
                            )
                        )
                    )
                ),
                array(
                    'containers' => array('NONRECTANGULAR'),
                    'filters'    => array(
                        'within_us' => array(
                            'method' => array(
                                'Priority Mail Express',
                                'Priority Mail',
                                'Standard Post',
                                'Media Mail',
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                'Global Express Guaranteed (GXG)',
                                'USPS GXG Envelopes',
                                'Priority Mail Express International',
                                'Priority Mail International',
                                'First-Class Mail International Package',
                                'First-Class Mail International Parcel',
                                'First-Class Package International Service',
                            )
                        )
                    )
                ),
             ),

            'size'=>array(
                'REGULAR'     => Mage::helper('usa')->__('Regular'),
                'LARGE'       => Mage::helper('usa')->__('Large'),
            ),

            'machinable'=>array(
                'true'        => Mage::helper('usa')->__('Yes'),
                'false'       => Mage::helper('usa')->__('No'),
            ),

            'delivery_confirmation_types' => array(
                'True' => Mage::helper('usa')->__('Not Required'),
                'False'  => Mage::helper('usa')->__('Required'),
            ),
            'insurance'=>array(
                'mandatory'        	=> Mage::helper('usa')->__('Compulsory'),
                //   'optional'       	=> Mage::helper('usa')->__('Optional'), //TODO
            	'none'				=> Mage::helper('usa')->__('None'),
            ),
        );

        $methods = $this->getConfigData('methods');
        if (!empty($methods)) {
            $codes['method'] = explode(",", $methods);
        } else {
            $codes['method'] = array();
        }

        if (!isset($codes[$type])) {
            return false;
        } elseif (''===$code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }



	public function getResponse()
	{
		if (!Mage::getStoreConfig('shipping/shipusa/active')) {
			return parent::getResponse();
		}
		$statuses = '';
		if ($this->_result instanceof Mage_Shipping_Model_Tracking_Result) {
			if ($trackings = $this->_result->getAllTrackings()) {
				foreach ($trackings as $tracking) {
					if($data = $tracking->getAllData()) {
						if (!empty($data['track_summary'])) {
							$statuses .= Mage::helper('usa')->__($data['track_summary']);
						} else {
							$statuses .= Mage::helper('usa')->__('Empty response');
						}
					}
				}
			}
		}
		if (empty($statuses)) {
			$statuses = Mage::helper('usa')->__('Empty response');
		}
		return $statuses;
	}

	/*****************************************************************
	 * COMMON CODE- If change here change in Fedex, UPS
	 */

	public function getTotalNumOfBoxes($weight)
	{
		if (!Mage::getStoreConfig('shipping/shipusa/active')) {
			return parent::getTotalNumOfBoxes($weight);
		}
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
		if(!count($request->getAllItems())) {
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

	public function getFinalPriceWithHandlingFee($cost){

		if (!Mage::getStoreConfig('shipping/shipusa/active')) {
            $cost = ceil($cost*100) / 100;
            return parent::getFinalPriceWithHandlingFee($cost);
		}
		$handlingFee = $this->getConfigData('handling_fee');
		if (!is_numeric($handlingFee) || $handlingFee<=0) {
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

		if($handlingAction == self::HANDLING_ACTION_PERPACKAGE)
		{
			if ($handlingType == self::HANDLING_TYPE_PERCENT) {
				$finalMethodPrice = $cost + ($cost * $handlingFee/100);
			} else {
				$finalMethodPrice = $cost + ($handlingFee * $this->_numBoxes);
			}
		} else {
			if ($handlingType == self::HANDLING_TYPE_PERCENT) {
				$finalMethodPrice = $cost + ($cost * $handlingFee/100);
			} else {
				$finalMethodPrice = $cost + $handlingFee;
			}

		}
		if ($this->getDebugFlag()) {
			Mage::helper('wsalogger/log')->postInfo('usashipping','Inbuilt UPS Handling Fee',$finalMethodPrice-$cost);
		}
        $finalMethodPrice = ceil($finalMethodPrice*100) / 100;

        return $finalMethodPrice;
	}



	protected function _setFreeMethodRequest($freeMethod)
	{
		if (!Mage::getStoreConfig('shipping/shipusa/active')) {
				return parent::_setFreeMethodRequest($freeMethod);
		}
		parent::_setFreeMethodRequest($freeMethod);
		$this->_rawRequest->setIgnoreFreeItems(true);


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
	 * ALL METHODS BELOW ARE REQUIRED FOR 1.6.0 and before ONLY - SEE ORIGINAL USPS FILE
	 *********************************************************************************************/

	/**
	 * Get correct weigt.
	 *
	 * Namely:
	 * Checks the current weight to comply with the minimum weight standards set by the carrier.
	 * Then strictly rounds the weight up until the first significant digit after the decimal point.
	 *
	 * @param float|integer|double $weight
	 * @return float
	 */
	protected function _getCorrectWeight($weight)
	{
		if (!Mage::getStoreConfig('shipping/shipusa/active')) {
			return parent::_getCorrectWeight($weight);
		}
		$minWeight = $this->getConfigData('min_package_weight');

		if($weight < $minWeight){
			$weight = $minWeight;
		}

		//rounds a number to one significant figure
		$weight = ceil($weight*10) / 10;

		return $weight;
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
        if (!$this->_baseCurrencyRate) {
            $this->_baseCurrencyRate = Mage::getModel('directory/currency')
                ->load($code)
                ->getAnyRate($this->_request->getBaseCurrency()->getCode());
        }

        return $this->_baseCurrencyRate;
    }


    /****
     * Required for 1.4.2 and below
     */
    protected function _filterServiceName($name)
    {
        $name = (string)preg_replace(array('~<[^/!][^>]+>.*</[^>]+>~sU', '~\<!--.*--\>~isU', '~<[^>]+>~is'), '', html_entity_decode($name));
        $name = str_replace('*', '', $name);

        return $name;
    }

    protected function _isUSCountry($countyId)
    {
        switch ($countyId) {
            case 'AS': // Samoa American
            case 'GU': // Guam
            case 'MP': // Northern Mariana Islands
            case 'PW': // Palau
            case 'PR': // Puerto Rico
            case 'VI': // Virgin Islands US
            case 'US'; // United States
                return true;
        }

        return false;
    }



}
