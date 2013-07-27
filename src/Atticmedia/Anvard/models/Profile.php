<?php namespace Atticmedia\Anvard\Models;

use Eloquent;
use Config;

class Profile extends Eloquent
{
    public function __construct(array $attributes = array())
    {
        parent::__construct();
        $this->table = Config::get('anvard::db.profilestable');
    }

    public function user()
    {
        return $this->belongsTo('\\Atticmedia\\Anvard\\Models\\User');
    }
}
