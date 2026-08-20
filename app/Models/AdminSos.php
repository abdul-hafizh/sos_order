<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bound to the vw_admin_sos database view (SELECT * FROM t_user WHERE
 * kode_cabang = 'GSOS'). Every place in the app that needs to know "who is
 * a GSOS user/admin" should query through this model instead of filtering
 * t_user by kode_cabang = 'GSOS' directly - that way, if the branch code
 * for GSOS ever changes, only the vw_admin_sos view needs to be updated in
 * the database, not the application code.
 */
class AdminSos extends Model
{
    protected $table = 'vw_admin_sos';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $casts = [
        'is_admin' => 'boolean',
    ];
}
