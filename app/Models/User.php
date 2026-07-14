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
        'modified_by', 'modified_date', 'idx', 'email', 'telegram_chat_id'
    ];

    public function getAuthPassword()
    {
        return $this->pwd;
    }
}