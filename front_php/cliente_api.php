<?php
/**
 * cliente_api.php — la capa de DATOS del front.
 *
 * Es al front lo que el repositorio es a la API: la ÚNICA pieza que sabe
 * dónde viven los datos —en la API, nunca en la base— y la única que habla
 * HTTP.
 *
 * ======================================================================
 * LO QUE ESTE ARCHIVO NO HACE, Y ES LO MÁS IMPORTANTE
 * ======================================================================
 *
 * No abre ninguna conexión a MariaDB. No hay `new PDO(...)` en todo el front,
 * y no lo va a haber: es el Artículo de la constitución que dice que el front
 * no toca la base.
 *
 * Y hay una tentación que en este proyecto es MAYOR que en otros, porque el
 * front y la API están **los dos en PHP**: bastaría un
 *
 *     require_once __DIR__ . '/../api_facturas/modelos/Producto.php';
 *
 * para usar aquí la clase `Producto` de la API. Funcionaría. Y estaría mal:
 * los dos dejarían de ser procesos independientes, y renombrar un método
 * dentro de la API rompería el front **sin que nadie tocara el contrato**.
 * Lo único que comparten es el JSON.
 *
 * Por eso este archivo trabaja con **arrays**, no con objetos de la API: lo
 * que llega es lo que el JSON traía, ni más ni menos.
 *
 * ======================================================================
 * QUÉ DEVUELVE CADA FUNCIÓN
 * ======================================================================
 *
 * Siempre un array con la misma forma, para que las vistas no tengan que
 * saber qué es un 422:
 *
 *     ['ok' => bool, 'datos' => mixed, 'errores' => string[]]
 *
 * Una vista pregunta «¿salió bien?», no «¿fue 200 o 204?».
 */

declare(strict_types=1);

// La dirección de la API. Dentro de Docker la manda el compose con el NOMBRE
// del servicio; fuera vale el valor de la derecha. Nunca 'localhost' dentro de
// un contenedor: ahí localhost es el contenedor mismo.
define('URL_API', getenv('URL_API') ?: 'http://localhost:8022');

const TIEMPO_MAXIMO = 10;   // segundos

/**
 * Hace la petición HTTP y unifica un solo caso: que la API no responda.
 *
 * Devuelve null cuando NO hubo respuesta —API caída, tiempo agotado—, que es
 * distinto de «respondió con un error». Un 404 es la API funcionando y
 * diciendo que ese producto no existe; un null es que no hay con quién hablar.
 *
 * @return array{codigo:int, cuerpo:array}|null
 */
function llamar_api(string $metodo, string $ruta, ?array $cuerpo = null): ?array
{
    $curl = curl_init(URL_API . $ruta);

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,          // devolver la respuesta, no imprimirla
        CURLOPT_CUSTOMREQUEST  => $metodo,       // GET, POST, PUT, PATCH, DELETE
        CURLOPT_TIMEOUT        => TIEMPO_MAXIMO,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);

    if ($cuerpo !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($cuerpo));
    }

    $respuesta = curl_exec($curl);
    $codigo    = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $fallo     = curl_errno($curl);
    curl_close($curl);

    if ($fallo !== 0) {
        return null;                              // no hubo con quién hablar
    }

    // El 204 no trae cuerpo, y eso NO es un error: es «no hay filas».
    return ['codigo' => $codigo,
            'cuerpo' => json_decode((string) $respuesta, true) ?? []];
}

/**
 * Traduce a texto los errores que produce ESTA API.
 *
 * El sobre es plano y tiene dos formas:
 *   ['estado' => …, 'mensaje' => …, 'detalle' => …]     → 400, 404, 500
 *   ['estado' => …, 'mensaje' => …, 'errores' => [...]] → cuando el cuerpo no cumple
 *
 * **Esta función es el único sitio del front que conoce ese formato.** Si
 * mañana la API cambia el sobre, se cambia aquí y en ninguna vista.
 *
 * @return string[]
 */
function mensajes_de_error(array $cuerpo): array
{
    if (isset($cuerpo['errores']) && is_array($cuerpo['errores']) && $cuerpo['errores'] !== []) {
        return array_values(array_map('strval', $cuerpo['errores']));
    }

    $partes = array_filter([
        $cuerpo['mensaje'] ?? '',
        $cuerpo['detalle'] ?? '',
    ], static fn ($t) => trim((string) $t) !== '');

    return $partes === []
        ? ['No se pudo completar la operación.']
        : array_values(array_map('strval', $partes));
}

const NO_DISPONIBLE = ['El servicio no está disponible. ¿Está arriba la API?'];

// ======================================================================
// LAS SEIS OPERACIONES SOBRE `producto`
//
// Una función por operación, con el nombre del recurso adentro. Cuando la v2
// traiga más tablas habrá más funciones —`listar_facturas`, `crear_cliente`—,
// no una `listar($tabla)` genérica: es la sección 6.1 de la metodología del
// curso, aplicada del lado del front.
// ======================================================================

/** RF1 — Listar. El 204 es «no hay ninguno», y no es un error. */
function listar_productos(int $limite = 1000): array
{
    $r = llamar_api('GET', "/api/producto?limite=$limite");

    if ($r === null) {
        return ['ok' => false, 'datos' => [], 'errores' => NO_DISPONIBLE];
    }
    if ($r['codigo'] === 204) {
        return ['ok' => true, 'datos' => [], 'errores' => []];
    }
    if ($r['codigo'] === 200) {
        return ['ok' => true, 'datos' => $r['cuerpo']['datos'] ?? [], 'errores' => []];
    }
    return ['ok' => false, 'datos' => [], 'errores' => mensajes_de_error($r['cuerpo'])];
}

/** RF2 — Obtener uno por su código. */
function obtener_producto(string $codigo): array
{
    $r = llamar_api('GET', '/api/producto/' . rawurlencode($codigo));

    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => NO_DISPONIBLE];
    }
    if ($r['codigo'] === 200) {
        return ['ok' => true, 'datos' => $r['cuerpo'], 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r['cuerpo'])];
}

/** RF3 — Crear. */
function crear_producto(array $datos): array
{
    return resultado_de(llamar_api('POST', '/api/producto', $datos));
}

/**
 * RF4 — Reemplazar: «guardar la ficha completa».
 * El código NO va en el cuerpo: identifica la fila y viaja en la ruta.
 */
function reemplazar_producto(string $codigo, array $datos): array
{
    return resultado_de(llamar_api('PUT', '/api/producto/' . rawurlencode($codigo), $datos));
}

/**
 * RF5 — Actualizar: «guardar solo lo que cambié».
 * Solo viaja lo diligenciado. Un campo en blanco NO se envía —no es que se
 * envíe vacío: sencillamente no va— y la API lo deja como estaba.
 */
function actualizar_producto(string $codigo, array $datos): array
{
    return resultado_de(llamar_api('PATCH', '/api/producto/' . rawurlencode($codigo), $datos));
}

/**
 * RF6 — Eliminar. En esta versión el borrado es **físico**: la fila se va de
 * la base y no vuelve. Por eso la pantalla pide confirmación antes.
 */
function eliminar_producto(string $codigo): array
{
    return resultado_de(llamar_api('DELETE', '/api/producto/' . rawurlencode($codigo)));
}

/** Lo común a las cuatro operaciones que escriben. */
function resultado_de(?array $r): array
{
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => NO_DISPONIBLE];
    }
    return $r['codigo'] === 200
        ? ['ok' => true, 'datos' => $r['cuerpo'], 'errores' => []]
        : ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r['cuerpo'])];
}
