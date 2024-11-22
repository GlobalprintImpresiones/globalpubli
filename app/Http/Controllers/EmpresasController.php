<?php

namespace App\Http\Controllers;

use App\Models\Empresas;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;

class EmpresasController extends Controller
{

    //AGREGAR
    public function store(Request $request)
    {

        //VALIDACION DE CAMPOS
        $validator = Validator::make($request->all(), [
            'nombreEmpresa' => ['required', 'string', 'unique:empresas'],
            'encargado' => ['required', 'string'],
            'rubroEmpresa' => ['required', 'string'],
            'celulares' => ['required', 'string', 'max:50', 'regex:/^[0-9()+\- ]*$/'],
            'direccionEmpresa' => ['required', 'string'],
            'correoEmpresa' => ['nullable', 'email']
        ], [
            'celulares.min' => 'El numero de celular no tiene los digitos suficientes.',
            'nombreEmpresa.unique' => 'El nombre de la empresa ya existe.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_NOT_FOUND);
        }

        $empresa = new Empresas;
        $empresa->nombreEmpresa = $request->nombreEmpresa;
        $empresa->encargado = $request->encargado;
        $empresa->rubroEmpresa = $request->rubroEmpresa;
        $empresa->celulares = $request->celulares;
        $empresa->direccionEmpresa = $request->direccionEmpresa;
        $empresa->correoEmpresa = $request->correoEmpresa;
        $empresa->save();

        return response()->json(['message' => 'Se agregó a ' . $request->nombreEmpresa]);
    }



    //MOSTRAR
    public function show($id)
    {

        try {
            $empresa = Empresas::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'No se encontró a la empresa para mostrarla.'], Response::HTTP_NOT_FOUND);
        }

        return $empresa;
    }



    //ACTUALIZAR
    public function update(Request $request, $id)
    {

        try {
            $empresa = Empresas::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'No se encontró a la empresa para actualizarla.'], Response::HTTP_NOT_FOUND);
        }


        //VALIDACION DE CAMPOS
        $validator = Validator::make($request->all(), [
            'nombreEmpresa' => ['required', 'string', Rule::unique('empresas')->ignore($id, 'id_empresa')],
            'encargado' => ['required', 'string'],
            'rubroEmpresa' => ['required', 'string', 'max:50'],
            'celulares' => ['required', 'string', 'max:50', 'regex:/^[0-9()+\- ]*$/'],
            'direccionEmpresa' => ['required', 'string'],
            'correoEmpresa' => ['nullable', 'email']
        ], [
            'nombreEmpresa.unique' => 'El nombre de la empresa ya existe.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_NOT_FOUND);
        }

        $empresa->nombreEmpresa = $request->nombreEmpresa;
        $empresa->encargado = $request->encargado;
        $empresa->rubroEmpresa = $request->rubroEmpresa;
        $empresa->celulares = $request->celulares;
        $empresa->direccionEmpresa = $request->direccionEmpresa;
        $empresa->correoEmpresa = $request->correoEmpresa;
        $empresa->save();

        return response()->json(['message' => 'La empresa ' . $request->nombreEmpresa .  ' ha sido actualizada exitosamente.']);
    }



    //BORRAR
    public function destroy($id)
    {
        try {
            $empresa = Empresas::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'No se encontró a la empresa para eliminarla.'], Response::HTTP_NOT_FOUND);
        }

        $empresa->delete();
        return response()->json(['message' => 'La empresa ' . $empresa->nombreEmpresa . ' ha sido borrada exitosamente.']);
    }
    

    // MOSTRAR EMPRESAS DESCENDENTE
    public function index()
    {
        return Empresas::orderBy('nombreEmpresa', 'asc')->get();
    }


    public function bloquearDesbloquear($id)
    {
        // Busca el pedido en la base de datos
        $empresa = Empresas::find($id);

        if (!$empresa) {
            return response()->json(['error' => 'Empresa no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        if($empresa->estadoEmpresa == 1){
            $empresa->estadoEmpresa = 0;
            $message = 'Empresa bloqueada exitosamente';
        }else{
            $empresa->estadoEmpresa = 1;
            $message = 'Empresa desbloqueada exitosamente';
        }

        $empresa->save();

        return response()->json(['message' => $message], Response::HTTP_OK);
    
    }
}
