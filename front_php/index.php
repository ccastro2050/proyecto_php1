<?php
/**
 * index.php — el FRONT CONTROLLER del front.
 *
 * Sí: el front también tiene uno, y es el mismo patrón que la API. Todas las
 * peticiones entran aquí, este archivo mira el método y la ruta, y decide qué
 * pantalla pintar. Nada de SQL, nada de negocio, nada de HTML: eso está en
 * `vistas/`.
 *
 * Las pantallas de la v1:
 *   GET  /                        → el inicio
 *   GET  /productos               → el listado
 *   GET  /productos/nuevo         → el formulario vacío
 *   POST /productos/nuevo         → guarda el nuevo
 *   GET  /productos/{cod}/editar  → el formulario con la ficha
 *   POST /productos/{cod}/editar  → guarda, completo o parcial según el botón
 *   POST /productos/{cod}/eliminar
 *
 * **Cada pantalla tiene su dirección propia**, no una con el nombre de la
 * tabla como parámetro: se puede guardar como marcador, mandar por correo y
 * poner en un menú (sección 6.1 de la metodología).
 */

declare(strict_types=1);

require_once __DIR__ . '/cliente_api.php';

// ----------------------------------------------------------------------
// 1. CAPTURAR la petición
// ----------------------------------------------------------------------
$metodo = $_SERVER['REQUEST_METHOD'];
$ruta   = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

// ----------------------------------------------------------------------
// 1.b LOS ARCHIVOS ESTÁTICOS (y una trampa que costó una pantalla fea)
// ----------------------------------------------------------------------
//
// El servidor embebido de PHP, cuando se le da un router —que es lo que
// hacemos con `php -S ... index.php`—, **lo ejecuta para TODAS las
// peticiones**. También para `/publico/estilos.css`. Y como este archivo no
// tiene una ruta que se llame así, la hoja de estilos caía en el 404 de
// abajo: el navegador recibía una página HTML donde esperaba CSS, y la
// pantalla salía sin un solo estilo.
//
// La solución es la que el propio PHP documenta: **devolver `false`** desde
// el router. Eso le dice «yo no me encargo de ésta, entrégala tal cual», y
// el servidor sirve el archivo del disco.
if (PHP_SAPI === 'cli-server') {
    $archivo = __DIR__ . $ruta;
    if ($ruta !== '/' && is_file($archivo)) {
        return false;
    }
}

// Los avisos que una pantalla le deja a la siguiente. Se guardan en la sesión
// porque después de guardar se REDIRIGE (ver más abajo), y una redirección
// pierde todo lo que hubiera en memoria.
session_start();

/** Deja un aviso para la pantalla siguiente y redirige. */
function redirigir_con(string $destino, string $tipo, $mensaje): void
{
    $_SESSION['aviso'] = ['tipo' => $tipo, 'mensajes' => (array) $mensaje];
    header("Location: $destino");
    exit;
}

/** Saca el aviso pendiente (y lo borra: se muestra una sola vez). */
function aviso_pendiente(): ?array
{
    $aviso = $_SESSION['aviso'] ?? null;
    unset($_SESSION['aviso']);
    return $aviso;
}

/**
 * Pinta una vista dentro del marco común.
 *
 * Las tres variables que el MARCO siempre necesita se ponen aquí con un valor
 * por defecto, para que ninguna vista tenga que acordarse de mandarlas:
 *
 *   · $aviso   — lo que dejó la pantalla anterior (o nada);
 *   · $errores — lo que respondió la API en esta petición (o nada);
 *   · $ruta    — la dirección actual, que el menú usa para marcar dónde
 *                está parado el usuario.
 *
 * La de $ruta hace falta por una razón que no se ve a simple vista: **esto es
 * una función**, así que la $ruta que se calculó arriba, en el cuerpo del
 * archivo, no entra aquí sola. Sin pasársela, el menú no marcaría nada.
 */
function pintar(string $vista, array $datos = []): void
{
    $datos += ['errores' => [], 'ruta' => $GLOBALS['ruta'] ?? '/'];
    $datos['aviso'] = aviso_pendiente();

    extract($datos);                      // $productos, $ficha, $aviso, $ruta…
    $contenido = __DIR__ . "/vistas/$vista.php";
    require __DIR__ . '/vistas/plantilla.php';
}

// ----------------------------------------------------------------------
// 2. ENRUTAR
// ----------------------------------------------------------------------

// ---- El inicio ----
if ($ruta === '/' && $metodo === 'GET') {
    pintar('inicio');
    exit;
}

// ---- El listado ----
if ($ruta === '/productos' && $metodo === 'GET') {
    $r = listar_productos();
    // Aun con error se pinta la pantalla: el usuario ve el aviso DENTRO de la
    // aplicación, no una página de error de PHP.
    pintar('productos_lista', [
        'productos' => $r['datos'],
        'errores'   => $r['errores'],
    ]);
    exit;
}

