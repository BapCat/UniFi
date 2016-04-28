<?php namespace Cardless\UniFi;

use GuzzleHttp\Client;

class UniFiAuthenticator {
  private $client;
  private $base_url;
  
  public function __construct(Client $client, $base_url) {
    $this->client   = $client;
    $this->base_url = $base_url;
  }
  
  public function login() {
    $res = $this->client->get("{$this->base_url}/api/login", json_encode([
      'username' => Config::get('unifi::unifi.username'),
      'password' => Config::get('unifi::unifi.password'),
    ]));
  }
  
  public function logout() {
    $res = $this->client->post("{$this->base_url}/logout");
  }
}
