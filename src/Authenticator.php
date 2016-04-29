<?php namespace BapCat\UniFi;

use GuzzleHttp\Client;

class Authenticator {
  private $client;
  
  public function __construct(Client $client) {
    $this->client = $client;
  }
  
  public function login($username, $password) {
    return $this->client->post('/api/login', [
      'json' => [
        'username' => $username,
        'password' => $password
      ]
    ]);
  }
  
  public function logout() {
    return $this->client->post('/logout');
  }
}
