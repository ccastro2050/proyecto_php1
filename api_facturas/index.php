<?php
/**
 * index.php — el FRONT CONTROLLER de la API Facturas v1.
 *
 * TODAS las peticiones entran por aquí (el servidor se arranca con
 * `php -S 0.0.0.0:8022 index.php`). Este archivo hace UNA cosa: mirar el
 * método (GET, POST…) y la ruta, y entregar la petición al método del
 * controlador que corresponde. Nada de SQL, nada de negocio.
 *
 * El recorrido completo de una petición, paso a paso, está explicado en
 * docs/FLUJO_DE_UNA_PETICION.md.
 *
 * ======================================================================
 * LOS SEIS RECURSOS DE LA v1: LAS TABLAS SIN CLAVE FORÁNEA
 * ======================================================================
 *
 * `producto`, `empresa`, `persona`, `rol`, `ruta` y `usuario`. Son las tablas
 * de las que las demás van a depender, y por eso van primero: una tabla con
 * clave foránea no se puede llenar si la tabla a la que apunta está vacía.
 *
 * Y se dividen en dos grupos, que es la diferencia que más se nota al leer
 * este archivo:
 *
 *   · los de llave ESCRITA por el cliente — `producto`, `empresa`, `persona`
 *     y `usuario` —: la llave llega en la URL como texto;
 *   · los de llave GENERADA por la base — `rol` y `ruta` —: la llave es un
 *     número `AUTO_INCREMENT`, y aquí se comprueba que de verdad lo sea antes
 *     de entregarla al controlador.
 *
 * Rutas de la v1 (contratos exactos en docs 6_contracts.md):
 *   GET    /                          → diagnóstico
 *
 *   GET    /api/<recurso>[?limite=N]  → listar     (204 si está vacía)
 *   POST   /api/<recurso>             → crear
 *   GET    /api/<recurso>/{llave}     → obtener uno
 *   PUT    /api/<recurso>/{llave}     → reemplazo completo
 *   PATCH  /api/<recurso>/{llave}     → actualización parcial
 *   DELETE /api/<recurso>/{llave}     → eliminar
 *
 *   …con <recurso> en: producto · empresa · persona · rol · ruta · usuario
 */

// "Modo estricto de tipos": si una función espera int y llega el string "5",
// PHP lanza error en vez de convertirlo en silencio. DEBE ser la primera
// instrucción del archivo. Todos los archivos del proyecto lo llevan.
declare(strict_types=1);

// require_once = "carga este archivo aquí (una sola vez)". __DIR__ es la
// carpeta donde vive ESTE archivo. Esta lista es el inventario del proyecto:
require_once __DIR__ . '/servicios/ensamblador.php';
require_once __DIR__ . '/controladores/ControladorProducto.php';
require_once __DIR__ . '/controladores/ControladorEmpresa.php';
require_once __DIR__ . '/controladores/ControladorPersona.php';
require_once __DIR__ . '/controladores/ControladorRol.php';
require_once __DIR__ . '/controladores/ControladorRuta.php';
require_once __DIR__ . '/controladores/ControladorUsuario.php';

// Toda respuesta de esta API es JSON — se avisa en el encabezado HTTP:
header('Content-Type: application/json; charset=utf-8');

// ----------------------------------------------------------------------
// 1. CAPTURAR la petición: método, ruta y body
// ----------------------------------------------------------------------

// $_SERVER es un array que PHP llena solo en cada petición.
// REQUEST_METHOD trae el verbo que mandó el cliente: "GET", "POST", "PUT"…
$metodo = $_SERVER['REQUEST_METHOD'];

// REQUEST_URI trae todo lo pedido, ej. "/api/producto?limite=5".
// parse_url(..., PHP_URL_PATH) recorta y deja SOLO la ruta: "/api/producto".
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// El body (el JSON que traen POST, PUT y PATCH) se lee del canal especial
// php://input — solo se puede leer UNA vez, por eso se guarda aquí:
//   file_get_contents  → lee el texto crudo del body
//   json_decode(..., true) → lo convierte a array de PHP
//   ?? []              → si no había body (o el JSON está malo), queda []
$body = json_decode(file_get_contents('php://input'), true) ?? [];

// ----------------------------------------------------------------------
// 2. ENRUTAR: comparar método + ruta y llamar al método del controlador
// ----------------------------------------------------------------------

// GET / — diagnóstico (sirve para saber si la API está viva)
if ($ruta === '/' && $metodo === 'GET') {
    // json_encode convierte el array a texto JSON; echo lo escribe en la
    // respuesta. JSON_UNESCAPED_UNICODE deja las tildes legibles.
    echo json_encode([
        'mensaje'   => 'API Facturas funcionando',
        'version'   => 'v1',
        'motor'     => 'mariadb',
        'recursos'  => ['/api/producto', '/api/empresa', '/api/persona',
                        '/api/rol', '/api/ruta', '/api/usuario'],
        'contratos' => 'docs/spec_kit/versiones/v1_sin_fk/6_contracts.md',
    ], JSON_UNESCAPED_UNICODE);
    return;   // terminamos: no siga evaluando rutas
}

