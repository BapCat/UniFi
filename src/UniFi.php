<?php namespace BapCat\UniFi;

use BapCat\Interfaces\Ioc\Ioc;
use BapCat\Values\Text;

use GuzzleHttp\Client;

class UniFi {
  private $ioc;
  private $client;
  
  public function __construct(Ioc $ioc, Client $client) {
    $this->ioc    = $ioc;
    $this->client = $client;
  }
  
  public function login($username, $password, callable $callback) {
    $authenticator = $this->ioc->make(Authenticator::class, [$this->client]);
    $authenticator->login($username, $password);
    $callback($this->ioc->make(Actions::class, [$this->client]));
    $authenticator->logout();
  }
  
  /**
   * @TODO This needs some Entity love once that's possible with Mongo
   */
  public function isAuthedByAp($guest_mac, $ap_mac) {
    $guest_gateway = $this->ioc->make(GuestGateway::class);
    
    $guests = $guest_gateway->query()->authed()->where('mac', $guest_mac)->get();
    
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
    
    $guests = $guest_gateway->query()->authed()->where('mac', $guest_mac)->get();
    
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
    
    $connect = $events->query()->guestConnected()->forGuest($mac)->latest()->get();
    $connect = array_shift($connect);
    
    if($connect !== null) {
      $disconnect = $events->query()->guestDisconnected()->forGuest($mac)->since($connect['_id'])->get();
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
