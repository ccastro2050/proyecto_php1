<?php
/**
 * El listado de productos.
 *
 * Las columnas están escritas aquí, con sus nombres. Cuando la v2 traiga más
 * tablas, cada una tendrá su vista — no una genérica que recorra una
 * descripción, porque entonces esta pantalla no podría decir lo que solo vale
 * para productos.
 */
?>
<div class="encabezado">
  <div>
    <h1>Productos</h1>
    <p class="nota">
      El catálogo del que se surten las facturas. Eliminar un producto lo
      retira de la base para siempre, así que la pantalla pregunta antes.
    </p>
  </div>
  <a class="boton primario" href="/productos/nuevo">Agregar</a>
</div>

<?php if (!empty($productos)): ?>
  <div class="tabla-scroll">
    <table>
      <thead>
        <tr>
          <th>Código</th>
          <th>Nombre</th>
          <th>Stock</th>
          <th>Valor unitario</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($productos as $p): ?>
          <tr>
            <td><code><?= htmlspecialchars((string) $p['codigo']) ?></code></td>
            <td><?= htmlspecialchars((string) $p['nombre']) ?></td>
            <td><?= htmlspecialchars((string) $p['stock']) ?></td>
            <td><?= htmlspecialchars(number_format((float) $p['valorunitario'], 2, ',', '.')) ?></td>
            <td>
              <div class="acciones">
                <a class="boton" href="/productos/<?= rawurlencode((string) $p['codigo']) ?>/editar">Editar</a>

                <?php /* POST y no un enlace: un GET que borra lo puede
                         disparar el navegador solo al precargar la página. */ ?>
                <form method="post"
                      action="/productos/<?= rawurlencode((string) $p['codigo']) ?>/eliminar"
                      onsubmit="return confirm('¿Eliminar <?= htmlspecialchars((string) $p['codigo']) ?>?');">
                  <button class="boton peligro" type="submit">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <p class="nota"><?= count($productos) ?> ficha(s).</p>

<?php else: ?>
  <?php /* Vacío NO es un error, y la pantalla lo distingue: si la API está
           caída, arriba hay además un aviso rojo. */ ?>
  <p class="vacio">
    Todavía no hay productos. Use «Agregar» para crear el primero.
  </p>
<?php endif; ?>
