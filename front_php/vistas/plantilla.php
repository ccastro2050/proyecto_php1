<?php
/**
 * plantilla.php — el MARCO de todas las pantallas.
 *
 * La cabecera, el menú, los avisos y el pie. En el medio se carga la vista
 * que el enrutador eligió, que llega en $contenido.
 *
 * NO HAY AUTENTICACIÓN AQUÍ, y no es un olvido: el ingreso con usuario es de
 * otra versión, y anticiparlo contradice el Artículo 1 de la constitución.
 */
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($titulo ?? 'Facturas') ?></title>
  <link rel="stylesheet" href="/publico/estilos.css">
</head>
<body>

  <?php /* ================================================================
       LO QUE ESTA PANTALLA NO DICE, Y POR QUÉ

       Aquí NO se nombra el stack —ni PHP, ni MariaDB— ni se explica la
       arquitectura. Quien usa esto administra un catálogo de productos, y
       esa información no le sirve para nada: es ruido entre él y su tarea.

       Todo eso está donde le corresponde, para quien SÍ lo necesita: el
       README, la constitución y 6_contracts.md.
       ================================================================ */ ?>

  <header>
    <div class="barra">
      <div class="contenedor barra-interna">
        <a class="marca" href="/">Facturas</a>
        <span class="dependencia">Empresa de Ejemplo</span>
      </div>
    </div>

    <?php /* EL MENÚ, CON UN ENLACE POR PANTALLA. Hoy hay uno porque la v1
             construye una tabla. Cuando lleguen más recursos habrá un enlace
             por cada uno, a direcciones que existen — no una dirección con el
             nombre de la tabla como parámetro. */ ?>
    <nav class="menu">
      <div class="contenedor menu-interno">
        <a class="enlace<?= ($_SERVER['REQUEST_URI'] === '/' ? ' activo' : '') ?>" href="/">Inicio</a>
        <a class="enlace<?= (str_starts_with($_SERVER['REQUEST_URI'], '/productos') ? ' activo' : '') ?>" href="/productos">Productos</a>
      </div>
    </nav>
  </header>

  <main class="contenedor">

    <?php /* Los avisos: los que dejó la pantalla anterior (tras redirigir) y
             los que produjo esta misma. Uno por línea: cuando la API devuelve
             tres errores de validación, se ven los tres. */ ?>
    <?php if (!empty($aviso)): ?>
      <?php foreach ($aviso['mensajes'] as $mensaje): ?>
        <p class="aviso <?= htmlspecialchars($aviso['tipo']) ?>"><?= htmlspecialchars($mensaje) ?></p>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php foreach (($errores ?? []) as $error): ?>
      <p class="aviso error"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <?php require $contenido; ?>

  </main>

  <footer class="contenedor">
    <p>Sistema de facturas &mdash; ejemplo de referencia del curso</p>
  </footer>

</body>
</html>
