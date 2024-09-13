<?php

namespace App\Models;

class ContentCommentLike extends BaseModel
{
    protected $table = 'content_comment_likes';

    public function comment()
    {
        return $this->belongsTo(ContentComment::class, 'content_comment_id', 'id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }
}
