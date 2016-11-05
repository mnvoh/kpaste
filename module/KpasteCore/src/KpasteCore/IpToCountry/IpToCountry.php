<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    IpToCountry.php
 * @createdat   Jul 21, 2013 3:59:00 PM
 */

namespace KpasteCore\IpToCountry;

use Zend\Session\Container as SessionContainer;
use KpasteCore\Model\Ip2Country;

class IpToCountry
{
    protected $rawIpv4Address;
    protected $numericalIpv4Address;
    protected $ipInfo;
    protected $serviceLocator;
    
    public function __construct($serviceLocator, $ip = null) 
    {
        $this->serviceLocator = $serviceLocator;
        
        if($ip)     $this->setIp($ip);
    }
    
    public function setIp($ip)
    {
        if(!preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $ip)) {
            $this->ipInfo = new Ip2Country();
            $this->ipInfo->exchangeArray(array(
                'ctry'      => 'ZZ',
                'country'   => 'UNKNOWN',
            ));
            return;
        }
        
        $parts = explode('.', $ip);
        $multiplier = 256 * 256 * 256;
        $this->numericalIpv4Address = 0;
        foreach($parts as $part)
        {
            $temp = (int)$part;
            if($temp < 0 || $temp > 255) {
                $this->ipInfo = new Ip2Country();
                $this->ipInfo->exchangeArray(array(
                    'ctry'      => 'ZZ',
                    'country'   => 'UNKNOWN',
                ));
                return;
            }
            
            $this->numericalIpv4Address += $multiplier * $temp;
            $multiplier /= 256;
        }
        $this->rawIpv4Address = $ip;
        
        $this->fetchIpData();
        
        return $this;
    }

    public function getRawIpv4Address() 
    {
        return $this->rawIpv4Address;
    }
    
    public function getNumericalIpv4Address() 
    {
        return $this->numericalIpv4Address;
    }
    
    public function getFlagPath()
    {
        $settings = new SessionContainer('settings');
        $siteUrl = $settings->settings['site-url'];
        return $siteUrl . "/images/country-flags/{$this->ipInfo->ctry}.png";
    }
    
    public function getShortName()
    {
        return $this->ipInfo->ctry;
    }
    
    public function getLongName()
    {
        return $this->ipInfo->country;
    }
    
    private function fetchIpData()
    {
        $ipToCountryTable = $this->serviceLocator->get('KpasteCore\Model\Ip2CountryTable');
        $this->ipInfo = $ipToCountryTable->fetchIpInfo($this->numericalIpv4Address);
        if(!$this->ipInfo)
        {
            $this->ipInfo = new Ip2Country();
            $this->ipInfo->exchangeArray(array(
                'ctry'      => 'ZZ',
                'country'   => 'UNKNOWN',
            ));
        }
    }
}

?>
