<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Método de pago de prueba generado en el frontend con Stripe.js
            // (ej. "pm_card_visa" para pruebas con la tarjeta de prueba de Stripe).
            'payment_method' => ['required', 'string'],
        ];
    }
}
