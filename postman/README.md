# postman — la colección de la API, lista para importar

Esta API no tiene documentación interactiva integrada (PHP puro, sin
framework: no hay quién la genere — el contrato vive en
[6_contracts.md](../docs/spec_kit/versiones/v1_producto_mariadb/6_contracts.md)).
**Postman cumple ese papel**: aquí está la colección con los endpoints de
la v1 ya armados, para verlos y probarlos con clics.

## Cómo usarla (3 pasos)

1. Instale **Postman** (postman.com/downloads). Si le pide cuenta, puede
   usar la opción de cliente ligero sin registrarse.
2. **Import** (botón arriba a la izquierda) → arrastre el archivo
   `coleccion_v1.postman_collection.json` de esta carpeta.
3. Con el proyecto corriendo (`docker compose up -d`), abra cualquier
   petición y dele **Send**.

## El orden cuenta una historia

Las 13 peticiones están numeradas para recorrerlas de arriba a abajo:
diagnóstico → lecturas (con query string y parámetro de ruta) → el ciclo
de escritura (POST/PUT/PATCH/DELETE) → **la pareja didáctica** (9 y 10: el
mismo body da 422 en PUT y 200 en PATCH) → los errores (404, 422 con lista,
el 500 del código duplicado). Cada petición trae su explicación en la
pestaña de descripción.

## La variable {{base}}

La colección usa la variable `base` = `http://localhost:8022` (el proyecto
del curso). Si está probando **SU reconstrucción** (la de la
[GUIA_IA1](../docs/spec_kit/versiones/v1_producto_mariadb/GUIA_IA1.md), que corre en el puerto 8122): clic en la
colección → pestaña **Variables** → cambie `base` a
`http://localhost:8122`. Una sola edición y las 13 peticiones apuntan a su
proyecto.
