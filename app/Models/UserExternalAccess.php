<?php

namespace App\Models;

use App\Enums\UserExternalAccess\UserExternalAccessStatusEnum;
use Illuminate\Database\Eloquent\Model;

class UserExternalAccess extends Model
{
    protected $fillable = ['external_access_id', 'external_key', 'status'];

    protected $casts = [
        'status' => UserExternalAccessStatusEnum::class,
    ];

    public function externalAccessService()
    {
        return $this->belongsTo(ExternalAccessService::class, 'external_access_id');
    }
}
