<?php

namespace App\Http\Controllers;

use App\Models\Empresas;
use App\Models\Pedidos;
use App\Models\Seguimientos;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

class SeguimientosController extends Controller
{
    //AGREGAR
    public function store(Request $request)
    {
        // VALIDACION DE CAMPOS
        $validator = Validator::make($request->all(), [
            'nombreEmpresa' => ['required', 'string', 'exists:empresas,nombreEmpresa'],
            'idPedido' => ['nullable', 'integer', 'exists:pedidos,id_pedido'],
            'condiciones' => ['nullable', 'string'],
            'historia' => ['required', 'string'],
        ], [
            'nombreEmpresa.exists' => 'El nombre de la empresa no existe.',
            'idPedido.exists' => 'El pedido no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $empresa = Empresas::where('nombreEmpresa', $request->nombreEmpresa)->first();

        // Verificar si la empresa está bloqueada o no
        if ($empresa->estadoEmpresa == 0) {
            return response()->json(['error' => 'La empresa está bloqueada y no se pueden realizar seguimientos.'], Response::HTTP_FORBIDDEN);
        }

        // Inicializa el pedido como null
        $pedido = null;

        // Solo busca el pedido si el ID no es null
        if ($request->idPedido !== null) {
            $pedido = Pedidos::where('id_pedido', $request->idPedido)->first();
        }

        $seguimiento = new Seguimientos();
        $seguimiento->id_empresa = $empresa->id_empresa;
        $seguimiento->id_pedido = $pedido ? $pedido->id_pedido : null; // Maneja el caso donde $pedido es null
        $seguimiento->condiciones = $request->condiciones;
        $seguimiento->historia = $request->historia;

        $seguimiento->save();
        return response()->json(['message' => 'Seguimiento realizado exitosamente']);
    }


    //MOSTRAR
    public function show($id)
    {
        try {
            // Obtener el seguimiento con las relaciones necesarias
            $seguimiento = Seguimientos::with(['empresa', 'pedido.categorias' => function ($query) {
                $query->withPivot('cantidad', 'descripcion');
            }])->findOrFail($id);

            $empresa = $seguimiento->empresa;
            $pedido = $seguimiento->pedido;

            // Construir los detalles de las categorías
            $detalles = [];
            if ($pedido) {
                foreach ($pedido->categorias as $categoria) {
                    $detalles[] = [
                        'cantidad' => $categoria->pivot->cantidad,
                        'categoria' => $categoria->nombreCategoria,
                        'descripcion' => $categoria->pivot->descripcion
                    ];
                }
            }

            // Construir la respuesta
            $response = [
                'id' => $seguimiento->id_seguimiento,
                'nombreEmpresa' => $empresa->nombreEmpresa,
                'celulares' => $empresa->celulares,
                'fechaIngreso' => $empresa->created_at,
                'descripcionTrabajo' => $detalles,
                'Total' => $pedido ? $pedido->montoTotal : null,
                'Condiciones' => $seguimiento->condiciones,
                'Historia' => $seguimiento->historia,
                'fechaSeguimiento' => $seguimiento->created_at,
            ];

            return response()->json($response, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'No se encontró el seguimiento para mostrarlo.'], Response::HTTP_NOT_FOUND);
        }
    }


    //BORRAR
    public function destroy($id)
    {
        try {
            $seguimiento = Seguimientos::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'No se encontro el seguimiento para borrarlo']);
        }

        $seguimiento->delete();
        return response()->json(['message' => 'El seguimiento ha sido borrado exitosamente.']);
    }


    // MOSTRAR SEGUIMIENTOS DESCENDENTE
    public function index()
    {
        // Obtener todos los seguimientos en orden descendente por id
        $seguimientos = Seguimientos::with(['empresa', 'pedido.categorias' => function ($query) {
            $query->withPivot('cantidad', 'descripcion');
        }])->orderBy('id_seguimiento', 'desc')->get();

        // Construir la respuesta
        $response = $seguimientos->map(function ($seguimiento) {
            $empresa = $seguimiento->empresa;
            $pedido = $seguimiento->pedido;

            // Construir los detalles de las categorías
            $detalles = [];
            if ($pedido) {
                foreach ($pedido->categorias as $categoria) {
                    $detalles[] = [
                        'cantidad' => $categoria->pivot->cantidad,
                        'categoria' => $categoria->nombreCategoria,
                        'descripcion' => $categoria->pivot->descripcion
                    ];
                }
            }

            return [
                'id' => $seguimiento->id_seguimiento,
                'nombreEmpresa' => $empresa->nombreEmpresa,
                'celulares' => $empresa->celulares,
                'fechaIngreso' => $empresa->created_at,
                'descripcionTrabajo' => $detalles,
                'Total' => $pedido ? $pedido->montoTotal : null,
                'Condiciones' => $seguimiento->condiciones,
                'Historia' => $seguimiento->historia,
                'fechaSeguimiento' => $seguimiento->created_at,
            ];
        });

        return response()->json($response, Response::HTTP_OK);
    }

    //SEGUIMIENTOS QUE NO SE HICIERON HACE "X" TIEMPO
    public function notificaciones()
    {
        // Establecer límites de tiempo para los seguimientos
        $timeLimitAutomatico = Carbon::now()->subMonth(); // 1 mes para automáticos
        $timeLimitManual = Carbon::now()->subDays(20);    // 20 días para manuales

        // Crear un array para almacenar las notificaciones
        $notificaciones = [];

        // Obtener todas las empresas con sus seguimientos
        $empresas = Empresas::with(['seguimientos' => function ($query) {
            // Ordenar seguimientos en orden descendente
            $query->orderBy('created_at', 'desc');
        }])->get();

        foreach ($empresas as $empresa) {
            // Obtener el último seguimiento de la empresa
            $ultimoSeguimiento = $empresa->seguimientos->first();

            if ($ultimoSeguimiento) {
                if ($ultimoSeguimiento->id_pedido !== null) {
                    // Seguimiento automático (con pedido)
                    if ($ultimoSeguimiento->created_at->lt($timeLimitAutomatico)) {
                        $tiempoTranscurrido = $ultimoSeguimiento->created_at->diffForHumans();
                        $notificaciones[] = "{$empresa->nombreEmpresa}. Se le entregó el pedido hace {$tiempoTranscurrido}.";
                    }
                } else {
                    // Seguimiento manual (sin pedido)
                    if ($ultimoSeguimiento->created_at->lt($timeLimitManual)) {
                        $diasExactos = $ultimoSeguimiento->created_at->diffInDays(Carbon::now());
                        $notificaciones[] = "{$empresa->nombreEmpresa}. Sin seguimiento hace {$diasExactos} días.";
                    }
                }
            }
        }

        // Retornar las notificaciones como respuesta JSON
        return response()->json([
            'notificaciones' => $notificaciones
        ], Response::HTTP_OK);
    }
}
