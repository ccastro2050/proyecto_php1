<?php
/**
 * RepositorioUsuarioMariaDB — la capa de DATOS de `usuario`.
 *
 * Cumple IRepositorioUsuario con `implements`. PDO, prepared statements,
 * SQL a la vista.
 *
 * ======================================================================
 * AQUÍ —Y SOLO AQUÍ— SE CIFRA LA CONTRASEÑA
 * ======================================================================
 *
 * `password_hash()` de PHP es la función del lenguaje para esto, y hace dos
 * cosas que conviene saber:
 *
 *   · le agrega una **sal** distinta a cada clave, así que cifrar dos veces
 *     la misma contraseña da dos textos diferentes. Por eso un hash **no se
 *     compara con `===`**: para eso existe `password_verify()`, y eso llega
 *     en la v3;
 *   · y guarda el algoritmo dentro del propio texto, de modo que mañana se
 *     puede cambiar a uno más fuerte sin perder los usuarios viejos. Por eso
 *     se usa `PASSWORD_DEFAULT` y no un algoritmo con nombre.
 *
 * El resultado ocupa unos 60 caracteres; la columna es VARCHAR(200) para que
 * quepa también el de un algoritmo futuro más largo.
 *
 * **Y mire los dos SELECT: ninguno pide `contrasena`.** No es que se filtre
 * después — es que no se trae nunca.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioUsuario.php';
require_once __DIR__ . '/../modelos/Usuario.php';

class RepositorioUsuarioMariaDB implements IRepositorioUsuario
{
    private ?PDO $conexion = null;

    public function __construct(
        private readonly string $dsn,
        private readonly string $usuario,
        private readonly string $clave,
    ) {
    }

    // ------------------------------------------------------------------
    // Ayudantes privados
    // ------------------------------------------------------------------

    /** Abre la conexión PDO la primera vez y la reutiliza (perezosa). */
    private function obtenerConexion(): PDO
    {
        if ($this->conexion === null) {
            $this->conexion = new PDO($this->dsn, $this->usuario, $this->clave, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_FOUND_ROWS => true,
            ]);
        }
        return $this->conexion;
    }

    /** Una fila cruda convertida en objeto del modelo. */
    private function armarUsuario(array $fila): Usuario
    {
        return new Usuario(
            $fila['email'],
        );
    }

    // ------------------------------------------------------------------
    // Los 5 métodos del contrato
    // ------------------------------------------------------------------

    public function obtenerTodos(int $limite): array
    {
        // Solo `email`: la contraseña no se trae ni para mirarla.
        $sql = 'SELECT email FROM usuario ORDER BY email LIMIT :limite';
        $sentencia = $this->obtenerConexion()->prepare($sql);
        $sentencia->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sentencia->execute();

        $filas = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn(array $fila) => $this->armarUsuario($fila), $filas);
    }

    public function obtenerPorClave(string $email): ?Usuario
    {
        $sql = 'SELECT email FROM usuario WHERE email = :email';
        $sentencia = $this->obtenerConexion()->prepare($sql);
        $sentencia->execute(['email' => $email]);

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->armarUsuario($fila);
    }

    /** Inserta el usuario con la clave ya cifrada. true = insertado. */
    public function crear(string $email, string $contrasena): bool
    {
        $sql = 'INSERT INTO usuario (email, contrasena)
                VALUES (:email, :contrasena)';
        $sentencia = $this->obtenerConexion()->prepare($sql);

        $sentencia->execute([
            'email'      => $email,
            // Lo que se guarda es el hash. La clave en claro muere aquí.
            'contrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
        ]);

        return $sentencia->rowCount() === 1;
    }

    public function actualizarContrasena(string $email, string $contrasena): int
    {
        $sql = 'UPDATE usuario SET contrasena = :contrasena WHERE email = :email';
        $sentencia = $this->obtenerConexion()->prepare($sql);
        $sentencia->execute([
            'email'      => $email,
            'contrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
        ]);
        return $sentencia->rowCount();
    }

    public function eliminar(string $email): int
    {
        $sql = 'DELETE FROM usuario WHERE email = :email';
        $sentencia = $this->obtenerConexion()->prepare($sql);
        $sentencia->execute(['email' => $email]);
        return $sentencia->rowCount();
    }
}
