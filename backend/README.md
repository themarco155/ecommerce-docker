# API E-commerce Segura con Swagger

> Para correrla con Docker junto al frontend y MySQL, revisa el [README principal](../README.md).

API RESTful hecha en Laravel 12 para un e-commerce básico: registro/login de clientes con JWT, catálogo de productos, creación de órdenes de compra y procesamiento de pagos con Stripe. Documentada completamente con Swagger/OpenAPI.

## Requisitos

- PHP 8.2 o superior
- Composer
- MySQL (o MariaDB, por ejemplo con XAMPP)
- Una cuenta de Stripe (modo de prueba) para las claves de la pasarela de pago

## Instalación

1. **Clona el repositorio** y entra a la carpeta del proyecto:
   ```bash
   git clone <URL-de-tu-repositorio>
   cd ecommerce-api
   ```

2. **Instala las dependencias**:
   ```bash
   composer install
   ```

3. **Crea el archivo de entorno**:
   ```bash
   copy .env.example .env
   ```
   (en Linux/Mac usa `cp .env.example .env`)

4. **Genera la clave de la aplicación**:
   ```bash
   php artisan key:generate
   ```

5. **Crea la base de datos** en MySQL (por ejemplo, desde phpMyAdmin):
   ```sql
   CREATE DATABASE ecommerce_api;
   ```
   Ajusta en `.env` los datos de conexión si son distintos al valor por defecto de XAMPP:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ecommerce_api
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Corre las migraciones y los seeders** (crea las tablas y agrega productos de ejemplo + un cliente de prueba):
   ```bash
   php artisan migrate --seed
   ```

7. **Genera el secreto de JWT**:
   ```bash
   php artisan jwt:secret
   ```

8. **Configura tus claves de Stripe** en `.env` (usa las claves de prueba de tu cuenta, en https://dashboard.stripe.com/test/apikeys):
   ```
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   ```

9. **Genera la documentación de Swagger**:
   ```bash
   php artisan l5-swagger:generate
   ```

10. **Levanta el servidor**:
    ```bash
    php artisan serve
    ```

11. **Abre la documentación interactiva** en el navegador:
    `http://localhost:8000/api/documentation`

## Cliente de prueba

El seeder crea un cliente de prueba para no tener que registrar uno nuevo cada vez:

- Email: `cliente@example.com`
- Password: `password123`

## Endpoints principales

| Método | Ruta | Descripción | Requiere token |
|---|---|---|---|
| POST | `/api/register` | Registrar un cliente nuevo | No |
| POST | `/api/login` | Iniciar sesión y obtener el token JWT | No |
| GET | `/api/me` | Ver el perfil del cliente autenticado | Sí |
| POST | `/api/logout` | Cerrar sesión (invalidar el token) | Sí |
| POST | `/api/refresh` | Renovar el token JWT | Sí |
| GET | `/api/products` | Listar el catálogo de productos | No |
| GET | `/api/products/{id}` | Ver el detalle de un producto | No |
| POST | `/api/products` | Crear un producto | Sí |
| PUT | `/api/products/{id}` | Editar un producto | Sí |
| DELETE | `/api/products/{id}` | Eliminar un producto | Sí |
| GET | `/api/orders` | Historial de compras del cliente | Sí |
| POST | `/api/orders` | Crear una orden de compra | Sí |
| GET | `/api/orders/{id}` | Ver el detalle de una orden | Sí |
| POST | `/api/orders/{id}/pay` | Procesar el pago de una orden con Stripe | Sí |

Todo el detalle de cada endpoint (parámetros, cuerpos de petición, respuestas y códigos de error) está documentado en Swagger UI: `/api/documentation`.

### Cómo usar el token JWT

Después de `/api/login` o `/api/register`, la respuesta trae `data.authorization.token`. Ese token se manda en cada petición protegida en el header:

```
Authorization: Bearer {token}
```

En Swagger UI, se pone con el botón "Authorize" arriba a la derecha.

### Ejemplo de flujo completo

1. `POST /api/register` (o `/api/login` con el cliente de prueba) → obtener el token.
2. `GET /api/products` → ver el catálogo y elegir productos.
3. `POST /api/orders` con el body:
   ```json
   {
     "items": [
       { "product_id": 1, "quantity": 2 },
       { "product_id": 3, "quantity": 1 }
     ]
   }
   ```
4. `POST /api/orders/{id}/pay` con el body (tarjeta de prueba de Stripe):
   ```json
   { "payment_method": "pm_card_visa" }
   ```
5. `GET /api/orders` → ver el historial de compras, ya con el estado `paid`.

## Estructura de la base de datos

- **users**: clientes registrados (nombre, email, password).
- **products**: catálogo de productos (nombre, precio, stock, etc.).
- **orders**: órdenes de compra de un cliente, con un total y un estado (`pending`, `paid`, `cancelled`).
- **order_items**: detalle de productos dentro de cada orden (cantidad, precio unitario, subtotal).
- **payments**: registro de cada intento de pago con Stripe (id del PaymentIntent, monto, estado).

## Decisiones de implementación

- **Autenticación**: `tymon/jwt-auth`, con un guard `api` (driver `jwt`) definido en `config/auth.php`. El modelo `User` implementa `JWTSubject`.
- **Pagos**: `stripe/stripe-php`. `PaymentController` crea y confirma un `PaymentIntent` por el total de la orden; si Stripe confirma el pago, la orden pasa a `paid` y se guarda el registro en `payments`.
- **Validación**: cada endpoint que recibe datos usa su propio Form Request (`app/Http/Requests`), así el controlador no se llena de reglas de validación.
- **Errores consistentes**: en `bootstrap/app.php` se centraliza el manejo de errores para que toda la API (rutas bajo `/api`) responda siempre en JSON con el mismo formato `{ "success": false, "message": "..." }`, tanto para errores de validación (422) como de autenticación (401), recursos no encontrados (404) y errores del servidor (500).
- **Concurrencia en el stock**: al crear una orden, cada producto se bloquea con `lockForUpdate()` dentro de una transacción, para evitar que dos compras simultáneas dejen el stock en negativo.
- **Documentación**: `darkaonline/l5-swagger`, con anotaciones `@OA\...` directamente en los controladores de `app/Http/Controllers/Api`.

## Notas sobre Stripe

Para probar pagos sin usar tarjetas reales, Stripe ofrece métodos de pago de prueba ya creados, como `pm_card_visa` (pago exitoso) o `pm_card_chargeDeclined` (pago rechazado). Más métodos de prueba en la documentación oficial: https://stripe.com/docs/testing
