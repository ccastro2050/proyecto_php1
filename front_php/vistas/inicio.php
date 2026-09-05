<?php
/**
 * La pantalla de inicio: dice qué es esto y a dónde se puede entrar.
 *
 * Un menú vale más que una lista de direcciones en un README: aquí se hace
 * clic. Cuando la v2 traiga más tablas, cada una agrega su tarjeta.
 */
?>
<div class="p-4 p-md-5 mb-4 bg-white border rounded-3 shadow-sm">
  <h1 class="display-6 fw-semibold">Sistema de facturas</h1>
  <p class="fs-5 text-body-secondary mb-0">
    El catálogo de productos que alimenta las facturas.
  </p>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <h2 class="card-title h5">Productos</h2>
        <p class="card-text text-body-secondary">
          Cuatro datos por ficha: código, nombre, stock y valor unitario.
          Se pueden agregar, corregir y retirar.
        </p>
      </div>
      <div class="card-footer bg-transparent border-0 pb-3">
        <a class="btn btn-primary" href="/productos">Ver el catálogo</a>
      </div>
    </div>
  </div>

  <?php /* La segunda tarjeta NO es un enlace, y eso es deliberado: dice lo
           que esta versión NO hace. Un menú que promete pantallas que no
           existen es peor que un menú corto. */ ?>
  <div class="col-md-6">
    <div class="card h-100 border-dashed bg-body-tertiary">
      <div class="card-body">
        <h2 class="card-title h5 text-body-secondary">Las demás tablas</h2>
        <p class="card-text text-body-secondary">
          Esta versión construye <strong>una</strong> tabla completa, de punta
          a punta: su API y su pantalla. Personas, clientes y facturas llegan
          en las versiones siguientes, cada una con la suya.
        </p>
      </div>
    </div>
  </div>
</div>
