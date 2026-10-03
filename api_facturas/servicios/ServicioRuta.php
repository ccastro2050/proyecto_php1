<?php
/**
 * ServicioRuta — la capa de NEGOCIO de `ruta`.
 *
 * Recibe la INTERFAZ del repositorio por constructor: no sabe si detrás hay
 * MariaDB o un falso en memoria. No conoce HTTP — comunica los problemas con
 * excepciones que el controlador traduce.
 *
 * Y no sabe nada del índice UNIQUE de la columna `ruta`: esa es una regla que
 * defiende la base de datos. El negocio no la repite — repetirla aquí sería
 * tener la misma regla en dos sitios, lista para desincronizarse.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

require_once __DIR__ . '/IServicioRuta.php';
require_once __DIR__ . '/../repositorios/IRepositorioRuta.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../modelos/Ruta.php';

class ServicioRuta implements IServicioRuta
{
    public function __construct(
        private readonly IRepositorioRuta $repositorio,
    ) {
    }

    // ------------------------------------------------------------------
    // Validación de negocio de la llave
    // ------------------------------------------------------------------

    private function validarClave(int $id): int
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'El id de la ruta debe ser un entero mayor que cero.'
            );
        }
        return $id;
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

    public function obtener(int $id): Ruta
    {
        $id = $this->validarClave($id);
        $ruta = $this->repositorio->obtenerPorClave($id);
        if ($ruta === null) {
            throw new NoEncontradoExcepcion("No existe una ruta con id = $id");
        }
        return $ruta;
    }

    public function crear(array $datos): void
    {
        // El primer argumento es null: el id todavía no existe — lo pondrá
        // la base al insertar. Ver el modelo `Ruta`.
        $ruta = new Ruta(
            null,
            $datos['ruta'],
            $datos['descripcion'],
        );
        $this->repositorio->crear($ruta);
    }

    public function actualizar(int $id, array $datos): int
    {
        $id = $this->validarClave($id);
        if ($datos === []) {
            throw new InvalidArgumentException('No se envió ningún campo para actualizar.');
        }
        $filasAfectadas = $this->repositorio->actualizar($id, $datos);
        if ($filasAfectadas === 0) {
            throw new NoEncontradoExcepcion("No existe una ruta con id = $id");
        }
        return $filasAfectadas;
    }

    public function eliminar(int $id): int
    {
        $id = $this->validarClave($id);
        $filasEliminadas = $this->repositorio->eliminar($id);
        if ($filasEliminadas === 0) {
            throw new NoEncontradoExcepcion("No existe una ruta con id = $id");
        }
        return $filasEliminadas;
    }
}