// ---- Agregar ----
if ($ruta === '/productos/nuevo') {
    if ($metodo === 'GET') {
        pintar('productos_formulario', ['ficha' => null, 'editando' => false, 'errores' => []]);
        exit;
    }

    $datos = [
        'codigo'        => trim($_POST['codigo'] ?? ''),
        'nombre'        => trim($_POST['nombre'] ?? ''),
        // Los formularios SOLO producen texto: el «12» que la persona escribió
        // llega como "12". El contrato pide un número, y un número entre
        // comillas no es un número. Esto ajusta la FORMA del dato —trabajo del
        // front— y no juzga su VALOR, que es trabajo de la API.
        'stock'         => a_numero($_POST['stock'] ?? '', 'entero'),
        'valorunitario' => a_numero($_POST['valorunitario'] ?? '', 'decimal'),
    ];

    $r = crear_producto($datos);
    if ($r['ok']) {
        redirigir_con('/productos', 'exito', "Se agregó el producto {$datos['codigo']}.");
    }

    // Se devuelve el formulario CON lo que la persona había escrito: perder lo
    // digitado por un error de validación es castigarla dos veces.
    pintar('productos_formulario', [
        'ficha' => $datos, 'editando' => false, 'errores' => $r['errores'],
    ]);
    exit;
}

// ---- Editar ----
if (preg_match('#^/productos/([^/]+)/editar$#', $ruta, $coincidencias)) {
    $codigo = urldecode($coincidencias[1]);

    if ($metodo === 'GET') {
        $r = obtener_producto($codigo);
        if (!$r['ok']) {
            redirigir_con('/productos', 'error', $r['errores']);
        }
        pintar('productos_formulario', ['ficha' => $r['datos'], 'editando' => true, 'errores' => []]);
        exit;
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $stock  = trim($_POST['stock'] ?? '');
    $valor  = trim($_POST['valorunitario'] ?? '');

    // ==================================================================
    // AQUÍ ESTÁ LA LECCIÓN DEL FORMULARIO
    //
    // Qué botón se oprimió decide qué se envía. La diferencia NO está en un
    // `if` de negocio: está en el CUERPO de la petición.
    // ==================================================================
    if (($_POST['verbo'] ?? '') === 'completa') {
        // Ficha completa: los tres viajan aunque estén vacíos, y por eso un
        // nombre en blanco se rechaza. Es la semántica de reemplazar.
        $r = reemplazar_producto($codigo, [
            'nombre'        => $nombre,
            'stock'         => a_numero($stock, 'entero'),
            'valorunitario' => a_numero($valor, 'decimal'),
        ]);
    } else {
        // Solo lo que cambió: viaja únicamente lo diligenciado. El mismo
        // formulario a medio llenar que el reemplazo rechaza, aquí funciona.
        $cuerpo = [];
        if ($nombre !== '') { $cuerpo['nombre']        = $nombre; }
        if ($stock  !== '') { $cuerpo['stock']         = a_numero($stock, 'entero'); }
        if ($valor  !== '') { $cuerpo['valorunitario'] = a_numero($valor, 'decimal'); }
        $r = actualizar_producto($codigo, $cuerpo);
    }

    if ($r['ok']) {
        redirigir_con('/productos', 'exito', "Se guardó el producto $codigo.");
    }

    pintar('productos_formulario', [
        'ficha'    => ['codigo' => $codigo, 'nombre' => $nombre,
                       'stock' => $stock, 'valorunitario' => $valor],
        'editando' => true,
        'errores'  => $r['errores'],
    ]);
    exit;
}

// ---- Eliminar ----
// Se exige POST a propósito: un enlace GET que borra lo puede disparar el
// navegador solo, al precargar la página.
if (preg_match('#^/productos/([^/]+)/eliminar$#', $ruta, $coincidencias) && $metodo === 'POST') {
    $codigo = urldecode($coincidencias[1]);
    $r = eliminar_producto($codigo);

    $r['ok']
        ? redirigir_con('/productos', 'exito', "Se eliminó el producto $codigo.")
        : redirigir_con('/productos', 'error', $r['errores']);
}

// ---- Cualquier otra cosa ----
http_response_code(404);
pintar('no_encontrada', ['ruta' => $ruta]);

/**
 * El texto convertido a número si lo es; si no, el texto tal cual.
 *
 * Parece una validación en el front, y hay que ser preciso porque no lo es.
 * Un formulario HTML **solo produce texto**. El contrato pide un número, y
 * mandarlo entre comillas haría que la API lo rechazara **incluso siendo
 * correcto**.
 *
 * Así que esto ajusta la FORMA del dato, que es trabajo del front, y no juzga
 * su VALOR, que es trabajo de la API: si alguien escribió «doce», eso viaja
 * como «doce» y la API dice que no sirve.
 */
function a_numero(string $texto, string $tipo)
{
    $texto = trim($texto);
    if ($texto === '') {
        return '';
    }
    if ($tipo === 'entero') {
        return ctype_digit(ltrim($texto, '-')) ? (int) $texto : $texto;
    }
    return is_numeric($texto) ? (float) $texto : $texto;
}
