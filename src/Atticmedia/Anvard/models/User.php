<?php namespace Atticmedia\Anvard\Models;

use App\Models\User;

class User extends User {

    public function profiles()
    {
        return $this->hasMany('\\Atticmedia\\Anvard\\Models\\Profile');
    }
    
}
