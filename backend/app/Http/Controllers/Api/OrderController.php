<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(name="Órdenes", description="Creación y consulta de órdenes de compra")
 */
class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/orders",
     *     tags={"Órdenes"},
     *     summary="Historial de compras del cliente autenticado",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Listado de órdenes del cliente")
     * )
     */
    public function index()
    {
        $orders = Auth::guard('api')->user()
            ->orders()
            ->with(['items.product', 'payment'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/orders",
     *     tags={"Órdenes"},
     *     summary="Crear una orden de compra a partir de una lista de productos",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"items"},
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="product_id", type="integer", example=1),
     *                     @OA\Property(property="quantity", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Orden creada"),
     *     @OA\Response(response=422, description="Datos inválidos o sin stock suficiente")
     * )
     */
    public function store(StoreOrderRequest $request)
    {
        $user = Auth::guard('api')->user();
        $items = $request->validated('items');

        $order = DB::transaction(function () use ($user, $items) {
            $order = Order::create([
                'user_id' => $user->id,
                'total' => 0,
                'status' => 'pending',
            ]);

            $total = 0;

            foreach ($items as $item) {
                // Bloquea la fila del producto para evitar condiciones de carrera con el stock.
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();

                if (! $product || ! $product->active) {
                    throw ValidationException::withMessages([
                        'items' => "El producto con id {$item['product_id']} no está disponible.",
                    ]);
                }

                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "No hay suficiente stock de \"{$product->name}\". Disponible: {$product->stock}.",
                    ]);
                }

                $subtotal = $product->price * $item['quantity'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ]);

                $product->decrement('stock', $item['quantity']);

                $total += $subtotal;
            }

            $order->update(['total' => $total]);

            return $order;
        });

        return response()->json([
            'success' => true,
            'message' => 'Orden creada correctamente.',
            'data' => $order->load('items.product'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/orders/{id}",
     *     tags={"Órdenes"},
     *     summary="Ver el detalle de una orden propia",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle de la orden"),
     *     @OA\Response(response=403, description="La orden no pertenece al cliente autenticado"),
     *     @OA\Response(response=404, description="Orden no encontrada")
     * )
     */
    public function show(Order $order)
    {
        $this->authorizeOwnership($order);

        return response()->json([
            'success' => true,
            'data' => $order->load(['items.product', 'payment']),
        ]);
    }

    private function authorizeOwnership(Order $order): void
    {
        if ($order->user_id !== Auth::guard('api')->id()) {
            abort(403, 'Esta orden no pertenece al cliente autenticado.');
        }
    }
}
