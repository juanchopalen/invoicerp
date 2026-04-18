# InvoicERP — Flujo para probar

Esta guía describe cómo preparar el entorno y probar la API fiscal y el panel Filament sin duplicar la lógica de negocio fuera del core documentado en el plan arquitectónico.

## Requisitos previos

1. PHP 8.3+, Composer, base de datos (MySQL o SQLite según `.env`).
2. Variables de entorno configuradas en `.env` (`APP_URL`, `DB_*`).
3. Migraciones y seed ejecutados:

```bash
cd /ruta/al/proyecto
php artisan migrate
php artisan db:seed
```

El seeder crea un **tenant** `default` y un usuario de panel (revisa `database/seeders/DatabaseSeeder.php` para email y contraseña).
Tambien inicializa catalogos de localizacion (pais/estado/ciudad) con `laravel-world` configurado para Venezuela (`VE`).

## Autenticación en la API

Todas las rutas bajo `POST|GET /api/v1/...` exigen **una** de estas credenciales en el header:

```http
Authorization: Bearer <token>
```

### Opción A — Token Sanctum (usuario con `tenant_id`)

El usuario debe tener `tenant_id` asignado (el seeder lo hace). Obtén un token de acceso personal, por ejemplo desde `php artisan tinker`:

```php
$user = \App\Models\User::where('email', 'admin@invoicerp.net')->first();
$user->createToken('prueba')->plainTextToken;
```

Usa el valor devuelto completo como `<token>`.

### Opción B — Clave de cliente API (`api_clients`)

Formato: `prefijo.secreto` (un solo string en el Bearer). La clave en claro solo se muestra **una vez** al crear el cliente en Filament (**Fiscal → API clients → Crear**).

En base de datos solo se guarda el hash; la verificación compara el Bearer completo contra ese hash.

## Endpoints disponibles

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/v1/documents/emit` | Emite un documento (idempotente por tenant + `source_system` + `external_reference`). |
| `POST` | `/api/v1/documents/cancel` | Anula un documento `issued` (por `document_id` o por `source_system` + `external_reference`). |
| `GET` | `/api/v1/documents/{id}` | Consulta un documento por ID numérico. |

Cabeceras opcionales de trazabilidad:

- `X-Correlation-Id`: UUID o string; si no envías, el servidor genera uno.
- `X-Request-Id`: identificador de petición (opcional).

La aplicación registra correlación en `audit_logs` cuando el core escribe auditoría.

## 1. Ejemplo para Postman

### Entorno (Environment)

Crea un entorno con variables reutilizables:

| Variable | Ejemplo | Uso |
|----------|---------|-----|
| `base_url` | `http://127.0.0.1:8000` | Origen sin barra final |
| `token` | *(token Sanctum o `prefijo.secreto` de API client)* | Cabecera `Authorization` |

### Petición: emitir documento

1. **Método y URL:** `POST` → `{{base_url}}/api/v1/documents/emit`
2. **Authorization:** tipo **Bearer Token** y pega el valor de `token` (Postman envía `Authorization: Bearer <valor>`).
3. **Headers** (pestaña *Headers*):
   - `Content-Type`: `application/json`
   - *(Opcional)* `X-Correlation-Id`: un UUID o texto para trazar la petición en auditoría.
4. **Body:** pestaña **raw**, tipo **JSON**:

```json
{
  "source_system": "postman",
  "external_reference": "pedido-1001",
  "document_type": "invoice",
  "currency": "VES",
  "schema_version": 1,
  "items": [
    {
      "line_number": 1,
      "description": "Servicio",
      "qty": "1",
      "unit_price": "100.0000",
      "tax_rate": "0",
      "line_subtotal": "100.0000",
      "line_tax": "0.0000",
      "line_total": "100.0000"
    }
  ]
}
```

5. **Send** — espera **201 Created** la primera vez; si repites el mismo JSON, **200 OK** (mismo `id`, idempotencia).

### Peticiones relacionadas en la misma colección

- **GET** `{{base_url}}/api/v1/documents/{{document_id}}` — guarda el `id` devuelto por emit en una variable de entorno `document_id` para reutilizarlo.
- **POST** `{{base_url}}/api/v1/documents/cancel` — Body JSON, por ejemplo:

```json
{
  "document_id": 1,
  "reason": "Prueba desde Postman"
}
```

*(Sustituye `document_id` por el valor real o usa `source_system` + `external_reference` en lugar de `document_id`.)*

