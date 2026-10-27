<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;

/**
 * @OA\Tag(name="Productos", description="Catálogo de productos de la tienda")
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products",
     *     tags={"Productos"},
     *     summary="Listar el catálogo de productos (público)",
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer"), description="Número de página"),
     *     @OA\Response(response=200, description="Listado paginado de productos")
     * )
     */
    public function index()
    {
        $products = Product::where('active', true)
            ->orderBy('name')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/products/{id}",
     *     tags={"Productos"},
     *     summary="Ver el detalle de un producto (público)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle del producto"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function show(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/products",
     *     tags={"Productos"},
     *     summary="Crear un producto (requiere autenticación)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","price","stock"},
     *             @OA\Property(property="name", type="string", example="Camisa azul"),
     *             @OA\Property(property="description", type="string", example="Camisa de algodón talla M"),
     *             @OA\Property(property="price", type="number", format="float", example=19.99),
     *             @OA\Property(property="stock", type="integer", example=50),
     *             @OA\Property(property="image_url", type="string", example="https://ejemplo.com/camisa.jpg")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Producto creado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Producto creado correctamente.',
            'data' => $product,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/products/{id}",
     *     tags={"Productos"},
     *     summary="Editar un producto (requiere autenticación)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Camisa azul (edición limitada)"),
     *             @OA\Property(property="price", type="number", format="float", example=24.99),
     *             @OA\Property(property="stock", type="integer", example=30)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Producto actualizado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente.',
            'data' => $product,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/products/{id}",
     *     tags={"Productos"},
     *     summary="Eliminar un producto (requiere autenticación)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Producto eliminado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente.',
        ]);
    }
}
