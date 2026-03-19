<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryCustomer extends Model
{
    protected $fillable = ['name', 'email', 'registration_number'];

    public function departments()
    {
        return $this->hasMany(DeliveryDepartment::class)->orderBy('sort_order');
    }

    public function notes()
    {
        return $this->hasMany(DeliveryNote::class);
    }
}
