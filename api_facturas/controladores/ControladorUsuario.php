<?php
/**
 * ControladorUsuario — la capa HTTP de `usuario`.
 *
 * Lee la petición, VALIDA la forma del body (→ 422), delega al servicio y
 * responde JSON. Aquí no hay SQL ni reglas de negocio.
 *
 * `usuario` NO TIENE LLAVES FORÁNEAS —quién tiene qué rol es la tabla
 * `rol_usuario`, y ésa es de la v2—, así que su CRUD es de esta versión.
 *
 * **PUT y PATCH hacen lo mismo aquí, y es correcto:** de un usuario solo se
 * puede cambiar la contraseña, porque el email es la llave primaria. Lo que
 * los distingue es la exigencia: el PUT pide la clave obligatoriamente; el
 * PATCH acepta un body sin ella, y entonces el servicio responde 400 porque
 * no hay nada que escribir.
 *
 * **Y la contraseña nunca viaja de vuelta.** El modelo `Usuario` no la tiene
 * (vea su cabecera), así que ninguna respuesta de este controlador la puede
 * incluir ni por descuido.
 *
 * La traducción de excepciones, igual en los cinco métodos:
 *   Body con errores de forma → 422 (con la lista de errores)
 *   InvalidArgumentException  → 400 (regla de negocio)
 *   NoEncontradoExcepcion     → 404 (no existe)
 *   PDOException y cualquier otra → 500 (mensaje del motor en `detalle`)
 *
 * **Y ese 500 es deliberado.** Crear dos veces el mismo email lo rechaza la
 * base por llave primaria y aquí sale un 500. El **409** es de la v2.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioUsuario.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

class ControladorUsuario
{
    public function __construct(
        private readonly IServicioUsuario $servicio,
    ) {
    }

    // ------------------------------------------------------------------
    // GET /api/usuario[?limite=N]  →  listar
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
                'tabla'  => 'usuario',
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
    // GET /api/usuario/{email}  →  obtener uno
    // ------------------------------------------------------------------
    public function obtener(string $email): void
    {
        try {
            $ficha = $this->servicio->obtener($email);
            $this->responder(200, $ficha->toArray());
        } catch (InvalidArgumentException $e) {
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responder(404, [
                'estado' => 404, 'mensaje' => 'Usuario no encontrado.', 'detalle' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // POST /api/usuario  →  crear
    // ------------------------------------------------------------------
    public function crear(array $body): void
    {
        $errores = array_merge(
            $this->validarClave($body),
            $this->validarContrasena($body, true),
        );
        if ($errores !== []) {
            $this->responder(422, [
                'estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores,
            ]);
            return;   // con errores de forma no se sigue: nada llegó a la BD
        }

        try {
            $this->servicio->crear($body['email'], $body['contrasena']);
            $this->responder(200, [
                'estado' => 200, 'mensaje' => 'Usuario creado exitosamente.',
            ]);
        } catch (Throwable $e) {
            // Ej.: email duplicado — la BD rechaza por llave primaria:
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // PUT /api/usuario/{email}  →  reemplazo COMPLETO (= la contraseña)
    // ------------------------------------------------------------------
    public function reemplazar(string $email, array $body): void
    {
        // El PUT exige la contraseña: es el único campo que se puede escribir.
        $errores = $this->validarContrasena($body, true);
        if ($errores !== []) {
            $this->responder(422, [
                'estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores,
            ]);
            return;
        }

        $this->escribirContrasena($email, $body['contrasena'] ?? null, 'reemplazado');
    }

    // ------------------------------------------------------------------
    // PATCH /api/usuario/{email}  →  actualización PARCIAL (= la contraseña)
    // ------------------------------------------------------------------
    public function actualizar(string $email, array $body): void
    {
        // El PATCH solo valida lo que llegue: si no llega nada, la forma es
        // válida y el 400 lo decide el servicio (ver su `actualizarContrasena`).
        $errores = $this->validarContrasena($body, false);
        if ($errores !== []) {
            $this->responder(422, [
                'estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores,
            ]);
            return;
        }

        $this->escribirContrasena($email, $body['contrasena'] ?? null, 'actualizado');
    }

    // ------------------------------------------------------------------
    // DELETE /api/usuario/{email}  →  eliminar
    // ------------------------------------------------------------------
    public function eliminar(string $email): void
    {
        try {
            $filas = $this->servicio->eliminar($email);
            $this->responder(200, [
                'estado' => 200, 'mensaje' => 'Usuario eliminado exitosamente.',
                'filasEliminadas' => $filas,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responder(404, [
                'estado' => 404, 'mensaje' => 'Usuario no encontrado.', 'detalle' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->responder(500, [
                'estado' => 500, 'mensaje' => 'Error interno.', 'detalle' => $e->getMessage(),
            ]);
        }
    }

    // ==================================================================
    // Lo común a PUT y PATCH: escribir la contraseña
    // ==================================================================

    /**
     * PUT y PATCH terminan aquí porque hacen lo mismo. Lo único que cambia es
     * la palabra del mensaje, que es lo que viene en $verbo.
     */
    private function escribirContrasena(string $email, ?string $contrasena, string $verbo): void
    {
        try {
            $filas = $this->servicio->actualizarContrasena($email, $contrasena);
            $this->responder(200, [
                'estado' => 200, 'mensaje' => "Usuario $verbo exitosamente.",
                'filasAfectadas' => $filas,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responder(400, [
                'estado' => 400, 'mensaje' => 'Parámetros inválidos.', 'detalle' => $e->getMessage(),
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responder(404, [
                'estado' => 404, 'mensaje' => 'Usuario no encontrado.', 'detalle' => $e->getMessage(),
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

    /** La llave: obligatoria, texto de 1 a 100 caracteres. */
    private function validarClave(array $datos): array
    {
        $email = $datos['email'] ?? null;
        if (!is_string($email) || trim($email) === '' || strlen($email) > 100) {
            return ['El campo email es obligatorio: texto de 1 a 100 caracteres.'];
        }
        return [];
    }

    /**
     * La contraseña. Con $obligatoria=true (POST/PUT) tiene que venir; con
     * false (PATCH) solo se valida si llega.
     *
     * El mínimo de 6 es una decisión de este curso, escrita también en el
     * `2_spec.md`: el ancho de la columna (200) no dice nada sobre lo corta
     * que puede ser una clave.
     */
    private function validarContrasena(array $datos, bool $obligatoria): array
    {
        if (array_key_exists('contrasena', $datos)) {
            $clave = $datos['contrasena'];
            if (!is_string($clave) || strlen($clave) < 6 || strlen($clave) > 200) {
                return ['El campo contrasena debe ser un texto de 6 a 200 caracteres.'];
            }
            return [];
        }
        return $obligatoria ? ['El campo contrasena es obligatorio.'] : [];
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
