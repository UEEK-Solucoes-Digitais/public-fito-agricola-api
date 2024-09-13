<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'admins';

    protected $primaryKey = 'id';

    protected $hidden = ['password', 'remember_token'];

    const ADMIN_ACCESS = 'admins,costs,contents,properties,interference_factors,inputs,crops,stocks,assets,reports,harvests';
    const CONSULTANT_AND_PRODUCER_ACCESS = 'admins,costs,contents,properties,interference_factors,inputs,crops,stocks,assets,reports';
    const MA_ACCESS = 'admins,contents,interference_factors,inputs';
    const TEAM_ACCESS = 'assets,interference_factors,inputs,contents,properties';

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'admin_id', 'id')->where("status", 1);
    }

    public function properties_many()
    {
        return $this->belongsToMany(Property::class, "admins_properties", "admin_id", "property_id");
    }

    public function tokens()
    {
        return $this->hasMany(AdminNotificationToken::class, 'admin_id', 'id');
    }

    public function all_properties()
    {
        // Obter os resultados das duas relações
        $has_many = $this->properties;
        $belongs_to_many = $this->properties_many;

        // Combinar os resultados em uma Collection
        $all_properties = $has_many->merge($belongs_to_many);

        return $all_properties;
    }

    public function all_properties_count()
    {
        // se for administrador, retorna 1 apenas para passar nas validações
        if ($this->access_level == 1) {
            return 1;
        }

        // Obter os resultados das duas relações
        $has_many = $this->properties;
        $belongs_to_many = $this->properties_many;

        // Combinar os resultados em uma Collection
        $all_properties = $has_many->merge($belongs_to_many);

        return $all_properties->count();
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'admin_id', 'id');
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
