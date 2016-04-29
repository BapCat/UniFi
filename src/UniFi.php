<?php namespace BapCat\UniFi;

use BapCat\Interfaces\Ioc\Ioc;

use GuzzleHttp\Client;

class UniFi {
  private $ioc;
  
  public function __construct(Ioc $ioc) {
    $this->ioc = $ioc;
  }
  
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
}
