<?php
/**
 * Copyright © Yogesh Khasturi. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace YogeshKhasturi\GeoIpRedirection\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Locker implements ObserverInterface
{
    /**
     * @var \MageWorx\GeoLock\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\ResponseFactory
     */
    private $responseFactory;

     /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $actionFlag;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * @var bool
     */
    protected $isDenied = false;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManagerInterface;


    public function __construct(
        \YogeshKhasturi\GeoIpRedirection\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->helper = $helper;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->actionFlag = $actionFlag;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        // $CurruntIpAddress = '184.154.75.197';
        $CurruntIpAddress = $this->helper->getUserIpAddr();
        if (!$this->helper->isEnabled()) {
            return $this;
        }

        if ($this->request->isAjax()) {
            return $this;
        }
        $countries = $this->helper->getCountries();
        if (!$countries || empty($countries)) {
            return $this;
        }
        $curruntCountryCode = $this->getCurruntCountryCode($CurruntIpAddress);
        $checkByIpList = $this->detectByIpList($CurruntIpAddress);
        if ($curruntCountryCode && $countries && $this->helper->isEnabled()) {
            if (in_array($curruntCountryCode, $countries))
            {
                $this->denyCustomerAccess($observer);
            }            
        }
        
        if ($this->isDenied === true) {
            $this->denyCustomerAccess($observer);
        } 
        // Geo Store Redirection From IP

        // echo $this->_storeManagerInterface->getStore()->getName(2);
        if ($this->helper->isRedirectionEnabled()) {
        $storeManagerDataList =$this->_storeManagerInterface->getStores();

            foreach ($storeManagerDataList as $key => $value) {
                if (strtoupper($value['code'])===strtoupper($curruntCountryCode)) 
                {
                    $this->_storeManagerInterface->setCurrentStore($curruntCountryCode);
                }
            }
        }

        // End Store Redirection From IP
        return $this;
        
    }    
    
    function getCurruntCountryCode($ip)
    {
        // $ip =  '47.254.22.115';
        // $json_data = file_get_contents("http://apinotes.com/ipaddress/ip.php?ip=$ip");
        // $url = 'http://apinotes.com/ipaddress/ip.php?ip='.$ip;
        $api_access_key = $this->helper->getAccessApiKey();
        $url = 'http://api.ipstack.com/'.$ip.'?access_key='.$api_access_key;
        $ip_data = json_decode($this->curl_get_contents($url), TRUE);
        if (isset($ip_data['country_code'])) { 

            if ($ip_data['country_code'] != '') {
                return $ip_data['country_code'];
            } else {
                return FALSE;
            }
        }
    }

    function curl_get_contents($url)
    {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      $data = curl_exec($ch);
      curl_close($ch);
      return $data;
    }
    protected function denyCustomerAccess($observer)
    {
        /** @var \Magento\Framework\App\Action\Action $action */
        $action = $observer->getControllerAction();
        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $action->getResponse();

        $response->clearBody()
            ->setStatusCode(\Magento\Framework\App\Response\Http::STATUS_CODE_403);
        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
    }

    protected function detectByIpList($customerIp)
    {

        $ipBlackList = $this->helper->getIpBlackList();
        if ($ipBlackList) {
            foreach ($ipBlackList as $ip) {

                if ($ip===$customerIp) {
                   $this->isDenied = true;
                   break;
                }
            }
        }
        return $this->isDenied;
    }
}