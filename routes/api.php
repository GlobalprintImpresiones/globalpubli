<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresasController;
use App\Http\Controllers\PedidosController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\SeguimientosController;
use Illuminate\Support\Facades\Route;


Route::post('login', [AuthController::class, 'login']);


Route::group(['middleware' => ['auth:sanctum']], function () {

    //USUARIOS
    Route::apiResource('usuarios', AuthController::class);

    // EMPRESAS
    Route::apiResource('empresas', EmpresasController::class);
    Route::post('empresas/bloquearDesbloquear/{id}', [EmpresasController::class, 'bloquearDesbloquear']);

    //CATEGORIAS
    Route::apiResource('categorias', CategoriasController::class);

    //PEDIDOS
    Route::apiResource('pedidos', PedidosController::class);
    Route::post('estadoPedido/{id}', [PedidosController::class, 'estadoPedido']);
    Route::post('pdf/{id}', [PedidosController::class, 'pdf']);
    Route::get('pedidos/empresa/{id}', [PedidosController::class, 'pedidosPorEmpresa']);
    Route::post('pedidos/empresaYpedido', [PedidosController::class, 'storeEmpresaYPedido']);
    Route::post('pedidos/sumarAnticipo/{id}', [PedidosController::class, 'sumarAnticipo']);

    // SEGUIMIENTOS
    Route::apiResource('seguimientos', SeguimientosController::class);
    Route::get('notificaciones', [SeguimientosController::class, 'notificaciones']);

    // DASHBOARD
    Route::get('pedidosDiaActual', [DashboardController::class, 'pedidosDiaActual']);
    Route::get('pedidosDiaAnterior', [DashboardController::class, 'pedidosDiaAnterior']);
    Route::get('pedidosMesActual', [DashboardController::class, 'pedidosMesActual']);
    Route::get('pedidosMesAnterior', [DashboardController::class, 'pedidosMesAnterior']);
    Route::get('clientesNuevosDiaActual', [DashboardController::class, 'clientesNuevosDiaActual']);
    Route::get('clientesNuevosDiaAnterior', [DashboardController::class, 'clientesNuevosDiaAnterior']);
    Route::get('clientesNuevosMesActual', [DashboardController::class, 'clientesNuevosMesActual']);
    Route::get('clientesNuevosMesAnterior', [DashboardController::class, 'clientesNuevosMesAnterior']);
    Route::get('categoriasMasDemandadas', [DashboardController::class, 'categoriasMasDemandadas']);

    Route::post('logout', [AuthController::class, 'logout']);
});