// ----------------------------------------------------------------------
// Los recursos de LLAVE ESCRITA por el cliente (la llave es texto)
// ----------------------------------------------------------------------

// Cada entrada dice: el nombre del recurso → el controlador ya armado.
// Las funciones `crearServicio*` las trae el ensamblador, el único archivo
// que conoce clases concretas.
$deLlaveDeTexto = [
    'producto' => fn() => new ControladorProducto(crearServicioProducto()),
    'empresa'  => fn() => new ControladorEmpresa(crearServicioEmpresa()),
    'persona'  => fn() => new ControladorPersona(crearServicioPersona()),
    'usuario'  => fn() => new ControladorUsuario(crearServicioUsuario()),
];

foreach ($deLlaveDeTexto as $recurso => $armar) {
    // /api/<recurso> — la COLECCIÓN (sin llave en la URL): listar y crear
    if ($ruta === "/api/$recurso") {
        $controlador = $armar();
        if ($metodo === 'GET') {
            $controlador->listar();
        } elseif ($metodo === 'POST') {
            $controlador->crear($body);
        } else {
            responderNoPermitido();   // PUT, DELETE… aquí no existen → 405
        }
        return;
    }

    // /api/<recurso>/{llave} — UNA ficha concreta.
    // str_starts_with pregunta si la ruta EMPIEZA por "/api/<recurso>/";
    // substr corta lo que sigue después de ese prefijo: eso es la llave.
    // Ej.: "/api/producto/PR001" → $llave = "PR001".
    if (str_starts_with($ruta, "/api/$recurso/")) {
        $llave = substr($ruta, strlen("/api/$recurso/"));
        // urldecode revierte la codificación de URL (un "%20" vuelve a ser
        // espacio, y un "%40" vuelve a ser la arroba de un email):
        $llave = urldecode($llave);

        $controlador = $armar();
        if ($metodo === 'GET') {
            $controlador->obtener($llave);
        } elseif ($metodo === 'PUT') {
            $controlador->reemplazar($llave, $body);
        } elseif ($metodo === 'PATCH') {
            $controlador->actualizar($llave, $body);
        } elseif ($metodo === 'DELETE') {
            $controlador->eliminar($llave);
        } else {
            responderNoPermitido();
        }
        return;
    }
}

// ----------------------------------------------------------------------
// Los recursos de LLAVE GENERADA por la base (la llave es un número)
// ----------------------------------------------------------------------

$deLlaveNumerica = [
    'rol'  => fn() => new ControladorRol(crearServicioRol()),
    'ruta' => fn() => new ControladorRuta(crearServicioRuta()),
];

foreach ($deLlaveNumerica as $recurso => $armar) {
    if ($ruta === "/api/$recurso") {
        $controlador = $armar();
        if ($metodo === 'GET') {
            $controlador->listar();
        } elseif ($metodo === 'POST') {
            // Ojo: el POST de estos recursos NO manda la llave — la pone la
            // base. Ver la cabecera de `ControladorRol`.
            $controlador->crear($body);
        } else {
            responderNoPermitido();
        }
        return;
    }

    if (str_starts_with($ruta, "/api/$recurso/")) {
        $texto = urldecode(substr($ruta, strlen("/api/$recurso/")));

        // AQUÍ ESTÁ LA DIFERENCIA con los recursos de arriba: la llave tiene
        // que ser un número, y se comprueba ANTES de llamar al controlador.
        //
        // ctype_digit('12') es true; ctype_digit('abc') y ctype_digit('1.5')
        // son false. Si no es un número, la ruta simplemente NO EXISTE — por
        // eso sale un 404 de ruta y no un 422 de dato: "/api/rol/abc" no es
        // una petición mal escrita sobre un rol, es una dirección que esta
        // API no sirve.
        if (!ctype_digit($texto)) {
            responderRutaNoEncontrada($metodo, $ruta);
            return;
        }
        $id = (int) $texto;

        $controlador = $armar();
        if ($metodo === 'GET') {
            $controlador->obtener($id);
        } elseif ($metodo === 'PUT') {
            $controlador->reemplazar($id, $body);
        } elseif ($metodo === 'PATCH') {
            $controlador->actualizar($id, $body);
        } elseif ($metodo === 'DELETE') {
            $controlador->eliminar($id);
        } else {
            responderNoPermitido();
        }
        return;
    }
}

// Ninguna ruta coincidió: 404 de RUTA
// (distinto del 404 de "el producto no existe", que decide el servicio).
responderRutaNoEncontrada($metodo, $ruta);

// ----------------------------------------------------------------------
// Funciones de apoyo del enrutador. ": void" declara que no devuelven nada.

function responderNoPermitido(): void
{
    // 405 = "la ruta existe, pero no con ese método"
    http_response_code(405);
    echo json_encode([
        'estado' => 405, 'mensaje' => 'Método no permitido para esta ruta.',
    ], JSON_UNESCAPED_UNICODE);
}

function responderRutaNoEncontrada(string $metodo, string $ruta): void
{
    http_response_code(404);
    echo json_encode([
        'estado' => 404, 'mensaje' => 'Ruta no encontrada.', 'detalle' => "$metodo $ruta",
    ], JSON_UNESCAPED_UNICODE);
}
