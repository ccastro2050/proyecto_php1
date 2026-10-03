<?php
/**
 * Ensamblador — el ÚNICO lugar del sistema que conoce clases concretas.
 *
 * Seis funciones, una por recurso de la v1, y todas hacen lo mismo: armar la
 * cadena `servicio → repositorio` con la configuración que llega del entorno.
 *
 * ======================================================================
 * ¿SEIS FUNCIONES CASI IGUALES? SÍ, Y ES A PROPÓSITO
 * ======================================================================
 *
 * La tentación es evidente: una sola función `crearServicio(string $recurso)`
 * con un `switch` adentro, o peor, algo que arme el nombre de la clase con
 * texto (`"Repositorio{$recurso}MariaDB"`).
 *
 * Se descartó, y por la misma razón por la que la API es específica y no
 * genérica (Artículo 10 de la constitución):
 *
 *   · con seis funciones, PHP verifica los tipos: si `ServicioRol` no acepta
 *     un `IRepositorioRol`, el error sale al llamarla;
 *   · con nombres armados en texto, el error sale **en producción**, cuando
 *     alguien pida el recurso que nadie probó;
 *   · y quien lea este archivo ve el inventario completo del sistema de un
 *     vistazo, sin ejecutar nada.
 *
 * Seis funciones cortas y aburridas se leen mejor que una lista y un `switch`.
 *
 * ======================================================================
 * UN MOTOR, Y EL CÓDIGO LO DICE
 * ======================================================================
 *
 * No hay arreglo de motores ni selección: de la v1 a la v4 este sistema corre
 * sobre **MariaDB**, y nada aquí finge lo contrario (YAGNI con dirección).
 * Cuando la **v5** agregue los otros motores, **es este archivo —y solo éste—
 * el que se convierte en una fábrica de verdad**: controladores y servicios no
 * se tocarán, y ése será el examen del principio abierto/cerrado.
 */

// Modo estricto de tipos (ver explicación completa en index.php):
declare(strict_types=1);

require_once __DIR__ . '/ServicioProducto.php';
require_once __DIR__ . '/ServicioEmpresa.php';
require_once __DIR__ . '/ServicioPersona.php';
require_once __DIR__ . '/ServicioRol.php';
require_once __DIR__ . '/ServicioRuta.php';
require_once __DIR__ . '/ServicioUsuario.php';
require_once __DIR__ . '/../repositorios/RepositorioProductoMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioEmpresaMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioPersonaMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioRolMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioRutaMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioUsuarioMariaDB.php';

// ----------------------------------------------------------------------
// La configuración, leída UNA vez
// ----------------------------------------------------------------------

/**
 * Los tres datos de la conexión, iguales para los seis repositorios.
 *
 *   getenv('X')  → lee la variable X del entorno (el compose las inyecta)
 *   ?:           → "si vino vacía o no existe, usa este valor por defecto"
 *
 * Los defaults apuntan a localhost:13326 = el puerto PUBLICADO de la BD, para
 * poder correr la API sin Docker mientras la BD sí está en Docker.
 *
 * @return array{0: string, 1: string, 2: string} dsn, usuario, clave
 */
function configuracionDeLaBase(): array
{
    return [
        getenv('DB_DSN')     ?: 'mysql:host=localhost;port=13326;dbname=bdfacturas_mariadb_local',
        getenv('DB_USUARIO') ?: 'paradigmas',
        getenv('DB_CLAVE')   ?: 'paradigmas123',
    ];
}

// ----------------------------------------------------------------------
// Una función por recurso
// ----------------------------------------------------------------------

// Fíjese en el tipo de retorno de cada una: promete LA INTERFAZ, no la clase
// concreta. Quien las llame (index.php) no sabrá qué hay dentro.

function crearServicioProducto(): IServicioProducto
{
    // Aquí — y SOLO aquí — se hace `new` de clases concretas.
    [$dsn, $usuario, $clave] = configuracionDeLaBase();
    // Se ARMA la cadena de capas: el servicio recibe el repositorio ya
    // construido (inyección de dependencias hecha a mano, sin frameworks):
    return new ServicioProducto(new RepositorioProductoMariaDB($dsn, $usuario, $clave));
}

function crearServicioEmpresa(): IServicioEmpresa
{
    [$dsn, $usuario, $clave] = configuracionDeLaBase();
    return new ServicioEmpresa(new RepositorioEmpresaMariaDB($dsn, $usuario, $clave));
}

function crearServicioPersona(): IServicioPersona
{
    [$dsn, $usuario, $clave] = configuracionDeLaBase();
    return new ServicioPersona(new RepositorioPersonaMariaDB($dsn, $usuario, $clave));
}

function crearServicioRol(): IServicioRol
{
    [$dsn, $usuario, $clave] = configuracionDeLaBase();
    return new ServicioRol(new RepositorioRolMariaDB($dsn, $usuario, $clave));
}

function crearServicioRuta(): IServicioRuta
{
    [$dsn, $usuario, $clave] = configuracionDeLaBase();
    return new ServicioRuta(new RepositorioRutaMariaDB($dsn, $usuario, $clave));
}

function crearServicioUsuario(): IServicioUsuario
{
    [$dsn, $usuario, $clave] = configuracionDeLaBase();
    return new ServicioUsuario(new RepositorioUsuarioMariaDB($dsn, $usuario, $clave));
}