**Nota:** Si Postman muestra errores de SSL en local, en *Settings → General* desactiva **SSL certificate verification** solo para desarrollo, o usa `http://` en `base_url`.

## Flujo de prueba recomendado (API con curl)

Sustituye `APP_URL` y `TOKEN` por tus valores. En local suele ser `http://127.0.0.1:8000` o `http://localhost:8000`.

### 1. Emitir un documento (`201 Created`)

```bash
curl -sS -X POST "${APP_URL}/api/v1/documents/emit" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -H "X-Correlation-Id: $(uuidgen 2>/dev/null || echo manual-test-1)" \
  -d '{
    "source_system": "curl",
    "external_reference": "pedido-1001",
    "document_type": "invoice",
    "currency": "VES",
    "schema_version": 1,
    "items": [
      {
        "line_number": 1,
        "description": "Servicio",
        "qty": "1",
        "unit_price": "100.0000",
        "tax_rate": "0",
        "line_subtotal": "100.0000",
        "line_tax": "0.0000",
        "line_total": "100.0000"
      }
    ]
  }'
```

Respuesta esperada: código `201` y cuerpo JSON con `id`, `document_number`, `hash`, `issued_at`, `items`, etc.

### 2. Idempotencia — repetir la misma petición (`200 OK`)

Vuelve a ejecutar **exactamente** el mismo cuerpo JSON que en el paso 1.

- Primera emisión: `201`.
- Reintentos con el mismo cuerpo: `200` y el **mismo** `id` (no se crea un segundo documento).

Si repites la misma clave (`source_system` + `external_reference`) pero **cambias** el cuerpo (por ejemplo otra descripción de línea), la API responde `409` (conflicto de idempotencia).

### 3. Consultar el documento (`200 OK`)

Usa el `id` devuelto en el paso 1:

```bash
curl -sS "${APP_URL}/api/v1/documents/1" \
  -H "Authorization: Bearer ${TOKEN}"
```

(Sustituye `1` por el `id` real.)

### 4. Anular el documento (`200 OK`)

Con el mismo `id`:

```bash
curl -sS -X POST "${APP_URL}/api/v1/documents/cancel" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "document_id": 1,
    "reason": "Prueba de anulación"
  }'
```

Alternativa por referencia externa:

```json
{
  "source_system": "curl",
  "external_reference": "pedido-1001",
  "reason": "Anulación por referencia"
}
```

Debes enviar **o** `document_id` **o** el par `source_system` + `external_reference`.

## Flujo de prueba en Filament (panel)

1. Arranca el servidor: `php artisan serve` (y Vite si usas el front; el panel Filament sirve por su propia ruta).
2. Abre el panel: `{APP_URL}/admin` (ruta por defecto del proveedor Filament).
3. Inicia sesión con el usuario seed (con **tenant** asignado; sin tenant no podrás acceder al panel).
4. Navega a **Fiscal**:
   - **Documentos fiscales**: listado solo lectura; acciones **Emitir documento** (modal) y **Cancelar** en fila si el estado es `issued`.
   - **Clientes API**: crea un cliente y copia la clave mostrada una sola vez para pruebas con curl.
   - **Registros de auditoría**: solo lectura de `audit_logs` del tenant.
5. El panel **no** implementa reglas fiscales en Livewire: las mutaciones van por HTTP a la misma API (`InvoiErpApiClient` + token Sanctum generado al vuelo).

## Verificación en base de datos

Tras las pruebas puedes revisar:

- `fiscal_documents` — documentos emitidos o anulados; correlativo por tenant y `document_type` en `tenant_document_sequences`.
- `fiscal_document_items` — líneas ligadas al documento.
- `audit_logs` — acciones como `documents.emit`, `documents.emit.idempotent`, `documents.cancel`, etc.

## Errores frecuentes

| Síntoma | Causa probable |
|---------|----------------|
| `401` / `Invalid credentials` | Bearer incorrecto, usuario sin token válido, o API key mal copiada. |
| `403` (usuario) | Usuario sin `tenant_id`. |
| `409` al emitir | Misma clave de idempotencia con **distinto** payload que la primera emisión. |
| `422` al cancelar | Documento no está en estado `issued` o datos incompletos (`document_id` vs par fuente/referencia). |

## Referencia rápida de rutas

Prefijo global de Laravel: las rutas definidas en `routes/api.php` quedan bajo **`/api`**, es decir:

- `POST /api/v1/documents/emit`
- `POST /api/v1/documents/cancel`
- `GET /api/v1/documents/{id}`

Archivo de rutas: `routes/api.php`.
