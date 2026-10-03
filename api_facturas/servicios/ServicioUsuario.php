<?php
/**
 * ServicioUsuario — la capa de NEGOCIO de `usuario`.
 *
 * Recibe la INTERFAZ del repositorio por constructor. No conoce HTTP —
 * comunica los problemas con excepciones que el controlador traduce.
 *
 * **Y no sabe nada de cifrado.** La palabra `password_hash` no aparece en este
 * archivo: la clave le llega en claro, la pasa en claro, y quien la cifra es
 * el repositorio. Si mañana se cambia el algoritmo, este archivo no se toca —
 * que es justamente para lo que sirve tener capas.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

require_once __DIR__ . '/IServicioUsuario.php';
require_once __DIR__ . '/../repositorios/IRepositorioUsuario.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../modelos/Usuario.php';

class ServicioUsuario implements IServicioUsuario
{
    public function __construct(
        private readonly IRepositorioUsuario $repositorio,
    ) {
    }

    // ------------------------------------------------------------------
    // Validación de negocio de la llave
    // ------------------------------------------------------------------

    private function validarClave(string $email): string
    {
        $email = trim($email);
        if ($email === '') {
            throw new InvalidArgumentException(
                'El email del usuario no puede estar vacío.'
            );
        }
        return $email;
    }

    // ------------------------------------------------------------------
    // Operaciones de negocio
    // ------------------------------------------------------------------

    public function listar(int $limite): array
    {
        if ($limite <= 0) {
            throw new InvalidArgumentException('El límite debe ser un entero mayor que cero.');
        }
        return $this->repositorio->obtenerTodos($limite);
    }

    public function obtener(string $email): Usuario
    {
        $email = $this->validarClave($email);
        $usuario = $this->repositorio->obtenerPorClave($email);
        if ($usuario === null) {
            throw new NoEncontradoExcepcion("No existe un usuario con email = $email");
        }
        return $usuario;
    }

    public function crear(string $email, string $contrasena): void
    {
        $this->repositorio->crear($email, $contrasena);
    }

    public function actualizarContrasena(string $email, ?string $contrasena): int
    {
        $email = $this->validarClave($email);

        // El PATCH con body {} llega aquí con la clave en null: la forma era
        // válida y aun así no hay nada que escribir. Es una REGLA DE NEGOCIO,
        // no un error de forma — por eso sale como 400 y no como 422.
        if ($contrasena === null || $contrasena === '') {
            throw new InvalidArgumentException('No se envió ninguna contraseña nueva.');
        }

        $filasAfectadas = $this->repositorio->actualizarContrasena($email, $contrasena);
        if ($filasAfectadas === 0) {
            throw new NoEncontradoExcepcion("No existe un usuario con email = $email");
        }
        return $filasAfectadas;
    }

    public function eliminar(string $email): int
    {
        $email = $this->validarClave($email);
        $filasEliminadas = $this->repositorio->eliminar($email);
        if ($filasEliminadas === 0) {
            throw new NoEncontradoExcepcion("No existe un usuario con email = $email");
        }
        return $filasEliminadas;
    }
}
