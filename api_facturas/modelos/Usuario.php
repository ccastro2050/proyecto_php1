<?php
/**
 * Usuario — el modelo de la tabla `usuario`: una fila vista como objeto.
 *
 * Quién puede entrar al sistema. En la v1 es una tabla con su CRUD y su
 * pantalla: se crean usuarios, se les cambia la clave y se borran. **Entrar**
 * con uno de ellos es trabajo de la v3.
 *
 * ======================================================================
 * LO QUE ESTE MODELO NO TIENE, Y ES LO MÁS IMPORTANTE DE ÉL
 * ======================================================================
 *
 * La tabla tiene dos columnas —`email` y `contrasena`— y **este objeto solo
 * guarda la primera**. No es un olvido:
 *
 *   · la `contrasena` de la base es un HASH, y un hash no se le muestra a
 *     nadie: no sirve para nada bueno y sí para que alguien lo intente romper
 *     con calma en otra parte;
 *   · si estuviera aquí, acabaría en `toArray()`, y de ahí en el JSON, en el
 *     registro del servidor y en la caché del navegador — **sin que nada
 *     falle**. La pantalla se vería perfecta. Ése es el problema.
 *
 * Así que la clave entra al sistema (al crear, al cambiarla) y nunca sale. El
 * repositorio la escribe cifrada y jamás la lee.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

class Usuario
{
    private string $email;   // La llave primaria: el correo con el que entra.

    public function __construct(
        string $email,
    ) {
        $this->email = $email;
    }

    // ------------------------------------------------------------------
    // GETTERS — para LEER cada propiedad desde afuera
    // ------------------------------------------------------------------

    public function getEmail(): string
    {
        return $this->email;
    }

    // ------------------------------------------------------------------
    // No hay SETTERS: el email es la llave primaria y no cambia. Y la
    // contraseña no es una propiedad de este objeto (ver la cabecera).
    // ------------------------------------------------------------------

    // ------------------------------------------------------------------
    // Conversión para la respuesta JSON
    // ------------------------------------------------------------------

    /** El objeto como array (columna => valor), listo para json_encode. */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
        ];
    }
}
