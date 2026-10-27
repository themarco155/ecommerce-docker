<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @OA\Tag(name="Autenticación", description="Registro, login y sesión del cliente")
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/register",
     *     tags={"Autenticación"},
     *     summary="Registrar un nuevo cliente",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Juan Pérez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Cliente registrado correctamente"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Cliente registrado correctamente.',
            'data' => [
                'user' => $user,
                'authorization' => $this->formatToken($token),
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/login",
     *     tags={"Autenticación"},
     *     summary="Iniciar sesión y obtener un token JWT",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login exitoso"),
     *     @OA\Response(response=401, description="Credenciales inválidas")
     * )
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Las credenciales proporcionadas son incorrectas.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión iniciada correctamente.',
            'data' => [
                'user' => Auth::guard('api')->user(),
                'authorization' => $this->formatToken($token),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/me",
     *     tags={"Autenticación"},
     *     summary="Obtener el perfil del cliente autenticado",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Perfil del cliente"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function me()
    {
        return response()->json([
            'success' => true,
            'data' => Auth::guard('api')->user(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     tags={"Autenticación"},
     *     summary="Cerrar sesión (invalidar el token actual)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Sesión cerrada")
     * )
     */
    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/refresh",
     *     tags={"Autenticación"},
     *     summary="Refrescar el token JWT",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Token renovado")
     * )
     */
    public function refresh()
    {
        $token = Auth::guard('api')->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'authorization' => $this->formatToken($token),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatToken(string $token): array
    {
        return [
            'token' => $token,
            'type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ];
    }
}
