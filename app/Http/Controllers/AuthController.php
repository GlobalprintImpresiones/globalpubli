<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Personas;
use App\Models\Roles;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\Rule;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthController extends Controller
{

    // REGISTRAR
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:users',
            'password' => 'required|confirmed',
            'nombreRol' => ['required', 'string', 'exists:roles,nombreRol']
        ], [
            'name.unique' => 'El nombre de usuario ya existe.',
            'nombreRol.exists' => 'El nombre del rol no existe.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }


        $rol = Roles::where('nombreRol', $request->nombreRol)->first();

        $user = new User();
        $user->name = $request->name;
        $user->password = Hash::make($request->password);
        $user->id_rol = $rol->id_rol;
        $user->save();

        return response()->json(['message' => 'Se agregó a ' . $request->name]);
    }


    //MOSTRAR
    public function show($id)
    {

        try {
            $user = User::with('rol')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'No se encontró al usuario para mostrarlo.'], Response::HTTP_NOT_FOUND);
        }

        return $user;
    }


    //ACTUALIZAR
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'No se encontro al usuario para actualizarlo']);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', Rule::unique('users')->ignore($id)],
            'password' => ['required', 'confirmed'],
            'nombreRol' => ['required', 'string', 'max:50', 'exists:roles,nombreRol']
        ], [
            'name.unique' => 'El nombre de usuario ya existe.',
            'nombreRol.exists' => 'El nombre del rol no existe.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_NOT_FOUND);
        }

        $rol = Roles::where('nombreRol', $request->nombreRol)->first();

        $user->name = $request->name;
        $user->password = Hash::make($request->password);
        $user->id_rol = $rol->id_rol;

        $user->save();
        return response()->json(['message' => 'Se actualizó a ' . $request->name]);
    }

    //BORRAR
    public function destroy($id)
    {
        try {
            $users = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'No se encontró al usuario para eliminarlo.'], Response::HTTP_NOT_FOUND);
        }

        $users->delete();
        return response()->json(['message' => 'El usuario ' . $users->name . ' ha sido borrado exitosamente.']);
    }


    //LOGIN
    public function login(Request $request)
    {
        // Valida los datos de entrada
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $credentials = $request->only('name', 'password');

        if (Auth::attempt($credentials)) {
            // La autenticación fue exitosa
            $user = Auth::user();
            $token = $user->createToken('token')->plainTextToken;
            $name = $user->name; // Obtener el 'name' del usuario
            $nombreRol = $user->rol->nombreRol; // Obtener el 'nombreRol' del usuario

            return response([
                "token" => $token,
                "name" => $name, // Agregar 'name' a la respuesta
                "nombreRol" => $nombreRol, // Agregar 'nombreRol' a la respuesta
            ], Response::HTTP_OK);
        } else {
            // La autenticación falló
            return response()->json(['error' => 'Credenciales no válidas'], Response::HTTP_UNAUTHORIZED);
        }
    }


    // LOGOUT
    public function logout(Request $request)
    {
        $user = Auth::user();

        // Obtener los tokens de acceso activos del usuario
        $tokens = $user->tokens;

        // Iterar sobre los tokens y eliminarlos
        foreach ($tokens as $token) {
            $token->delete();
        }

        return response()->json(['message' => 'Sesión cerrada exitosamente'], 200);
    }


    // MOSTRAR USUARIOS DESCENDENTE
    public function index()
    {
        return User::with('rol')->orderBy('updated_at', 'desc')->get();
    }
}
