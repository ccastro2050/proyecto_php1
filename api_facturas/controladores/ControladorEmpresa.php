<?php
/**
 * ControladorEmpresa — la capa HTTP de `empresa`.
 *
 * Lee la petición, VALIDA la forma del body (→ 422), delega al servicio y
 * responde JSON. Aquí no hay SQL ni reglas de negocio.
 *
 * `empresa` NO TIENE LLAVES FORÁNEAS, y por eso es de esta versión: es una de
 * las tablas de las que los demás van a depender. Mientras nadie dependa de
 * ella, crear y eliminar siempre funcionan.
 *
 * La traducción de excepciones, igual en los cinco métodos:
 *   Body con errores de forma → 422 (con la lista de errores)
 *   InvalidArgumentException  → 400 (regla de negocio)
 *   NoEncontradoExcepcion     → 404 (no existe)
 *   PDOException y cualquier otra → 500 (mensaje del motor en `detalle`)
 *
 * **Y ese 500 es deliberado.** Si alguien crea dos veces el mismo código, la
 * base lo rechaza por llave primaria y aquí sale un 500 — que es tan cierto
 * como inútil para quien lo lee. Traducirlo a un **409** es el trabajo de la
 * v2, cuando aparezcan las llaves foráneas y los rechazos se vuelvan el pan
 * de cada día. Adelantarlo aquí le quitaría a la v2 su lección principal.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioEmpresa.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

class ControladorEmpresa
{
    public function __construct(
        private readonly IServicioEmpresa $servicio,
    ) {
    }

    // ------------------------------------------------------------------
    // GET /api/empresa[?limite=N]  →  listar
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
                'tabla'  => 'empresa',
                'limite' => $limite,
                'total'  => count($datos),
                'datos'  => $datos,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responder(404, [
                'estado' => 404, 'mensaje' => 'Empresa no encontrada.', 'detalle' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // GET /api/empresa/{codigo}  →  obtener uno
    // ------------------------------------------------------------------
    public function obtener(string $codigo): void
    {
        try {
            $ficha = $this->servicio->obtener($codigo);
            $this->responder(200, $ficha->toArray());
        } catch (InvalidArgumentException $e) {
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responder(404, [
                'estado' => 404, 'mensaje' => 'Empresa no encontrada.', 'detalle' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // POST /api/empresa  →  crear
    // ------------------------------------------------------------------
    public function crear(array $body): void
    {
        // El codigo lo escribe quien crea la ficha, así que se valida como
        // un campo más.
        $errores = array_merge(
            $this->validarClave($body),
            $this->validarCampos($body, true),   // true = todos obligatorios
        );
        if ($errores !== []) {
            $this->responder(422, [
                'estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores,
            ]);
            return;
        }

        try {
            $this->servicio->crear($body);
            $this->responder(200, [
                'estado' => 200, 'mensaje' => 'Empresa creada exitosamente.',
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responder(404, [
                'estado' => 404, 'mensaje' => 'Empresa no encontrada.', 'detalle' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // PUT /api/empresa/{codigo}  →  reemplazo COMPLETO
    // ------------------------------------------------------------------
    public function reemplazar(string $codigo, array $body): void
    {
        // PUT reemplaza el recurso entero: los campos obligatorios
        // (nombre) deben venir todos. Omitir uno es 422, no
        // "déjalo como estaba" — para eso está PATCH.
        $errores = $this->validarCampos($body, true);
        if ($errores !== []) {
            $this->responder(422, [
                'estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores,
            ]);
            return;
        }

        try {
            $filas = $this->servicio->actualizar($codigo, $this->filtrarColumnas($body));
            $this->responder(200, [
                'estado' => 200, 'mensaje' => 'Empresa reemplazada exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responder(404, [
                'estado' => 404, 'mensaje' => 'Empresa no encontrada.', 'detalle' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // PATCH /api/empresa/{codigo}  →  actualización PARCIAL
    // ------------------------------------------------------------------
    public function actualizar(string $codigo, array $body): void
    {
        // El mismo body que PUT rechaza por incompleto, aquí pasa: PATCH
        // solo valida lo que llegó (false = nada es obligatorio).
        $errores = $this->validarCampos($body, false);
        if ($errores !== []) {
            $this->responder(422, [
                'estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores,
            ]);
            return;
        }

        try {
            $filas = $this->servicio->actualizar($codigo, $this->filtrarColumnas($body));
            $this->responder(200, [
                'estado' => 200, 'mensaje' => 'Empresa actualizada exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responder(404, [
                'estado' => 404, 'mensaje' => 'Empresa no encontrada.', 'detalle' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // DELETE /api/empresa/{codigo}  →  eliminar
    // ------------------------------------------------------------------
    public function eliminar(string $codigo): void
    {
        try {
            $filas = $this->servicio->eliminar($codigo);
            $this->responder(200, [
                'estado' => 200, 'mensaje' => 'Empresa eliminada exitosamente.',
                'filasEliminadas' => $filas,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responder(404, [
                'estado' => 404, 'mensaje' => 'Empresa no encontrada.', 'detalle' => $e->getMessage(),
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

    /** La llave: obligatoria, texto de 1 a 10 caracteres. */
    private function validarClave(array $datos): array
    {
        $codigo = $datos['codigo'] ?? null;
        if (!is_string($codigo) || $codigo === '' || strlen($codigo) > 10) {
            return ['El campo codigo es obligatorio: texto de 1 a 10 caracteres.'];
        }
        return [];
    }

    /**
     * Valida los campos de la ficha.
     * Con $obligatorios=true (POST/PUT) los obligatorios deben venir;
     * con false (PATCH) solo se valida lo que llegue.
     */
    private function validarCampos(array $datos, bool $obligatorios): array
    {
        $errores = [];

        if (array_key_exists('nombre', $datos)) {
            if (!is_string($datos['nombre']) || trim($datos['nombre']) === '' || strlen($datos['nombre']) > 100) {
                $errores[] = 'El campo nombre debe ser un texto de 1 a 100 caracteres.';
            }
        } elseif ($obligatorios) {
            $errores[] = 'El campo nombre es obligatorio.';
        }

        return $errores;
    }

    /** Lista blanca: los campos desconocidos se ignoran y jamás llegan al SQL. */
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
