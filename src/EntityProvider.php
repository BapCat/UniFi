<?php namespace BapCat\UniFi;

use BapCat\Remodel\EntityDefinition;
use BapCat\Remodel\Registry;
use BapCat\Services\ServiceProvider;
use BapCat\Values\Ip;
use BapCat\Values\Text;

class EntityProvider implements ServiceProvider {
  private $remodel;
  
  public function __construct(Registry $remodel) {
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
  }
  
  public function boot() {
    
  }
}
