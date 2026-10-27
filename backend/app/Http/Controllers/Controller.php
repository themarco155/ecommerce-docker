<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="API E-commerce Segura",
 *     version="1.0.0",
 *     description="API RESTful para la gestión de clientes, catálogo de productos, órdenes de compra y pagos con Stripe.",
 *     @OA\Contact(email="marcostario47@gmail.com")
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor de la API"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Autenticación mediante token JWT. Formato del header: Authorization: Bearer {token}"
 * )
 */
abstract class Controller
{
    //
}
