<?php

namespace App\Models;

class PropertyManagementDataInput extends BaseModel
{
    protected $table = 'properties_management_data_inputs';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function property_crop()
    {
        return $this->belongsTo(PropertyCropJoin::class, "properties_crops_id", "id");
    }

    public function product()
    {
        return $this->belongsTo(Product::class, "product_id", "id");
    }

    public function system_log()
    {
        return $this->hasOne(LogSystem::class, 'object_id', 'id')->where('table_name', self::getTableName())->where('operation', 1)->with('admin');
    }

    public static function readDataInputs()
    {

        $itens = PropertyManagementDataInput::where('status', 1)->orderBy("id", "DESC");

        return $itens->get();
    }

    public static function readDataInput($id)
    {
        return PropertyManagementDataInput::where('status', 1)->find($id);
    }

    public static function readDataInputByCropJoin($property_crop_join_id, $type)
    {
        $itens = PropertyManagementDataInput::with('system_log')->with('product')
            ->where('type', $type)
            ->where('status', 1)
            ->where('properties_crops_id', $property_crop_join_id)
            ->orderBy('date', 'desc')
            ->get();

        if ($type == 2) {
            $group = $itens->groupBy("date")->sortBy(function ($item, $key) {
                return $key;
            });

            $itens->each(function ($item) use ($group) {
                $item->application_number = array_search($item->date, array_keys($group->toArray())) + 1;
            });
        }

        return $itens;
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
