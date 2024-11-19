<?php

namespace App\Http\Controllers;

use App\Models\Categorias;
use App\Models\Pedidos;
use App\Models\Empresas;
use App\Models\Seguimientos;
use Carbon\Carbon;
use Illuminate\Http\Request;

use FPDF;


use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class PedidosController extends Controller
{

    //AGREGAR PEDIDO
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombreEmpresa' => ['required', 'string', 'exists:empresas,nombreEmpresa'],
            'fechaPedido' => ['required', 'date'],
            'fechaEntrega' => ['date', 'nullable'],
            'categorias' => ['required', 'array'],
            'anticipo' => ['nullable', 'integer'],
            'saldo' => ['nullable', 'integer'],
            'montoTotal' => ['required', 'integer'],
            'observaciones' => ['nullable', 'string', 'max:80'],
            'categorias.*.descripcion' => ['nullable', 'string', 'max:100']
        ], [
            'nombreEmpresa.exists' => 'El nombre de la empresa no existe.',
            'observaciones.max' => 'Las observaciones no pueden tener más de 80 caracteres.',
            'categorias.*.descripcion.max' => 'La descripción de cada categoría no puede tener más de 100 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $empresa = Empresas::where('nombreEmpresa', $request->nombreEmpresa)->first();

        // Verificar si la empresa está bloqueada o no
        if ($empresa->estadoEmpresa == 0) {
            return response()->json(['error' => 'La empresa está bloqueada y no se pueden realizar pedidos.'], Response::HTTP_FORBIDDEN);
        }

        // Verificar cada categoría antes de crear el pedido
        foreach ($request->categorias as $categoriaData) {
            $nombreCategoria = $categoriaData['nombreCategoria'];
            $descripcion = $categoriaData['descripcion'];
            $cantidad = $categoriaData['cantidad'];

            $categoria = Categorias::where('nombreCategoria', $nombreCategoria)->first();

            if (!$categoria) {
                return response()->json(['error' => 'La categoría no existe. No se creó el pedido.'], Response::HTTP_NOT_FOUND);
            }
        }

        $pedido = new Pedidos;
        $pedido->fechaPedido = $request->fechaPedido;
        $pedido->fechaEntrega = $request->fechaEntrega;
        $pedido->anticipo = $request->anticipo;
        $pedido->saldo = $request->saldo;
        $pedido->montoTotal = $request->montoTotal;
        $pedido->observaciones = $request->observaciones;
        $pedido->id_empresa = $empresa->id_empresa;
        $pedido->id_usuario = auth()->id(); // Obtener el ID del usuario logueado
        $pedido->estadoPedido = 0; // Establecer el estado a 0

        $pedido->save();

        // Attach categories to the pedido using their names
        $detalles = [];
        foreach ($request->categorias as $categoriaData) {
            $nombreCategoria = $categoriaData['nombreCategoria'];
            $cantidad = $categoriaData['cantidad'];
            $descripcion = $categoriaData['descripcion'];

            $categoria = Categorias::where('nombreCategoria', $nombreCategoria)->first();

            // Asigna la categoría al pedido con la cantidad correspondiente en la tabla intermedia
            $pedido->categorias()->attach([
                $categoria->id_categoria => [
                    'descripcion' => $descripcion,
                    'cantidad' => $cantidad
                ]
            ]);

            // Agregar los detalles de la categoría a la respuesta
            $detalles[] = [
                'nombreCategoria' => $nombreCategoria,
                'descripcion' => $descripcion,
                'cantidad' => $cantidad
            ];
        }

        // Construir la respuesta
        $response = [
            'id_pedido' => $pedido->id_pedido,
            'Empresa' => $empresa->nombreEmpresa,
            'Encargado' => $empresa->encargado, // Asegúrate de tener el campo 'encargado' en el modelo 'Empresas'
            'fechaPedido' => Carbon::parse($pedido->fechaPedido)->format('d/m/Y'),
            'fechaEntrega' => $pedido->fechaEntrega ? Carbon::parse($pedido->fechaEntrega)->format('d/m/Y') : null,
            'Detalle' => $detalles,
            'anticipo' => $pedido->anticipo,
            'saldo' => $pedido->saldo,
            'montoTotal' => $pedido->montoTotal,
            'observaciones' => $pedido->observaciones,
            'estadoPedido' => $pedido->estadoPedido
        ];

        return response()->json($response, Response::HTTP_CREATED);
    }



    //MOSTRAR
    public function show($id)
    {
        $pedido = Pedidos::with(['categorias' => function ($query) {
            $query->withPivot('descripcion', 'cantidad');
        }])->find($id);

        if (!$pedido) {
            return response()->json(['error' => 'Pedido no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $empresa = $pedido->empresa;

        // Construir los detalles de las categorías
        $detalles = [];
        foreach ($pedido->categorias as $categoria) {
            $detalles[] = [
                'Categoria' => $categoria->nombreCategoria,
                'Descripcion' => $categoria->pivot->descripcion,
                'Cantidad' => $categoria->pivot->cantidad
            ];
        }

        // Construir la respuesta
        $response = [
            'id_pedido' => $pedido->id_pedido,
            'Empresa' => $empresa->nombreEmpresa,
            'Encargado' => $empresa->encargado,
            'fechaPedido' => Carbon::parse($pedido->fechaPedido)->format('d/m/Y'),
            'fechaEntrega' => $pedido->fechaEntrega ? Carbon::parse($pedido->fechaEntrega)->format('d/m/Y') : null,
            'Detalle' => $detalles,
            'anticipo' => $pedido->anticipo,
            'saldo' => $pedido->saldo,
            'montoTotal' => $pedido->montoTotal,
            'observaciones' => $pedido->observaciones,
            'estadoPedido' => $pedido->estadoPedido
        ];

        return response()->json($response, Response::HTTP_OK);
    }



    //BORRAR
    public function destroy($id)
    {
        // Busca el pedido en la base de datos
        $pedido = Pedidos::find($id);

        if (!$pedido) {
            return response()->json(['error' => 'Pedido no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        // Verifica el estado del pedido
        if ($pedido->estadoPedido == 1) {
            return response()->json(['error' => 'El pedido no se puede eliminar porque ya esta entregado.'], Response::HTTP_FORBIDDEN);
        }

        // Elimina las categorías asociadas al pedido
        $pedido->categorias()->detach();

        // Elimina el pedido
        $pedido->delete();

        return response()->json(['message' => 'Pedido eliminado con éxito.'], Response::HTTP_OK);
    }



    // ENTREGAR PEDIDO
    public function estadoPedido($id)
    {
        // Busca el pedido en la base de datos
        $pedido = Pedidos::find($id);

        if (!$pedido) {
            return response()->json(['error' => 'Pedido no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $pedido->fechaEntrega = now();

        $pedido->estadoPedido = 1; // Establecer el estado a 1

        $pedido->save();

        // Crear un seguimiento al momento de entregar el pedido
        $seguimiento = new Seguimientos();
        $seguimiento->id_empresa = $pedido->id_empresa; // Asegúrate de que 'id_empresa' esté presente en el pedido
        $seguimiento->id_pedido = $id;   // Asociar el seguimiento al pedido
        $seguimiento->condiciones = 'Buenas';
        $seguimiento->historia = 'Se le entrego el trabajo'; // Mensaje de historial
        $seguimiento->save();

        return response()->json([
            'message' => 'Pedido entregado y seguimiento agregado.'
        ], Response::HTTP_OK);
    }



    //MOSTRAR PEDIDOS EN ORDEN DESCENDENTE
    public function index()
    {
        $pedidos = Pedidos::with(['user', 'empresa', 'categorias' => function ($query) {
            $query->withPivot('descripcion', 'cantidad');
        }])->orderBy('id_pedido', 'desc')->get();

        $formattedPedidos = $pedidos->map(function ($pedido) {
            // Construir los detalles de las categorías
            $detalles = $pedido->categorias->map(function ($categoria) {
                return [
                    'Categoria' => $categoria->nombreCategoria,
                    'Descripcion' => $categoria->pivot->descripcion,
                    'Cantidad' => $categoria->pivot->cantidad
                ];
            });

            return [
                'id_pedido' => $pedido->id_pedido,
                'Empresa' => $pedido->empresa->nombreEmpresa,
                'Encargado' => $pedido->empresa->encargado,
                'fechaPedido' => Carbon::parse($pedido->fechaPedido)->format('d/m/Y'),
                'fechaEntrega' => $pedido->fechaEntrega ? Carbon::parse($pedido->fechaEntrega)->format('d/m/Y') : null,
                'Detalle' => $detalles,
                'anticipo' => $pedido->anticipo,
                'saldo' => $pedido->saldo,
                'vendedor' => $pedido->user->name,
                'montoTotal' => $pedido->montoTotal,
                'observaciones' => $pedido->observaciones,
                'estadoPedido' => $pedido->estadoPedido
            ];
        });

        return response()->json(['pedidos' => $formattedPedidos], Response::HTTP_OK);
    }


    // PARA EL PDF DEL RECIBO
    public function pdf($id)
    {
        // Busca el pedido en la base de datos
        $pedido = Pedidos::with(['empresa', 'categorias' => function ($query) {
            $query->withPivot('descripcion', 'cantidad');
        }])->find($id);

        if (!$pedido) {
            return response()->json(['error' => 'Pedido no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $sizeLetra = 9;

        // Crear un nuevo objeto PDF con FPDI
        $pdf = new FPDF();
        $pdf->AddPage('L', [210, 160]); // Tamaño personalizado 21x16 cm en formato horizontal
        $pdf->SetFont('Arial', '', $sizeLetra, 'ISO-8859-1'); // Utiliza la codificación ISO-8859-1

        // Función personalizada para convertir texto a mayúsculas
        function formatText($text)
        {
            $encoding = mb_detect_encoding($text, mb_list_encodings(), true);
            if ($encoding) {
                return mb_convert_encoding($text, 'ISO-8859-1', $encoding);
            }
            return $text; // Devuelve el texto original si no se detecta la codificación
        }

        // IMAGEN
        $pdf->Image('admin/assets/images/logoPNG.png', 25, 3, 35);

        // Encabezado "RECIBO"
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 11); // Negrilla para el encabezado
        $pdf->Cell(0, 10, "RECIBO", 0, 1, 'C');
        $pdf->SetFont('Arial', '', $sizeLetra);

        // UBICACION Y NUMEROS
        $direccion = "Calle Santivañez N° 265 entre Junin y Hamiraya \nCel: 69474001 - 62616519";
        // Posicionar el cursor verticalmente 10 mm más arriba de la posición actual
        $currentY = $pdf->GetY(); // Obtiene la posición actual de Y
        $pdf->SetY($currentY - 10); // Ajusta la posición 10 mm hacia arriba

        // Ahora se coloca el texto con MultiCell
        $pdf->MultiCell(0, 5, formatText($direccion), 0, 'R'); // Información alineada a la derecha

        // Datos del pedido
        $nombreEmpresa = strtoupper($pedido->empresa->nombreEmpresa); // Datos de la empresa en mayúsculas
        $encargado = strtoupper($pedido->empresa->encargado); // Datos en mayúsculas
        $pdf->Ln(11); // Salto de línea

        // Información del pedido
        $pdf->Cell(50, 5, formatText("ID PEDIDO: " . $pedido->id_pedido), 0, 0, 'L');
        $pdf->Cell(70, 5, '', 0, 0); // Espacio en blanco
        $pdf->Cell(50, 5, formatText("FECHA DEL PEDIDO: " . Carbon::parse($pedido->fechaPedido)->format('d/m/Y')), 0, 1, 'L');

        $pdf->Cell(50, 5, formatText("EMPRESA: " . $nombreEmpresa . " (" . $encargado . ")"), 0, 0, 'L');
        $pdf->Cell(70, 5, '', 0, 0); // Espacio en blanco

        // Verificar si la fecha de entrega es null antes de formatear
        $fechaEntrega = $pedido->fechaEntrega ? Carbon::parse($pedido->fechaEntrega)->format('d/m/Y') : '';
        $pdf->Cell(50, 5, formatText("FECHA DE ENTREGA: " . $fechaEntrega), 0, 1, 'L');

        $pdf->Ln(1); // Salto de línea

        // Detalle de las categorías
        $pdf->SetFont('Arial', 'B', 10); // Negrilla para el subtítulo "DETALLE"
        $pdf->Cell(0, 10, "DETALLE", 0, 1, 'L');
        $pdf->Cell(0, 0, '', 'T'); // Línea horizontal
        $pdf->Ln(1); // Salto de línea

        $pdf->SetFont('Arial', '', $sizeLetra); // Fuente normal para los detalles

        $lineNumber = 1; // Contador para numerar las líneas

        foreach ($pedido->categorias as $categoria) {
            $nombreCategoria = $categoria->nombreCategoria;
            $descripcion = $categoria->pivot->descripcion;
            $cantidad = $categoria->pivot->cantidad;

            // Crear la línea de detalle con el formato correcto
            $detalle = sprintf("%d) %s %s", $lineNumber, str_pad($cantidad, 2, ' ', STR_PAD_LEFT), formatText($nombreCategoria) . ' ' . formatText($descripcion));

            // Imprimir detalle con el formato deseado
            $pdf->Cell(0, 6, formatText($detalle), 0, 1, 'L');

            $lineNumber++; // Incrementar el número de línea
        }


        $pdf->Ln(1); // Salto de línea
        $pdf->Cell(0, 0, '', 'T'); // Línea horizontal
        $pdf->Ln(1.5); // Salto de línea

        // Anticipo, saldo y total
        $pdf->Cell(75, 10, formatText("ANTICIPO: " . $pedido->anticipo), 0, 0, 'L');
        $pdf->Cell(65, 10, formatText("SALDO: " . $pedido->saldo), 0, 0, 'L');
        $pdf->Cell(60, 10, formatText("TOTAL: " . $pedido->montoTotal), 0, 1, 'L');

        $pdf->Ln(1); // Salto de línea

        // Observaciones
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 10, 'OBSERVACIONES', 0, 1, 'L');
        $pdf->SetFont('Arial', '', $sizeLetra);
        $pdf->MultiCell(0, 5, formatText($pedido->observaciones), 0, 'L');

        // FIRMAS
        $pdf->SetFont('Arial', 'B', 9);
        // Obtener la altura de la página
        $pageHeight = $pdf->GetPageHeight();

        // Altura desde el borde inferior donde se colocarán las firmas
        $yPosition = $pageHeight - 27; // 30 mm desde el borde inferior

        // Configurar la posición para la primera firma (izquierda)
        $pdf->SetY($yPosition);
        $pdf->SetX(15); // 20 mm desde el borde izquierdo

        // Dibujar la línea de la primera firma
        $pdf->Cell(60, 0, '', 'T'); // 60 mm de largo para la línea de la firma

        // Espacio debajo de la línea para el texto
        $pdf->Ln(1); // Salto de línea de 5 mm
        $pdf->Cell(73, 5, 'PUBLICARTE', 0, 0, 'C'); // Texto centrado bajo la línea

        // Configurar la posición para la segunda firma (derecha)
        $pdf->SetY($yPosition);
        $pdf->SetX($pdf->GetPageWidth() - 80); // Asegura que la firma esté a la derecha

        // Dibujar la línea de la segunda firma
        $pdf->Cell(60, 0, '', 'T'); // 60 mm de largo para la línea de la firma

        // Espacio debajo de la línea para el texto
        $pdf->Ln(1); // Salto de línea de 5 mm
        $pdf->Cell(158, 5, 'CLIENTE', 0, 0, 'R'); // Texto centrado bajo la línea


        // Genera el contenido del PDF
        $pdfContent = $pdf->Output("S");

        // Guarda el contenido en un archivo temporal
        $filePath = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($filePath, $pdfContent);

        // Crea una respuesta de archivo binario para el PDF
        $response = new BinaryFileResponse($filePath);

        // Nombre del archivo de descarga
        $fileName = sprintf('%s Pedido %d.pdf', str_replace(' ', '_', $nombreEmpresa), $pedido->id_pedido);

        // Establece las cabeceras de respuesta
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        // Elimina el archivo temporal después de que se envía
        register_shutdown_function(function () use ($filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        });

        return $response;
    }


    // MOSTRAR SEGUIMIENTOS PROPIOS DE LA EMPRESA
    public function pedidosPorEmpresa($empresaId)
    {
        // Validar si la empresa existe
        $empresa = Empresas::find($empresaId);

        if (!$empresa) {
            return response()->json(['error' => 'Empresa no encontrada'], Response::HTTP_NOT_FOUND);
        }

        // Obtener los pedidos relacionados con la empresa
        $pedidos = Pedidos::where('id_empresa', $empresaId)->orderBy('id_pedido', 'desc')->get();

        if ($pedidos->isEmpty()) {
            return response()->json(['message' => 'La empresa no tiene pedidos'], Response::HTTP_OK);
        }

        // Construir la respuesta solo con los IDs de los pedidos
        $response = $pedidos->map(function ($pedido) {
            return [
                'idPedido' => $pedido->id_pedido,
            ];
        });

        return response()->json($response, Response::HTTP_OK);
    }


    //AGREGAR UNA EMPRESA Y AL MISMO TIEMPO UN PEDIDO
    public function storeEmpresaYPedido(Request $request)
    {
        // Validación para la empresa
        $empresaValidator = Validator::make($request->all(), [
            'nombreEmpresa' => ['required', 'string', 'unique:empresas'],
            'encargado' => ['required', 'string'],
            'rubroEmpresa' => ['required', 'string'],
            'celulares' => ['required', 'string', 'max:50', 'regex:/^[0-9()+\- ]*$/'],
            'direccionEmpresa' => ['required', 'string'],
            'correoEmpresa' => ['nullable', 'email']
        ], [
            'celulares.min' => 'El número de celular no tiene los dígitos suficientes.',
            'nombreEmpresa.unique' => 'El nombre de la empresa ya existe.'
        ]);

        if ($empresaValidator->fails()) {
            return response()->json(['errors' => $empresaValidator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Validación para el pedido
        $pedidoValidator = Validator::make($request->all(), [
            'fechaPedido' => ['required', 'date'],
            'fechaEntrega' => ['date', 'nullable'],
            'categorias' => ['required', 'array'],
            'anticipo' => ['nullable', 'integer'],
            'saldo' => ['nullable', 'integer'],
            'montoTotal' => ['required', 'integer'],
            'observaciones' => ['nullable', 'string', 'max:80'],
            'categorias.*.descripcion' => ['nullable', 'string', 'max:100']
        ], [
            'observaciones.max' => 'Las observaciones no pueden tener más de 80 caracteres.',
            'categorias.*.descripcion.max' => 'La descripción de cada categoría no puede tener más de 100 caracteres.',
        ]);

        if ($pedidoValidator->fails()) {
            return response()->json(['errors' => $pedidoValidator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Creación de la empresa
        $empresa = new Empresas;
        $empresa->nombreEmpresa = $request->nombreEmpresa;
        $empresa->encargado = $request->encargado;
        $empresa->rubroEmpresa = $request->rubroEmpresa;
        $empresa->celulares = $request->celulares;
        $empresa->direccionEmpresa = $request->direccionEmpresa;
        $empresa->correoEmpresa = $request->correoEmpresa;
        $empresa->save();

        // Creación del pedido
        $pedido = new Pedidos;
        $pedido->fechaPedido = $request->fechaPedido;
        $pedido->fechaEntrega = $request->fechaEntrega;
        $pedido->anticipo = $request->anticipo;
        $pedido->saldo = $request->saldo;
        $pedido->montoTotal = $request->montoTotal;
        $pedido->observaciones = $request->observaciones;
        $pedido->id_empresa = $empresa->id_empresa;
        $pedido->id_usuario = auth()->id(); // Obtener el ID del usuario logueado
        $pedido->estadoPedido = 0;

        $pedido->save();

        // Asignación de categorías al pedido
        $detalles = [];
        foreach ($request->categorias as $categoriaData) {
            $nombreCategoria = $categoriaData['nombreCategoria'];
            $cantidad = $categoriaData['cantidad'];
            $descripcion = $categoriaData['descripcion'];

            $categoria = Categorias::where('nombreCategoria', $nombreCategoria)->first();

            if (!$categoria) {
                return response()->json(['error' => 'La categoría no existe. No se creó el pedido.'], Response::HTTP_NOT_FOUND);
            }

            // Asignar la categoría al pedido
            $pedido->categorias()->attach([
                $categoria->id_categoria => [
                    'descripcion' => $descripcion,
                    'cantidad' => $cantidad
                ]
            ]);

            // Agregar los detalles de la categoría a la respuesta
            $detalles[] = [
                'nombreCategoria' => $nombreCategoria,
                'descripcion' => $descripcion,
                'cantidad' => $cantidad
            ];
        }

        // Construir la respuesta
        $response = [
            'Empresa' => [
                'nombreEmpresa' => $empresa->nombreEmpresa,
                'encargado' => $empresa->encargado,
                'rubroEmpresa' => $empresa->rubroEmpresa,
            ],
            'Pedido' => [
                'id_pedido' => $pedido->id_pedido,
                'fechaPedido' => Carbon::parse($pedido->fechaPedido)->format('d/m/Y'),
                'fechaEntrega' => $pedido->fechaEntrega ? Carbon::parse($pedido->fechaEntrega)->format('d/m/Y') : null,
                'Detalle' => $detalles,
                'anticipo' => $pedido->anticipo,
                'saldo' => $pedido->saldo,
                'montoTotal' => $pedido->montoTotal,
                'observaciones' => $pedido->observaciones,
                'estadoPedido' => $pedido->estadoPedido
            ]
        ];

        return response()->json($response, Response::HTTP_CREATED);
    }
}
