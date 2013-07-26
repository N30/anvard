<?php namespace Atticmedia\Anvard\Models;

use App\Models\User as BaseUser;
use Config;

class User extends BaseUser {

    public function __construct(array $attributes = array())
    {
        parent::__construct();
        $this->table = Config::get('anvard::db.userstable');
    }


    public function profiles()
    {
        return $this->hasMany('\\Atticmedia\\Anvard\\Models\\Profile');
    }
    
}
