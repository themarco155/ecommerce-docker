<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayOrderRequest;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

/**
 * @OA\Tag(name="Pagos", description="Procesamiento de pagos de órdenes mediante Stripe")
 */
class PaymentController extends Controller
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * @OA\Post(
     *     path="/orders/{id}/pay",
     *     tags={"Pagos"},
     *     summary="Procesar el pago de una orden con Stripe",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_method"},
     *             @OA\Property(property="payment_method", type="string", example="pm_card_visa", description="Método de pago de prueba de Stripe")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Pago procesado correctamente"),
     *     @OA\Response(response=402, description="El pago fue rechazado por Stripe"),
     *     @OA\Response(response=403, description="La orden no pertenece al cliente autenticado"),
     *     @OA\Response(response=409, description="La orden ya fue pagada")
     * )
     */
    public function store(PayOrderRequest $request, Order $order)
    {
        if ($order->user_id !== Auth::guard('api')->id()) {
            abort(403, 'Esta orden no pertenece al cliente autenticado.');
        }

        if ($order->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden ya fue pagada.',
            ], 409);
        }

        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) round($order->total * 100), // Stripe trabaja en centavos.
                'currency' => 'usd',
                'payment_method' => $request->validated('payment_method'),
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
                'description' => "Orden #{$order->id}",
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                ],
            ]);
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar el pago con Stripe.',
                'error' => $e->getMessage(),
            ], 402);
        }

        $payment = $order->payment()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'stripe_payment_intent_id' => $paymentIntent->id,
                'amount' => $order->total,
                'currency' => 'usd',
                'status' => $paymentIntent->status,
            ]
        );

        if ($paymentIntent->status === PaymentIntent::STATUS_SUCCEEDED) {
            $order->update(['status' => 'paid']);
        }

        return response()->json([
            'success' => $paymentIntent->status === PaymentIntent::STATUS_SUCCEEDED,
            'message' => $paymentIntent->status === PaymentIntent::STATUS_SUCCEEDED
                ? 'Pago procesado correctamente.'
                : 'El pago quedó en estado: '.$paymentIntent->status,
            'data' => [
                'order' => $order->fresh(),
                'payment' => $payment,
            ],
        ]);
    }
}
