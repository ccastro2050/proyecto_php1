<?php
/**
 * RepositorioRutaMariaDB — la capa de DATOS de `ruta`.
 *
 * Cumple IRepositorioRuta con `implements`. Igual que el repositorio de
 * producto: PDO, prepared statements, SQL a la vista.
 *
 * Dos cosas propias de esta tabla:
 *   - el INSERT **no nombra `id`**: está declarada `AUTO_INCREMENT` y la base
 *     la pone sola (igual que en `rol`);
 *   - la columna `ruta` tiene un índice UNIQUE (`uq_ruta`) en la base, así que
 *     repetir una ruta es un rechazo del motor. **No se atrapa aquí:** la
 *     PDOException sube y el controlador la responde como 500. Traducirla a un
 *     409 entendible es trabajo de la v2.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioRuta.php';
require_once __DIR__ . '/../modelos/Ruta.php';

class RepositorioRutaMariaDB implements IRepositorioRuta
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

    /** Una fila cruda convertida en objeto del modelo, con sus tipos. */
    private function armarRuta(array $fila): Ruta
    {
        // (int) no es adorno: MariaDB entrega los números como texto, y sin
        // esta conversión el JSON saldría con "id": "3" en vez de "id": 3.
        return new Ruta(
            (int) $fila['id'],
            $fila['ruta'],
            $fila['descripcion'],
        );
    }

    // ------------------------------------------------------------------
    // Los 5 métodos del contrato
    // ------------------------------------------------------------------

    public function obtenerTodos(int $limite): array
    {
        $sql = 'SELECT id, ruta, descripcion FROM ruta ORDER BY id LIMIT :limite';
        $sentencia = $this->obtenerConexion()->prepare($sql);
        $sentencia->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sentencia->execute();

        $filas = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn(array $fila) => $this->armarRuta($fila), $filas);
    }

    public function obtenerPorClave(int $id): ?Ruta
    {
        $sql = 'SELECT id, ruta, descripcion FROM ruta WHERE id = :id';
        $sentencia = $this->obtenerConexion()->prepare($sql);
        $sentencia->execute(['id' => $id]);

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->armarRuta($fila);
    }

    /** Inserta la ficha. true = insertada. */
    public function crear(Ruta $ruta): bool
    {
        // Fíjese en que `id` NO aparece: es AUTO_INCREMENT y lo pone la base.
        $sql = 'INSERT INTO ruta (ruta, descripcion)
                VALUES (:ruta, :descripcion)';
        $sentencia = $this->obtenerConexion()->prepare($sql);

        $sentencia->execute([
            'ruta'        => $ruta->getRuta(),
            'descripcion' => $ruta->getDescripcion(),
        ]);

        return $sentencia->rowCount() === 1;
    }

    public function actualizar(int $id, array $datos): int
    {
        // SET dinámico SOLO con las columnas que llegaron. Los NOMBRES salen
        // de la lista blanca del controlador, nunca del cliente; los VALORES
        // siempre van como parámetros.
        $asignaciones = [];
        foreach (array_keys($datos) as $columna) {
            $asignaciones[] = "$columna = :$columna";
        }
        $sql = 'UPDATE ruta SET ' . implode(', ', $asignaciones)
             . ' WHERE id = :id_clave';

        $sentencia = $this->obtenerConexion()->prepare($sql);
        $sentencia->execute($datos + ['id_clave' => $id]);
        return $sentencia->rowCount();
    }

    public function eliminar(int $id): int
    {
        $sql = 'DELETE FROM ruta WHERE id = :id';
        $sentencia = $this->obtenerConexion()->prepare($sql);
        $sentencia->execute(['id' => $id]);
        return $sentencia->rowCount();
    }
}
