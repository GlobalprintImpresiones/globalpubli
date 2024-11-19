<?php

namespace App\Http\Controllers;

use App\Models\Pedidos;
use App\Models\Empresas;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function pedidosDiaActual()
    {
        $fechaActual = now();

        $countPedidosActual = Pedidos::whereDate('fechaPedido', $fechaActual)->count();

        return response()->json(['ventas de hoy' => $countPedidosActual], Response::HTTP_OK);
    }


    public function pedidosDiaAnterior()
    {
        $fechaAnterior = now()->subDay();

        $countPedidosAnterior = Pedidos::whereDate('fechaPedido', $fechaAnterior)->count();

        return response()->json(['ventas de ayer' => $countPedidosAnterior], Response::HTTP_OK);
    }


    public function pedidosMesActual()
    {
        $fechaInicioMes = now()->startOfMonth();
        $fechaFinMes = now()->endOfMonth();

        $countPedidosMesActual = Pedidos::whereBetween('fechaPedido', [$fechaInicioMes, $fechaFinMes])->count();

        return response()->json(['ventas del mes actual' => $countPedidosMesActual], Response::HTTP_OK);
    }


    public function pedidosMesAnterior()
    {
        $fechaInicioMesAnterior = now()->subMonth()->startOfMonth();
        $fechaFinMesAnterior = now()->subMonth()->endOfMonth();

        $countPedidosMesAnterior = Pedidos::whereBetween('fechaPedido', [$fechaInicioMesAnterior, $fechaFinMesAnterior])->count();

        return response()->json(['ventas del mes anterior' => $countPedidosMesAnterior], Response::HTTP_OK);
    }


    public function clientesNuevosDiaActual()
    {
        $fechaActual = now()->toDateString();

        $clientesNuevosHoy = Empresas::whereDate('created_at', $fechaActual)->count();

        return response()->json(['clientes nuevos hoy' => $clientesNuevosHoy], Response::HTTP_OK);
    }


    public function clientesNuevosDiaAnterior()
    {
        $fechaAnterior = now()->subDay()->toDateString();

        $clientesNuevosAyer = Empresas::whereDate('created_at', $fechaAnterior)->count();

        return response()->json(['clientes nuevos ayer' => $clientesNuevosAyer], Response::HTTP_OK);
    }


    public function clientesNuevosMesActual()
    {
        $inicioMesActual = now()->startOfMonth()->toDateString();
        $finMesActual = now()->endOfMonth()->toDateString();

        $clientesNuevosMesActual = Empresas::whereBetween('created_at', [$inicioMesActual, $finMesActual])->count();

        return response()->json(['clientes nuevos este mes' => $clientesNuevosMesActual], Response::HTTP_OK);
    }


    public function clientesNuevosMesAnterior()
    {
        $inicioMesAnterior = now()->subMonth()->startOfMonth()->toDateString();
        $finMesAnterior = now()->subMonth()->endOfMonth()->toDateString();

        $clientesNuevosMesAnterior = Empresas::whereBetween('created_at', [$inicioMesAnterior, $finMesAnterior])->count();

        return response()->json(['clientes nuevos mes anterior' => $clientesNuevosMesAnterior], Response::HTTP_OK);
    }




    public function categoriasMasDemandadas()
    {
        $categoriasMasDemandadas = DB::table('detalles')
            ->join('categorias', 'detalles.id_categoria', '=', 'categorias.id_categoria')
            ->select('categorias.nombreCategoria', DB::raw('COUNT(detalles.id_categoria) as totalPedidos'))
            ->groupBy('categorias.nombreCategoria')
            ->orderByDesc('totalPedidos')
            ->get();

        return response()->json($categoriasMasDemandadas, Response::HTTP_OK);
    }
}
