<?php namespace BapCat\UniFi;

use BapCat\Interfaces\Ioc\Ioc;
use BapCat\Remodel\EntityDefinition;
use BapCat\Remodel\Registry;
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
    $site = new EntityDefinition(Site::class);
    $site->table('site');
    $site->required('_id', Text::class); // MONGOID
    $site->required('name', Text::class);
    $site->optional('key', Text::class);
    $site->optional('desc', Text::class);
    $site->required('attr_hidden_id', Text::class);
    $site->optional('attr_hidden', Text::class); // BOOL
    $site->optional('attr_no_delete', Text::class); // BOOL
    $site->optional('attr_no_edit', Text::class); // BOOL
    
    $this->remodel->register($site);
    
    $ap = new EntityDefinition(AccessPoint::class);
    $ap->table('device');
    $ap->required('_id', Text::class); // MONGOID
    $ap->required('ip', Ip::class);
    $ap->required('mac', Text::class); //MAC
    $ap->required('model', Text::class);
    $ap->required('type', Text::class);
    $ap->required('version', Text::class);
    $ap->required('adopted', Text::class); // BOOL
    $ap->required('site_id', Text::class); // MONGOID
    $ap->required('name', Text::class);
    $ap->required('serial', Text::class);
    
    $this->remodel->register($ap);
    
    $event = new EntityDefinition(Event::class);
    $event->table('event');
    $event->required('_id', Text::class); // MONGOID
    
    $this->remodel->register($event);
    
    $guest = new EntityDefinition(Guest::class);
    $guest->table('guest');
    $guest->required('_id', Text::class); // MONGOID
    $guest->required('site_id', Text::class); // MONGOID
    $guest->required('mac', Text::class); // MAC
    $guest->required('ap_mac', Text::class); // MAC
    $guest->required('start', Text::class); // Number
    $guest->required('end', Text::class); // Number
    $guest->required('duration', Text::class); // Number
    $guest->required('authorized_by', Text::class);
    $guest->required('unauthorized_by', Text::class);
    
    $this->remodel->register($guest);
    
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
}
