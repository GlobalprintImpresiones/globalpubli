<?php

namespace App\Http\Controllers;

use App\Models\Categorias;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;


class CategoriasController extends Controller
{
    
    //AGREGAR
    public function store(Request $request)
    {
        //VALIDACION DE CAMPOS
        $validator = Validator::make($request->all(), [
            'nombreCategoria' => ['required', 'string', 'max:50', 'unique:categorias'],
        ], [
            'nombreCategoria.unique' => 'La categoria ya existe.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_NOT_FOUND);
        }

        $categoria = new Categorias;
        $categoria->nombreCategoria = $request->nombreCategoria;

        $categoria->save();
        return response()->json(['message' => 'Se agregó la categoria ' . $request->nombreCategoria]);
    }

    
    //MOSTRAR
    public function show($id)
    {
        try {
            $categoria = Categorias::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'No se encontró la categoria para mostrarla.'], Response::HTTP_NOT_FOUND);
        }

        return $categoria;
    }

    
    //ACTUALIZAR
    public function update(Request $request, $id)
    {
        try {
            $categoria = Categorias::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'No se encontró la categoria para actualizarla.'], Response::HTTP_NOT_FOUND);
        }

        //VALIDACION DE CAMPOS
        $validator = Validator::make($request->all(), [
            'nombreCategoria' => ['required', 'string', Rule::unique('categorias')->ignore($id, 'id_categoria')],
        ], [
            'nombreCategoria.unique' => 'La categoria ya existe.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_NOT_FOUND);
        }

        $categoria->nombreCategoria = $request->nombreCategoria;

        $categoria->save();
        return response()->json(['message' => 'La categoria ' . $request->nombreCategoria .  ' ha sido actualizada exitosamente.']);
    }

    
    //BORRAR
    public function destroy($id)
    {
        try {
            $categoria = Categorias::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'No se encontro la categoria para borrarla']);
        }

        $categoria->delete();
        return response()->json(['message' => 'La categoria ' . $categoria->nombreCategoria . ' ha sido borrada exitosamente.']);
    }

    // MOSTRAR CATEGORIAS DESCENDENTE
    public function index()
    {
        return Categorias::orderBy('updated_at', 'desc')->get();
    }
}