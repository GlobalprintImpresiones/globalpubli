<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedidos extends Model
{
    protected $primaryKey = 'id_pedido';
    use HasFactory;
    public $timestamps = false;

    // Definir la relación con la tabla "users"
    public function user()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    // Definir la relación con la tabla "empresas"
    public function empresa()
    {
        return $this->belongsTo(Empresas::class, 'id_empresa');
    }

    // Definir la relación con la tabla "categorias"
    public function categorias()
    {
        return $this->belongsToMany(Categorias::class, 'detalles', 'id_pedido', 'id_categoria');
    }
}
