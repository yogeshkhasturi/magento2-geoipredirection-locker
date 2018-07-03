<?php
/**
 * Copyright © Yogesh Khasturi. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace YogeshKhasturi\GeoIpRedirection\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    const XML_PATH_ENABLED = 'yogeshkhasturi_geoip/yogeshkhasturi_geo_lock/enable';
    const XML_PATH_ENABLED_REDIRECTION = 'yogeshkhasturi_geoip/yogeshkhasturi_geo_redirection/enable_geo_redirection';
    const XML_PATH_COUNTRIES = 'yogeshkhasturi_geoip/yogeshkhasturi_geo_lock/countries';
    const XML_PATH_IP_BLACK_LIST = 'yogeshkhasturi_geoip/yogeshkhasturi_geo_lock/ip_black_list';
    const XML_PATH_API_KEY = 'yogeshkhasturi_geoip/yogeshkhasturi_geo_lock/api_key_ipstack';
    const IP_LIST_REGEXP_DELIMITER = '/[\r?\n]+|[,?]+/';


    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isRedirectionEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED_REDIRECTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param null $storeId
     * @return array|null
     */
    public function getCountries($storeId = null)
    {
        $countriesRawValue = $this->scopeConfig->getValue(
            self::XML_PATH_COUNTRIES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $countriesRawValue = $this->prepareCode($countriesRawValue);
        if ($countriesRawValue) {
            $countriesCode = explode(',', $countriesRawValue);

            return $countriesCode;
        }

        return $countriesRawValue ? $countriesRawValue : null;
    }


    /**
     * @param null $storeId
     * @return array
     */
    public function getIpBlackList($storeId = null)
    {
        $rawIpList = $this->scopeConfig->getValue(
            self::XML_PATH_IP_BLACK_LIST,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $ipList = array_filter((array) preg_split(self::IP_LIST_REGEXP_DELIMITER, $rawIpList));

        return $ipList;
    }


    /**
     * Changes country code to upper case
     *
     * @param string $countryCode
     * @return string
     */
    public function prepareCode($countryCode)
    {
        return strtoupper(trim($countryCode));
    }

    /**
     * Get API Key From Config
     *
     * @return string 
    */

    public function getAccessApiKey($storeId = null)
    {
        $api_key = $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $api_key;
    }


    function getUserIpAddr(){
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $ipArr = explode(',', $ip);
        $ip = $ipArr[count($ipArr) - 1];

        return trim($ip);
    }
    
}
