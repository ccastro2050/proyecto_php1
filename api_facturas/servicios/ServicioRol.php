<?php
/**
 * ServicioRol — la capa de NEGOCIO de `rol`.
 *
 * Recibe la INTERFAZ del repositorio por constructor: no sabe si detrás hay
 * MariaDB o un falso en memoria. No conoce HTTP — comunica los problemas con
 * excepciones que el controlador traduce.
 *
 * La validación de la llave aquí es distinta a la de `producto`: un id que
 * llega como 0 o negativo no es un id de MariaDB, y rechazarlo de entrada
 * ahorra una consulta que se sabe que no va a encontrar nada.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

require_once __DIR__ . '/IServicioRol.php';
require_once __DIR__ . '/../repositorios/IRepositorioRol.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../modelos/Rol.php';

class ServicioRol implements IServicioRol
{
    public function __construct(
        private readonly IRepositorioRol $repositorio,
    ) {
    }

    // ------------------------------------------------------------------
    // Validación de negocio de la llave
    // ------------------------------------------------------------------

    private function validarClave(int $id): int
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'El id del rol debe ser un entero mayor que cero.'
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

    public function obtener(int $id): Rol
    {
        $id = $this->validarClave($id);
        $rol = $this->repositorio->obtenerPorClave($id);
        if ($rol === null) {
            throw new NoEncontradoExcepcion("No existe el rol con id = $id");
        }
        return $rol;
    }

    public function crear(array $datos): void
    {
        // El primer argumento es null: el id todavía no existe — lo pondrá
        // la base al insertar. Ver el modelo `Rol`.
        $rol = new Rol(
            null,
            $datos['nombre'],
        );
        $this->repositorio->crear($rol);
    }

    public function actualizar(int $id, array $datos): int
    {
        $id = $this->validarClave($id);
        if ($datos === []) {
            throw new InvalidArgumentException('No se envió ningún campo para actualizar.');
        }
        $filasAfectadas = $this->repositorio->actualizar($id, $datos);
        if ($filasAfectadas === 0) {
            throw new NoEncontradoExcepcion("No existe el rol con id = $id");
        }
        return $filasAfectadas;
    }

    public function eliminar(int $id): int
    {
        $id = $this->validarClave($id);
        $filasEliminadas = $this->repositorio->eliminar($id);
        if ($filasEliminadas === 0) {
            throw new NoEncontradoExcepcion("No existe el rol con id = $id");
        }
        return $filasEliminadas;
    }
}
