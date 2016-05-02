<?php namespace BapCat\UniFi;

use BapCat\Interfaces\Ioc\Ioc;
use BapCat\Remodel\EntityDefinition;
use BapCat\Remodel\Registry;
use BapCat\Remongel\MongoId;
use BapCat\Services\ServiceProvider;
use BapCat\Values\Ip;
use BapCat\Values\Text;
use Jenssegers\Mongodb\Connection;

class EntityProvider implements ServiceProvider {
  private $ioc;
  private $remodel;
  
  public function __construct(Ioc $ioc, Registry $remodel) {
    $this->ioc     = $ioc;
    $this->remodel = $remodel;
  }
  
  public function register() {
    $this->registerSite();
    $this->registerAccessPoint();
    $this->registerEvent();
    $this->registerGuest();
    
    //@TODO hardcoded values
    $this->ioc->bind(Connection::class, function() {
      $connection = new Connection([
        'driver'   => 'mongodb',
        'host'     => 'localhost',
        'port'     => 27117,
        'database' => 'ace',
        'username' => '',
        'password' => '',
        'options' => [
          'db' => 'admin' // sets the authentication database required by mongo 3
        ]
      ]);
      
      $connection->useDefaultQueryGrammar();
      return $connection;
    });
    
    $this->ioc->bind(SiteGateway::class, function() {
      return new SiteGateway($this->ioc->make(Connection::class));
    });
    
    $this->ioc->bind(AccessPointGateway::class, function() {
      return new AccessPointGateway($this->ioc->make(Connection::class));
    });
    
    $this->ioc->bind(EventGateway::class, function() {
      return new EventGateway($this->ioc->make(Connection::class));
    });
    
    $this->ioc->bind(GuestGateway::class, function() {
      return new GuestGateway($this->ioc->make(Connection::class));
    });
  }
  
  public function boot() {
    
  }
  
  private function registerSite() {
    $site = new EntityDefinition(Site::class);
    $site->table('site');
    $site->required('_id', MongoId::class);
    $site->required('name', Text::class);
    $site->optional('key', Text::class);
    $site->optional('desc', Text::class);
    $site->required('attr_hidden_id', Text::class);
    $site->optional('attr_hidden', Text::class); // BOOL
    $site->optional('attr_no_delete', Text::class); // BOOL
    $site->optional('attr_no_edit', Text::class); // BOOL
    
    $this->remodel->register($site);
  }
  
  private function registerAccessPoint() {
    $ap = new EntityDefinition(AccessPoint::class);
    $ap->table('device');
    $ap->required('_id', MongoId::class);
    $ap->required('ip', Ip::class);
    $ap->required('mac', Text::class); //MAC
    $ap->required('model', Text::class);
    $ap->required('type', Text::class);
    $ap->required('version', Text::class);
    $ap->required('adopted', Text::class); // BOOL
    $ap->required('site_id', MongoId::class);
    $ap->required('name', Text::class);
    $ap->required('serial', Text::class);
    
    $this->remodel->register($ap);
  }
  
  private function registerEvent() {
    $events = [
      'apAdopted'               => 'EVT_AP_Adopted',
      'apConnected'             => 'EVT_AP_Connected',
      'apLostContact'           => 'EVT_AP_Lost_Contact',
      'apRestartedUnknown'      => 'EVT_AP_RestartedUnknown',
      'apUpgradeScheduled'      => 'EVT_AP_UpgradeScheduled',
      'apUpgraded'              => 'EVT_AP_Upgraded',
      'guestAuthorized'         => 'EVT_AD_GuestAuthorizedFor',
      'guestUnauthorized'       => 'EVT_AD_GuestUnauthorized',
      'guestConnected'          => 'EVT_WG_Connected',
      'guestDisconnected'       => 'EVT_WG_Disconnected',
      'guestAuthorizationEnded' => 'EVT_WG_AuthorizationEnded',
      'userConnected'           => 'EVT_WU_Connected',
      'userDisconnected'        => 'EVT_WU_Disconnected'
    ];
    
    $event = new EntityDefinition(Event::class);
    $event->table('event');
    $event->required('_id', MongoId::class);
    
    foreach($events as $name => $key) {
      $event->scope($name, function($query) use($key) {
        return $query->where('key', $key);
      });
      
      $event->scope('or' . ucfirst($name), function($query) use($key) {
        return $query->orWhere('key', $key);
      });
    }
    
    $event->scope('since', function($query, $id) {
      return $query->where('_id', '>', $id);
    });
    
    $event->scope('forGuest', function($query, $guest) { // MAC
      return $query->where('guest', $guest);
    });
    
    $event->scope('latest', function($query) {
      return $query->orderBy('_id', 'desc');
    });
    
    $this->remodel->register($event);
  }
  
  private function registerGuest() {
    $guest = new EntityDefinition(Guest::class);
    $guest->table('guest');
    $guest->required('_id', MongoId::class);
    $guest->required('site_id', MongoId::class);
    $guest->required('mac', Text::class); // MAC
    $guest->required('ap_mac', Text::class); // MAC
    $guest->required('start', Text::class); // Number
    $guest->required('end', Text::class); // Number
    $guest->required('duration', Text::class); // Number
    $guest->required('authorized_by', Text::class);
    $guest->required('unauthorized_by', Text::class);
    
    $guest->scope('authed', function($query) {
      //TODO: Figure out MongoDB-local timestamp function
      //TODO: Auth time needs to be site-dependent
      return $query
        ->whereNull('unauthorized_by')
        ->where('end', '>', time() - 120 * 60)
      ;
    });
    
    $this->remodel->register($guest);
  }
}
