<?php
/**
 * Ruta — el modelo de la tabla `ruta`: una fila vista como objeto.
 *
 * El inventario de rutas del sistema: `/api/producto`, `/api/factura`… con su
 * descripción. En la v1 es una tabla como cualquier otra, con su CRUD y su
 * pantalla. Para QUÉ sirve —decidir qué rol puede entrar a cada ruta— es
 * trabajo de la v3.
 *
 * **Dos avisos para no perder tiempo leyendo el SQL:**
 *   - la tabla se llama `ruta` y una de sus columnas TAMBIÉN se llama `ruta`.
 *     Así está la base; por eso en el SQL se ve `ruta.ruta` y en este archivo
 *     conviene no confundir el dato con la ruta HTTP que lo sirve;
 *   - `id` es `?int` porque **LA LLAVE LA GENERA LA BASE** (`AUTO_INCREMENT`),
 *     igual que en `Rol`: una ruta recién armada en memoria todavía no tiene
 *     id, y decirlo en el tipo evita el `0` que no significa nada.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

class Ruta
{
    private ?int $id;
    private string $ruta;          // La ruta misma: '/api/producto'…
    private string $descripcion;   // Para qué sirve, en castellano.

    public function __construct(
        ?int $id,
        string $ruta,
        string $descripcion,
    ) {
        $this->id = $id;
        $this->ruta = $ruta;
        $this->descripcion = $descripcion;
    }

    // ------------------------------------------------------------------
    // GETTERS — para LEER cada propiedad desde afuera
    // ------------------------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRuta(): string
    {
        return $this->ruta;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    // ------------------------------------------------------------------
    // SETTERS — solo para lo que puede cambiar (la llave nunca)
    // ------------------------------------------------------------------

    public function setRuta(string $ruta): void
    {
        $this->ruta = $ruta;
    }

    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    // ------------------------------------------------------------------
    // Conversión para la respuesta JSON
    // ------------------------------------------------------------------

    /** El objeto como array (columna => valor), listo para json_encode. */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'ruta'        => $this->ruta,
            'descripcion' => $this->descripcion,
        ];
    }
}
