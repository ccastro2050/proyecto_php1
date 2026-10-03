<?php
/**
 * RepositorioPersonaMariaDB — la capa de DATOS de `persona`.
 *
 * Cumple IRepositorioPersona con `implements`. Igual que el repositorio de
 * producto: PDO, prepared statements, SQL a la vista.
 *
 * **Las escrituras no llevan try/catch, y es a propósito.** Si la base
 * rechaza —por ejemplo, un código repetido—, la PDOException sube sin que
 * nadie la toque y el controlador la responde como 500. Atraparla aquí para
 * traducirla a un 409 es trabajo de la v2, cuando esta tabla empiece a tener
 * quien dependa de ella.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioPersona.php';
require_once __DIR__ . '/../modelos/Persona.php';

class RepositorioPersonaMariaDB implements IRepositorioPersona
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
    private function armarPersona(array $fila): Persona
    {
        return new Persona(
            $fila['codigo'],
            $fila['nombre'],
            $fila['email'],
            $fila['telefono'],
        );
    }

    // ------------------------------------------------------------------
    // Los 5 métodos del contrato
    // ------------------------------------------------------------------

    public function obtenerTodos(int $limite): array
    {
        $sql = 'SELECT codigo, nombre, email, telefono FROM persona ORDER BY codigo LIMIT :limite';
        $sentencia = $this->obtenerConexion()->prepare($sql);
        $sentencia->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sentencia->execute();

        $filas = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn(array $fila) => $this->armarPersona($fila), $filas);
    }

    public function obtenerPorClave(string $codigo): ?Persona
    {
        $sql = 'SELECT codigo, nombre, email, telefono FROM persona WHERE codigo = :codigo';
        $sentencia = $this->obtenerConexion()->prepare($sql);
        $sentencia->execute(['codigo' => $codigo]);

        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->armarPersona($fila);
    }

    /** Inserta la ficha. true = insertada. */
    public function crear(Persona $persona): bool
    {
        $sql = 'INSERT INTO persona (codigo, nombre, email, telefono)
                VALUES (:codigo, :nombre, :email, :telefono)';
        $sentencia = $this->obtenerConexion()->prepare($sql);

        $sentencia->execute([
            'codigo' => $persona->getCodigo(),
            'nombre' => $persona->getNombre(),
            'email' => $persona->getEmail(),
            'telefono' => $persona->getTelefono(),
        ]);

        return $sentencia->rowCount() === 1;
    }

    public function actualizar(string $codigo, array $datos): int
    {
        // SET dinámico SOLO con las columnas que llegaron. Los NOMBRES salen
        // de la lista blanca del controlador, nunca del cliente; los VALORES
        // siempre van como parámetros.
        $asignaciones = [];
        foreach (array_keys($datos) as $columna) {
            $asignaciones[] = "$columna = :$columna";
        }
        $sql = 'UPDATE persona SET ' . implode(', ', $asignaciones)
             . ' WHERE codigo = :codigo_clave';

        $sentencia = $this->obtenerConexion()->prepare($sql);
        $sentencia->execute($datos + ['codigo_clave' => $codigo]);
        return $sentencia->rowCount();
    }

    public function eliminar(string $codigo): int
    {
        $sql = 'DELETE FROM persona WHERE codigo = :codigo';
        $sentencia = $this->obtenerConexion()->prepare($sql);
        $sentencia->execute(['codigo' => $codigo]);
        return $sentencia->rowCount();
    }
}
