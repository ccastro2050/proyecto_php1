<?php
/**
 * IServicioUsuario — el CONTRATO de la capa de negocio de `usuario`.
 *
 * El controlador depende de esta interfaz, no de la clase concreta.
 *
 * No tiene un `actualizar(array $datos)` genérico como los demás recursos, y
 * es porque de un usuario **solo se puede cambiar la clave**: el email es la
 * llave primaria. Un método que prometiera más de eso estaría mintiendo.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

require_once __DIR__ . '/../modelos/Usuario.php';

interface IServicioUsuario
{
    /** @return Usuario[] */
    public function listar(int $limite): array;

    /** La ficha, o NoEncontradoExcepcion si no existe. */
    public function obtener(string $email): Usuario;

    /** Crea el usuario con su clave. */
    public function crear(string $email, string $contrasena): void;

    /** Cambia la clave. Devuelve filas afectadas. */
    public function actualizarContrasena(string $email, ?string $contrasena): int;

    /** Elimina. Devuelve filas eliminadas. */
    public function eliminar(string $email): int;
}
