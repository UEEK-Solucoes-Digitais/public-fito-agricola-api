<?php

namespace App\Models;

class ContentComment extends BaseModel
{
    protected $table = 'content_comments';

    public  function answers()
    {
        return $this->hasMany(ContentComment::class, 'answer_id', 'id')->with(['admin', 'answers'])->withCount('likes')->where('status', 1)->orderBy("id", "ASC");
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    public function likes()
    {
        return $this->hasMany(ContentCommentLike::class, 'content_comment_id', 'id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    public static function readComments($content_id)
    {
        $itens = self::with(['answers', 'admin'])->withCount('likes')->where('status', 1)->where("content_id", $content_id)->orderBy("id", "DESC")->get();

        return $itens;
    }

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($item) {
            createLogSystem($item->id, self::getTableName(), 1);
        });

        static::updated(function ($item) {
            createLogSystem($item->id, self::getTableName(), 2, $item->getOriginal(), $item->getDirty());
        });
    }
}
