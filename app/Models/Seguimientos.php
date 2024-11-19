<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seguimientos extends Model
{
    protected $primaryKey = 'id_seguimiento';
    use HasFactory;

    // Definir la relación con la tabla "empresas"
    public function empresa()
    {
        return $this->belongsTo(Empresas::class, 'id_empresa');
    }

    // Definir la relación con la tabla "pedidos"
    public function pedido()
    {
        return $this->belongsTo(Pedidos::class, 'id_pedido');
    }
}
