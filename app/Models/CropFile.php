<?php

namespace App\Models;

use Illuminate\Http\Request;

class CropFile extends BaseModel
{
    protected $table = 'crops_files';

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

    public function crop()
    {
        return $this->belongsTo(Crop::class, 'crop_id', 'id');
    }

    public static function readCropFiles($admin_id, Request $request)
    {
        $crops_files = CropFile::with('crop')->where('status', 1)->orderBy("id", "desc");

        if ($admin_id) {
            $admin = Admin::find($admin_id);

            if ($admin->access_level != 1) {
                $crops_files->whereHas('crop', function ($q) use ($admin_id) {
                    $q->whereHas('property', function ($q) use ($admin_id) {
                        $q->where("admin_id", $admin_id)->orWhereHas("admins", function ($q) use ($admin_id) {
                            $q->where("admin_id", $admin_id);
                        });
                    });
                });
            }
        }

        if ($request->get("crop_id") && $request->get("crop_id") != 'null') {
            $crops_files->where('crop_id', $request->get("crop_id"));
        }

        $total = $crops_files->count();

        if ($request->get("page") && $request->get("page") != 'null') {
            $skip = ($request->get("page") - 1) * 40;
            $crops_files = $crops_files->skip($skip)->take(40)->get();
        } else {
            $crops_files = $crops_files->get();
        }

        return [$crops_files, $total];
    }

    public static function read($id)
    {
        $crop_file = CropFile::with('crop')->where('status', 1)->find($id);

        return $crop_file;
    }
}
