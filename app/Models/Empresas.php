<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresas extends Model
{
    protected $primaryKey = 'id_empresa';
    use HasFactory;

    // Definir la relación con la tabla "seguimientos"
    public function seguimientos()
    {
        return $this->hasMany(Seguimientos::class, 'id_empresa');
    }
}
