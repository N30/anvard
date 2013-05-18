<?php namespace Atticmedia\Anvard\Models;

use Eloquent;

class Profile extends Eloquent {
    
    public function user()
    {
        return $this->belongsTo('\\Atticmedia\\Anvard\\Models\\User');
    }
    
}
