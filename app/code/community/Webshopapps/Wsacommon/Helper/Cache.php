<?php
/**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_Wsacommon
 * User         karen
 * Date         16/11/2013
 * Time         22:19
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */


class Webshopapps_Wsacommon_Helper_Cache extends Mage_Core_Helper_Abstract
{
    const CACHE_PREFIX = 'wsacommon_cache'; // May want to push this to be declared in method later on
    const CACHE_TAG = Mage_Core_Model_Config::CACHE_TAG;
    const CACHE_TTL = 3600;

    static $_dataCache;

    /**
     * Data Cache Key gen for Magento cache
     * @param $key
     * @return string
     */
    public function getDataCacheKeySha($key)
    {
        if (is_array($key)) {
            $key = implode(',',array_keys($key));
        }
        return sha1($key);
    }

    /**
     * get Cached Data for Magento cache
     * @param $key
     * @return mixed|null|string
     */
    public function getCachedDataSha($key)
    {
        $result = $this->getCachedData($key);
        if ($result === null) {
            $cachedResult = Mage::app()->loadCache(self::CACHE_PREFIX . $this->getDataCacheKey($key));
            if ($cachedResult) {
                $result = $cachedResult;
            }
        }
        return $result;
    }

    /**
     * set Cached Data for Magento cache
     * @param $key
     * @param $value
     * @return $this
     */
    public function setCachedDataSha($key, $value)
    {
        $this->setCachedData($key, $value);
        Mage::app()->saveCache($value, self::CACHE_PREFIX . $this->getDataCacheKey($key), array(self::CACHE_TAG), self::CACHE_TTL);
        return $this;
    }

    /**
     * Returns cache key for some request to carrier data service
     *
     * @param string|array $key
     * @return string
     */
    public function getDataCacheKey($key)
    {
        if (is_array($key)) {
            $key = implode(',',array_keys($key));
        }
        return crc32($key);
    }

    /**
     * Checks whether some request to rates have already been done, so we have cache for it
     * Used to reduce number of same requests done to carrier service during one session
     *
     * Returns cached response or null
     *
     * @param string|array $key
     * @return null|string
     */
    public function getCachedData($key)
    {
        $key = $this->getDataCacheKey($key);
        return isset(self::$_dataCache[$key]) ? self::$_dataCache[$key] : null;
    }

    /**
     * Sets received carrier data to cache
     *
     * @param string|array $key
     * @param string $value
     * @return Mage_Usa_Model_Shipping_Carrier_Abstract
     */
    public function setCachedData($key, $value)
    {
        $key = $this->getDataCacheKey($key);
        self::$_dataCache[$key] = $value;
        return $this;
    }
}