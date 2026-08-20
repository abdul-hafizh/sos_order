<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 't_user';
    protected $primaryKey = 'id';
    public $timestamps = false; 

    protected $fillable = [
        'user', 'pwd', 'nama_user', 'list_menu', 'status',
        'type_user', 'kode_cabang', 'created_by', 'created_date',
        'modified_by', 'modified_date', 'idx', 'email', 'telegram_chat_id', 'is_admin'
    ];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    protected $appends = ['is_ho_user'];

    /**
     * Sumber kebenarannya adalah keanggotaan di view vw_admin_sos (bukan
     * membandingkan kode_cabang === 'GSOS' langsung), supaya frontend tidak
     * perlu tahu kode cabang GSOS itu sendiri.
     */
    public function getIsHoUserAttribute(): bool
    {
        return AdminSos::where('id', $this->id)->exists();
    }

    public function getAuthPassword()
    {
        return $this->pwd;
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
        // Table t_user does not have remember_token column
    }

    public function getRememberTokenName()
    {
        return '';
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'kode_cabang', 'kode_cabang');
    }
}