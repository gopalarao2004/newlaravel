<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInformation extends Model
{
    protected $table = 'user_information';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'address',
        'phone_no',
        'nickname',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}