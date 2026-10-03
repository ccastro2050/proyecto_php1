<?php
/**
 * IRepositorioUsuario — el CONTRATO de la capa de datos de `usuario`.
 *
 * Una interfaz nativa de PHP: dice QUÉ operaciones existen, nunca CÓMO se
 * hacen. El servicio depende de esto, no de una clase concreta.
 *
 * Fíjese en la forma de los métodos de escritura: la contraseña viaja como
 * un `string` SUELTO, no dentro del modelo `Usuario`. Es la consecuencia
 * directa de que el modelo no la tenga — entra por aquí y no vuelve a salir.
 *
 * Y fíjese en lo que NO hay: ningún método que devuelva la contraseña.
 * Comprobar una clave contra su hash es de la v3, y tendrá su propio método.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

require_once __DIR__ . '/../modelos/Usuario.php';

interface IRepositorioUsuario
{
    /**
     * Devuelve hasta $limite fichas ordenadas por email.
     * @return Usuario[]
     */
    public function obtenerTodos(int $limite): array;

    /** La ficha con esa llave, o null si no existe. */
    public function obtenerPorClave(string $email): ?Usuario;

    /** Inserta el usuario. La clave llega EN CLARO y se cifra aquí dentro. */
    public function crear(string $email, string $contrasena): bool;

    /**
     * Cambia la contraseña. Llega en claro y se cifra aquí dentro.
     * Devuelve las filas afectadas (0 = ese email no existe).
     */
    public function actualizarContrasena(string $email, string $contrasena): int;

    /** Elimina la ficha. Devuelve filas eliminadas (0 = no existía). */
    public function eliminar(string $email): int;
}
