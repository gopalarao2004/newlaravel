<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogDetail extends Model
{
    protected $table = 'blog_details';

    protected $primaryKey = 'postid';

    public $incrementing = false;

    protected $fillable = [
        'longpara',
        'keywords',
        'image',
        'viewcount',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
        ];
    }

    public function blog(): BelongsTo
    {
        return $this->belongsTo(UserBlog::class, 'postid', 'postid');
    }
}

