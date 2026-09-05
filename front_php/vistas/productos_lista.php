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
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <h1 class="h3 mb-1">Productos</h1>
    <p class="text-body-secondary mb-0">
      El catálogo del que se surten las facturas. Eliminar un producto lo
      retira de la base para siempre, así que la pantalla pregunta antes.
    </p>
  </div>
  <a class="btn btn-primary" href="/productos/nuevo">Agregar producto</a>
</div>

<?php if (!empty($productos)): ?>
  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col">Código</th>
            <th scope="col">Nombre</th>
            <th scope="col" class="text-end">Stock</th>
            <th scope="col" class="text-end">Valor unitario</th>
            <th scope="col" class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($productos as $p): ?>
            <tr>
              <td>
                <span class="badge text-bg-secondary font-monospace">
                  <?= htmlspecialchars((string) $p['codigo']) ?>
                </span>
              </td>
              <td><?= htmlspecialchars((string) $p['nombre']) ?></td>
              <td class="text-end"><?= htmlspecialchars((string) $p['stock']) ?></td>
              <td class="text-end">
                $ <?= htmlspecialchars(number_format((float) $p['valorunitario'], 2, ',', '.')) ?>
              </td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-secondary"
                   href="/productos/<?= rawurlencode((string) $p['codigo']) ?>/editar">Editar</a>

                <?php /* POST y no un enlace: un GET que borra lo puede
                         disparar el navegador solo al precargar la página. */ ?>
                <form class="d-inline" method="post"
                      action="/productos/<?= rawurlencode((string) $p['codigo']) ?>/eliminar"
                      onsubmit="return confirm('¿Eliminar <?= htmlspecialchars((string) $p['codigo']) ?>?');">
                  <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <p class="text-body-secondary small mt-3 mb-0">
    <?= count($productos) ?> ficha(s).
  </p>

<?php else: ?>
  <?php /* Vacío NO es un error, y la pantalla lo distingue: si la API está
           caída, arriba hay además un aviso rojo. */ ?>
  <div class="card shadow-sm">
    <div class="card-body text-center py-5">
      <p class="fs-5 mb-1">Todavía no hay productos</p>
      <p class="text-body-secondary">Use «Agregar producto» para crear el primero.</p>
      <a class="btn btn-primary" href="/productos/nuevo">Agregar producto</a>
    </div>
  </div>
<?php endif; ?>
