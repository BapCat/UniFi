<?php namespace BapCat\UniFi;

use GuzzleHttp\Client;

class Actions {
  private $client;
  
  public function __construct(Client $client) {
    $this->client = $client;
  }
  
  public function staStats($site) {
    return $this->client->get("/api/s/$site/stat/sta");
  }
  
  public function apStats($site) {
    return $this->client->get("/api/s/$site/stat/device");
  }
  
  public function authorize($site, $mac, $minutes, $up = null, $down = null, $bytes = null) {
    $data = [
      'cmd'     => 'authorize-guest',
      'mac'     => $mac,
      'minutes' => $minutes
    ];
    
    if($up    != null) { $data['up']    = $up;    }
    if($down  != null) { $data['down']  = $down;  }
    if($bytes != null) { $data['bytes'] = $bytes; }
    
    return $this->client->post("/api/s/$site/cmd/stamgr", ['json' => $data]);
  }
  
  public function unauthorize($site, $mac) {
    $data = [
      'cmd'     => 'unauthorize-guest',
      'mac'     => $mac
    ];
    
    return $this->client->post("/api/s/$site/cmd/stamgr", ['json' => $data]);
  }
  
  public function deauthorize($site, $mac) {
    $data = [
      'cmd' => 'unauthorize-guest',
      'mac' => $mac
    ];
    
    return $this->client->post("/api/s/$site/cmd/stamgr", ['json' => $data]);
  }
}
