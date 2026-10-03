<?php
/**
 * ControladorRol — la capa HTTP de `rol`.
 *
 * Lee la petición, VALIDA la forma del body (→ 422), delega al servicio y
 * responde JSON. Aquí no hay SQL ni reglas de negocio.
 *
 * `rol` NO TIENE LLAVES FORÁNEAS, y por eso es de esta versión. Pero trae algo
 * que ninguna otra tabla de la v1 tiene, y conviene verlo aquí:
 *
 * **LA LLAVE LA GENERA LA BASE.** `id` es `AUTO_INCREMENT`, así que:
 *   - el POST **no la manda** — no hay `validarClave` para el body, como sí la
 *     hay en `producto` y en `empresa`;
 *   - si alguien la manda de todos modos, la lista blanca de
 *     `filtrarColumnas` la ignora en silencio y nunca llega al SQL. No es un
 *     descuido: el cliente no decide las llaves de esta tabla;
 *   - y en la URL el id viaja como número. Quién comprueba que de verdad sea
 *     un número es el enrutador (`index.php`), antes de llegar aquí.
 *
 * La traducción de excepciones, igual en los cinco métodos:
 *   Body con errores de forma → 422 (con la lista de errores)
 *   InvalidArgumentException  → 400 (regla de negocio)
 *   NoEncontradoExcepcion     → 404 (no existe)
 *   PDOException y cualquier otra → 500 (mensaje del motor en `detalle`)
 *
 * **Y ese 500 es deliberado.** Si alguien repite un nombre de rol o borra uno
 * que ya está en uso, la base lo rechaza y aquí sale un 500 — tan cierto como
 * inútil para quien lo lee. Traducirlo a un **409** es el trabajo de la v2.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioRol.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

class ControladorRol
{
    public function __construct(
        private readonly IServicioRol $servicio,
    ) {
    }

    // ------------------------------------------------------------------
    // GET /api/rol[?limite=N]  →  listar
    // ------------------------------------------------------------------
    public function listar(): void
    {
        if (isset($_GET['limite'])) {
            $limite = (int) $_GET['limite'];
        } else {
            $limite = 1000;
        }

        try {
            $fichas = $this->servicio->listar($limite);

            if ($fichas === []) {
                http_response_code(204);   // 204 = éxito SIN contenido: tabla vacía
                return;
            }
            $datos = [];
            foreach ($fichas as $ficha) {
                $datos[] = $ficha->toArray();
            }
            $this->responder(200, [
                'tabla'  => 'rol',
                'limite' => $limite,
                'total'  => count($datos),
                'datos'  => $datos,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // GET /api/rol/{id}  →  obtener uno
    // ------------------------------------------------------------------
    public function obtener(int $id): void
    {
        try {
            $ficha = $this->servicio->obtener($id);
            $this->responder(200, $ficha->toArray());
        } catch (InvalidArgumentException $e) {
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responder(404, [
                'estado' => 404, 'mensaje' => 'Rol no encontrado.', 'detalle' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // POST /api/rol  →  crear
    // ------------------------------------------------------------------
    public function crear(array $body): void
    {
        // Solo se validan los CAMPOS: el id no viene en el body (lo pone la
        // base). Compárelo con `ControladorEmpresa`, donde además hay que
        // validar la llave que manda el cliente.
        $errores = $this->validarCampos($body, true);
        if ($errores !== []) {
            $this->responder(422, [
                'estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores,
            ]);
            return;   // con errores de forma no se sigue: nada llegó a la BD
        }

        try {
            $this->servicio->crear($this->filtrarColumnas($body));
            $this->responder(200, [
                'estado' => 200, 'mensaje' => 'Rol creado exitosamente.',
            ]);
        } catch (Throwable $e) {
            // Ej.: nombre repetido, si la tabla tuviera ese índice:
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // PUT /api/rol/{id}  →  reemplazo COMPLETO
    // ------------------------------------------------------------------
    public function reemplazar(int $id, array $body): void
    {
        // PUT exige TODOS los campos (el id va en la URL): un PUT con body
        // parcial muere aquí con 422 — esa es la semántica de PUT.
        $errores = $this->validarCampos($body, true);   // true = todos obligatorios
        if ($errores !== []) {
            $this->responder(422, [
                'estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores,
            ]);
            return;
        }

        try {
            $filas = $this->servicio->actualizar($id, $this->filtrarColumnas($body));
            $this->responder(200, [
                'estado' => 200, 'mensaje' => 'Rol reemplazado exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responder(404, [
                'estado' => 404, 'mensaje' => 'Rol no encontrado.', 'detalle' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // PATCH /api/rol/{id}  →  actualización PARCIAL
    // ------------------------------------------------------------------
    public function actualizar(int $id, array $body): void
    {
        // PATCH valida solo lo que llegue (false = nada es obligatorio).
        $errores = $this->validarCampos($body, false);
        if ($errores !== []) {
            $this->responder(422, [
                'estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores,
            ]);
            return;
        }

        try {
            $filas = $this->servicio->actualizar($id, $this->filtrarColumnas($body));
            $this->responder(200, [
                'estado' => 200, 'mensaje' => 'Rol actualizado exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (InvalidArgumentException $e) {
            // Aquí cae el PATCH con body vacío: la forma era válida, pero no
            // se mandó ningún campo — y eso lo decide el servicio, no el HTTP.
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responder(404, [
                'estado' => 404, 'mensaje' => 'Rol no encontrado.', 'detalle' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // DELETE /api/rol/{id}  →  eliminar
    // ------------------------------------------------------------------
    public function eliminar(int $id): void
    {
        try {
            $filas = $this->servicio->eliminar($id);
            $this->responder(200, [
                'estado' => 200, 'mensaje' => 'Rol eliminado exitosamente.',
                'filasEliminadas' => $filas,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responder(404, [
                'estado' => 404, 'mensaje' => 'Rol no encontrado.', 'detalle' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ==================================================================
    // LA VALIDACIÓN DEL BODY (los ifs de la frontera HTTP → 422)
    // ==================================================================

    /**
     * Valida los campos de la ficha.
     * Con $obligatorios=true (POST/PUT) los obligatorios deben venir;
     * con false (PATCH) solo se valida lo que llegue.
     *
     * No hay `validarClave`: el id no viaja en el body de esta tabla.
     */
    private function validarCampos(array $datos, bool $obligatorios): array
    {
        $errores = [];

        if (array_key_exists('nombre', $datos)) {
            // 50 es el ancho real de la columna en la base (VARCHAR(50)):
            // validar con otro número aquí dejaría que la base rechace algo
            // que la API había aceptado.
            if (!is_string($datos['nombre']) || trim($datos['nombre']) === '' || strlen($datos['nombre']) > 50) {
                $errores[] = 'El campo nombre debe ser un texto de 1 a 50 caracteres.';
            }
        } elseif ($obligatorios) {
            $errores[] = 'El campo nombre es obligatorio.';
        }

        return $errores;
    }

    /**
     * Lista blanca: los campos desconocidos se ignoran y jamás llegan al SQL.
     *
     * `id` no está en la lista, y eso es lo que hace que mandarlo no sirva de
     * nada: la llave de esta tabla es de la base.
     */
    private function filtrarColumnas(array $body): array
    {
        $datos = [];
        if (array_key_exists('nombre', $body)) {
            $datos['nombre'] = $body['nombre'];
        }
        return $datos;
    }

    // ------------------------------------------------------------------
    // Respuesta: SIEMPRE se sale por aquí
    // ------------------------------------------------------------------

    /** Escribe el código de estado y el cuerpo JSON de la respuesta. */
    private function responder(int $estado, array $cuerpo): void
    {
        http_response_code($estado);
        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    }
}
