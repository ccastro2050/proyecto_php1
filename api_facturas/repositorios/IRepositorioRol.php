<?php
/**
 * IRepositorioRol — el CONTRATO de la capa de datos de `rol`.
 *
 * Una interfaz nativa de PHP: dice QUÉ operaciones existen, nunca CÓMO se
 * hacen. El servicio depende de esto, no de una clase concreta — y por eso
 * se puede probar con un repositorio falso, sin base de datos.
 *
 * Fíjese en que la llave viaja como `int` y no como `string`: la de `rol` es
 * un número que pone la base, no un código escrito a mano.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

require_once __DIR__ . '/../modelos/Rol.php';

interface IRepositorioRol
{
    /**
     * Devuelve hasta $limite fichas ordenadas por id.
     * @return Rol[]
     */
    public function obtenerTodos(int $limite): array;

    /** La ficha con esa llave, o null si no existe. */
    public function obtenerPorClave(int $id): ?Rol;

    /** Inserta la ficha (el id lo pone la base). true = insertada. */
    public function crear(Rol $rol): bool;

    /**
     * Escribe los campos de $datos (los usan PUT y PATCH). Va como array
     * porque un PATCH puede traer SOLO algunos campos.
     * Devuelve las filas afectadas (0 = esa llave no existe).
     */
    public function actualizar(int $id, array $datos): int;

    /** Elimina la ficha. Devuelve filas eliminadas (0 = no existía). */
    public function eliminar(int $id): int;
}
