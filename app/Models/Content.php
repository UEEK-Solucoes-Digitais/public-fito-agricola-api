<?php

namespace App\Models;

class Content extends BaseModel
{
    protected $table = 'contents';

    public function admin()
    {
        return $this->belongsTo(Admin::class, "admin_id", "id");
    }

    // public function category()
    // {
    //     return $this->belongsTo(ContentCategory::class, "category_id", "id");
    // }

    public function blocks()
    {
        return $this->hasMany(ContentBlock::class, "content_id", "id")->orderBy('position', 'asc')->where('status', 1)->with("images");
    }

    public function comments()
    {
        return $this->hasMany(ContentComment::class, 'content_id', 'id')->with(['answers', 'admin'])->withCount('likes')->where("answer_id", null)->where('status', 1)->orderBy("id", "DESC");
    }

    public function videos()
    {
        return $this->hasMany(ContentVideo::class, 'content_id', 'id')->with('watched_contents')->where('status', 1)->orderBy("id", "ASC");
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

    public static function readContents($filter, $page, $admin_id, $content_type, $filter_courses = [], $filter_courses_function = false)
    {
        $admin = Admin::find($admin_id);

        $itens = self::where('status', 1)->where('content_type', $content_type)->with(['admin', 'blocks'])->orderBy("created_at", "DESC");

        if ($admin && $admin->access_level != 1) {
            if ($filter_courses_function) {
                $itens->whereNotIn('id', $filter_courses ?? []);
            } else {
                $itens->where(function ($q) use ($admin) {
                    $all_properties = $admin->all_properties();

                    $q->where(function ($q) use ($admin) {
                        $q->where('cities', null)->orWhere(function ($q) use ($admin) {
                            $q->where('cities', '!=', null)
                                ->when($admin->city, function ($query) use ($admin) {
                                    $query->where('cities', 'LIKE', "%{$admin->city}%");
                                }, function ($query) {
                                    $query->whereRaw('1 = 0'); // Esse bloco só será executado se $admin->city for nulo
                                });
                        });
                    })->where(function ($q) use ($admin) {
                        $q->where('countries', null)->orWhere(function ($q) use ($admin) {
                            $q->where('countries', '!=', null)
                                ->when($admin->country, function ($query) use ($admin) {
                                    $query->where('countries', 'LIKE', "%{$admin->country}%");
                                }, function ($query) {
                                    $query->whereRaw('1 = 0'); // Esse bloco só será executado se $admin->country for nulo
                                });
                        });
                    })->where(function ($q) use ($admin) {
                        $q->where('states', null)->orWhere(function ($q) use ($admin) {
                            $q->where('states', '!=', null)
                                ->when($admin->state, function ($query) use ($admin) {
                                    $query->where('states', 'LIKE', "%{$admin->state}%");
                                }, function ($query) {
                                    $query->whereRaw('1 = 0'); // Esse bloco só será executado se $admin->state for nulo
                                });
                        });
                    })->where(function ($q) use ($all_properties) {
                        $q->where('properties_ids', null)->orWhere(function ($q) use ($all_properties) {
                            $q->where('properties_ids', '!=', null)->whereIn('properties_ids', $all_properties->pluck('id')->toArray());
                        });
                    })->where(function ($q) use ($admin) {
                        $q->where('access_level', null)->orWhere(function ($q) use ($admin) {
                            $q->where('access_level', '!=', null)
                                ->when($admin->access_level, function ($query) use ($admin) {
                                    $query->where('access_level', 'LIKE', "%{$admin->access_level}%");
                                }, function ($query) {
                                    $query->whereRaw('1 = 0'); // Esse bloco só será executado se $admin->access_level for nulo
                                });
                        });
                    })->where(function ($q) use ($admin) {
                        $q->where('admins_ids', null)->orWhere(function ($q) use ($admin) {
                            $q->where('admins_ids', '!=', null)
                                ->when($admin->id, function ($query) use ($admin) {
                                    $query->where('admins_ids', 'LIKE', "%{$admin->id}%");
                                }, function ($query) {
                                    $query->whereRaw('1 = 0'); // Esse bloco só será executado se $admin->id for nulo
                                });
                        });
                    });
                });
            }
        }

        $total = $itens->count();

        if ($page) {
            $skip = ($page - 1) * 10;
            $itens = $itens->skip($skip)->take(10)->get();
        } else {
            $itens = $itens->get();
        }
        $itens->map(function ($item) use ($admin, $filter_courses_function) {
            $watched_videos = AdminWatchedContent::where('admin_id', $admin->id)->whereIn('content_video_id', $item->videos->pluck('id'))->get();
            $finished_videos = $watched_videos->where('is_finished', 1)->values();

            $item->is_liked = AdminContentInteractions::where('admin_id', $admin->id)->where('content_id', $item->id)->where('is_liked', 1)->count();
            $item->is_saved = AdminContentInteractions::where('admin_id', $admin->id)->where('content_id', $item->id)->where('is_saved', 1)->count();
            $item->is_watching = $watched_videos->count() > 0 && $finished_videos->count() < $item->videos->count() ? 1 : 0;

            if ($watched_videos->first()) {
                $item->watched_seconds = $watched_videos->first() ? $watched_videos->first()->last_second : '';
                $item->video_seconds = $item->videos->where('id', $watched_videos->first()->content_video_id)->count() ? $item->videos->where('id', $watched_videos->first()->content_video_id)->first()->duration_time : '';
            } else {
                $item->watched_seconds = '00:00:00';
                $item->video_seconds = $item->videos->count() ? $item->videos->first()->duration_time : '00:00:00';
            }
            $item->count_finished_user = $finished_videos->count();
            $item->count_finished = AdminWatchedContent::where('is_finished', 1)->whereIn('content_video_id', $item->videos->pluck('id'))->count();
            $item->count_videos = $item->videos->count();

            // if ($item->is_course == 1) {
            $item->is_available = $filter_courses_function ? 0 : 1;
            // }

            $item->categories_ids = explode(",", $item->categories_ids);

            return $item;
        });

        return [$itens, $total];
    }

    public static function readContent($url, $admin_id)
    {
        $item = self::where('url', $url)->with(['admin', 'blocks', 'comments', 'videos'])->first();

        $item->is_liked = AdminContentInteractions::where('admin_id', $admin_id)->where('content_id', $item->id)->where('is_liked', 1)->count();
        $item->is_saved = AdminContentInteractions::where('admin_id', $admin_id)->where('content_id', $item->id)->where('is_saved', 1)->count();
        $watched_videos = AdminWatchedContent::where('admin_id', $admin_id)->whereIn('content_video_id', $item->videos->pluck('id'))->get();
        $item->current_video = $watched_videos->where('is_finished', 0)->first() ? $watched_videos->where('is_finished', 0)->first()->content_video_id : null;

        $item->videos->map(function ($video) use ($admin_id) {
            $video->watched_seconds = AdminWatchedContent::where('admin_id', $admin_id)->where('content_video_id', $video->id)->first() ? AdminWatchedContent::where('admin_id', $admin_id)->where('content_video_id', $video->id)->first()->last_second : '';
            $video->is_finished = AdminWatchedContent::where('admin_id', $admin_id)->where('content_video_id', $video->id)->where('is_finished', 1)->count();
            return $video;
        });

        $item->comments->map(function ($comment) use ($admin_id) {
            $comment->is_liked = ContentCommentLike::where('admin_id', $admin_id)->where('content_comment_id', $comment->id)->count();

            $comment->answers->map(function ($answer) use ($admin_id) {
                $answer->is_liked = ContentCommentLike::where('admin_id', $admin_id)->where('content_comment_id', $answer->id)->count();
                return $answer;
            });
            return $comment;
        });

        return $item;
    }
}
