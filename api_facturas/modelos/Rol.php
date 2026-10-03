<?php
/**
 * Rol — el modelo de la tabla `rol`: una fila vista como objeto.
 *
 * Los papeles que puede tener un usuario: administrador, vendedor, consulta…
 * En la v1 es una tabla como cualquier otra, con su CRUD y su pantalla. Lo que
 * el rol SIGNIFICA —qué deja hacer y qué no— es trabajo de la v3.
 *
 * Mismo estilo clásico de P.O.O. que el `Producto`, con una diferencia que se
 * ve en el constructor:
 *   - `id` es `?int` (puede ser null) porque **LA LLAVE LA GENERA LA BASE**.
 *     Un rol recién armado en memoria, antes del INSERT, todavía no tiene id;
 *     el que sale de un SELECT sí. Decirlo en el tipo evita el `0` que no
 *     significa nada.
 *   - y por eso `id` no tiene setter: lo pone la base, no el programa.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

class Rol
{
    private ?int $id;
    private string $nombre;   // El nombre del papel: 'administrador', 'vendedor'…

    public function __construct(
        ?int $id,
        string $nombre,
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
    }

    // ------------------------------------------------------------------
    // GETTERS — para LEER cada propiedad desde afuera
    // ------------------------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    // ------------------------------------------------------------------
    // SETTERS — solo para lo que puede cambiar (la llave nunca)
    // ------------------------------------------------------------------

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    // ------------------------------------------------------------------
    // Conversión para la respuesta JSON
    // ------------------------------------------------------------------

    /** El objeto como array (columna => valor), listo para json_encode. */
    public function toArray(): array
    {
        return [
            'id'     => $this->id,
            'nombre' => $this->nombre,
        ];
    }
}
