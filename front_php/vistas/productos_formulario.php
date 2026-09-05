<?php
/**
 * El formulario de un producto: sirve para agregar y para editar.
 *
 * La diferencia entre los dos usos está en $editando, y se ve en dos sitios:
 * el código va de solo lectura al editar, y aparecen DOS botones de guardar
 * en vez de uno.
 */
?>
<div class="encabezado">
  <div>
    <h1><?= $editando ? 'Editar el producto' : 'Agregar un producto' ?></h1>
    <p class="nota">
      <?php if ($editando): ?>
        El código identifica la ficha y no se cambia. Si está mal, se agrega
        otra y se elimina esta.
      <?php else: ?>
        El código lo escribe usted y no se podrá cambiar después.
      <?php endif; ?>
    </p>
  </div>
  <a class="boton" href="/productos">Volver</a>
</div>

<form method="post">

  <?php /* EL CÓDIGO. Al editar va de solo lectura: es la identidad de la
           ficha, lo que la base llama llave primaria. Un campo que el usuario
           puede escribir pero el sistema no puede cambiar es una promesa
           falsa. */ ?>
  <label>
    Código
    <input type="text" name="codigo" maxlength="10"
           value="<?= htmlspecialchars((string) ($ficha['codigo'] ?? '')) ?>"
           <?= $editando ? 'readonly' : 'required' ?>>
    <?php if (!$editando): ?>
      <span class="ayuda">Hasta diez caracteres, como <code>PR001</code>.</span>
    <?php endif; ?>
  </label>

  <label>
    Nombre
    <input type="text" name="nombre" maxlength="100"
           value="<?= htmlspecialchars((string) ($ficha['nombre'] ?? '')) ?>">
  </label>

  <label>
    Stock
    <input type="number" name="stock" min="0"
           value="<?= htmlspecialchars((string) ($ficha['stock'] ?? '')) ?>">
    <span class="ayuda">Unidades disponibles: un número entero.</span>
  </label>

  <label>
    Valor unitario
    <input type="number" name="valorunitario" step="0.01" min="0"
           value="<?= htmlspecialchars((string) ($ficha['valorunitario'] ?? '')) ?>">
  </label>

  <?php /* ==============================================================
       LOS DOS BOTONES, QUE NO HACEN LO MISMO

         · «Guardar la ficha completa» manda todo, así que un dato
           obligatorio en blanco se rechaza.
         · «Guardar solo lo que cambié» manda únicamente lo diligenciado,
           así que el mismo formulario a medio llenar sí se guarda.

       El mismo formulario, dos comportamientos, y la diferencia no la
       decide ningún `if` de negocio: la decide QUÉ SE ENVÍA.
       ============================================================== */ ?>
  <?php if ($editando): ?>
    <div class="pareja">
      <button class="boton primario" type="submit" name="verbo" value="completa">
        Guardar la ficha completa
      </button>
      <button class="boton" type="submit" name="verbo" value="parcial">
        Guardar solo lo que cambié
      </button>
    </div>
    <span class="ayuda">
      «La ficha completa» exige que los tres datos estén diligenciados.
      «Solo lo que cambié» guarda lo que usted escribió y deja lo demás como
      estaba.
    </span>
  <?php else: ?>
    <button class="boton primario" type="submit">Agregar</button>
  <?php endif; ?>

</form>
