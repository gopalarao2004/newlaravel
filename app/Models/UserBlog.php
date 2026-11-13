<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserBlog extends Model
{
    protected $table = 'user_blogs';

    protected $primaryKey = 'postid';

    protected $fillable = [
        'userid',
        'title',
        'shortdesc',
    ];

    public function blogs(): HasMany
    {
        return $this->hasMany(UserBlog::class, 'userid', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid', 'id');
    }

    public function details(): HasOne
    {
        return $this->hasOne(BlogDetail::class, 'postid', 'postid');
    }
}

