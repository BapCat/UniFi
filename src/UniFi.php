<?php namespace BapCat\UniFi;

use BapCat\Interfaces\Ioc\Ioc;
use BapCat\Values\Text;

use GuzzleHttp\Client;

class UniFi {
  private $ioc;
  
  public function __construct(Ioc $ioc) {
    $this->ioc = $ioc;
  }
  
  /**
   * @TODO base_uri
   */
  public function login($username, $password, callable $callback) {
    $client = new Client([
      'base_uri' => 'https://srnc.playat.ch:8443',
      'cookies'  => true,
      'verify'   => false
    ]);
    
    $authenticator = $this->ioc->make(Authenticator::class, [$client]);
    $authenticator->login($username, $password);
    $callback(new Actions($client));
    $authenticator->logout();
  }
  
  /**
   * @TODO This needs some Entity love once that's possible with Mongo
   */
  public function isAuthedByAp($guest_mac, $ap_mac) {
    $guest_gateway = $this->ioc->make(GuestGateway::class);
    
    //@TODO: Do time stuff at DB level
    $guests = $guest_gateway->query()->whereNull('unauthorized_by')->where('end', '>', time() - 120 * 60)->where('mac', $guest_mac)->get();
    
    foreach($guests as $guest) {
      if($guest['ap_mac'] === $ap_mac) {
        return true;
      }
    }
    
    return false;
  }
  
  /**
   * @TODO This needs some Entity love once that's possible with Mongo
   */
  public function isAuthedBySite($guest_mac, $site_name) {
    $guest_gateway = $this->ioc->make(GuestGateway::class);
    $site_gateway  = $this->ioc->make(SiteGateway::class);
    
    //@TODO: Do time stuff at DB level
    $guests = $guest_gateway->query()->whereNull('unauthorized_by')->where('end', '>', time() - 120 * 60)->where('mac', $guest_mac)->get();
    
    foreach($guests as $guest) {
      $site = $site_gateway->query()->where('_id', $guest['site_id'])->get();
      $site = array_shift($site);
      
      if($site['name'] === $site_name) {
        return true;
      }
    }
    
    return false;
  }
  
  /**
   * @TODO This needs some Entity love once that's possible with Mongo
   */
  public function isAtSite($mac, $site_name) {
    $events = $this->ioc->make(EventGateway::class);
    $sites  = $this->ioc->make(SiteGateway::class);
    
    $connect = $events->query()->where('key', 'EVT_WG_Connected')->where('guest', $mac)->orderBy('_id', 'desc')->get();
    $connect = array_shift($connect);
    
    if($connect !== null) {
      $disconnect = $events->query()->where('key', 'EVT_WG_Disconnected')->where('guest', $mac)->where('_id', '>', $connect['_id'])->get();
      $disconnect = array_shift($disconnect);
      
      if($disconnect === null) {
        $site = $sites->query()->where('_id', $connect['site_id'])->get();
        $site = array_shift($site);
        
        if($site['name'] === $site_name) {
          return true;
        }
      }
    }
    
    return false;
  }
}
