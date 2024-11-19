<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categorias extends Model
{
    protected $primaryKey = 'id_categoria';
    use HasFactory;

    // Definir la relacion con la tabla "pedidos"
    public function pedido()
    {
        return $this->belongsToMany(Pedidos::class, "detalles", "id_categoria", "id_pedido");
    }
}
