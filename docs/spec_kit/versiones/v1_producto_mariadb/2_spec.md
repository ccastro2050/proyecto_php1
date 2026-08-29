# Especificación — Versión 1 del proyecto: api_facturas con producto + MariaDB

> **Versión 1** del desarrollo incremental ([mapa de versiones](../0_mapa_versiones.md)).
> Rige la constitución del proyecto: [../../1_constitution.md](../../1_constitution.md).
> En v1 el sistema completo ES esto: **no existe frontend, y la API solo conoce una entidad y un motor.** (La BD `bdfacturas`
> sí se crea COMPLETA desde el inicio — es infraestructura dada, ver
> [5_data_model.md](5_data_model.md); lo que crece por versiones es la API.)
>
> | Documento de esta versión | Contenido |
> |---|---|
> | **2_spec.md** (este) | QUÉ construir en v1 y sus criterios de aceptación |
> | [3_plan.md](3_plan.md) | CÓMO: stack, estructura y diseño de las capas |
> | [4_research.md](4_research.md) | Decisiones y alternativas *(lectura opcional)* |
> | [5_data_model.md](5_data_model.md) | La BD completa (dada) y la tabla `producto` |
> | [6_contracts.md](6_contracts.md) | Los 7 endpoints con formatos exactos |
> | [7_quickstart.md](7_quickstart.md) | Arranque y smoke test |
> | [8_tasks.md](8_tasks.md) | Orden de construcción por fases verificables |

---

## 1. Propósito de la v1

Construir la **primera rebanada vertical (corte vertical)** de la API de
facturación en **PHP puro**: el CRUD completo de **una sola entidad
(`producto`)** contra **un solo motor (MariaDB)** — pero con la
**arquitectura en capas completa desde el primer día**: controlador →
servicio → repositorio, comunicados por **interfaces nativas de PHP**.

> **¿Qué es una "rebanada vertical"?** En lugar de construir el sistema por
> capas horizontales ("primero TODOS los repositorios, luego TODOS los
> servicios…" — donde nada funciona hasta el final), se construye un corte
> que **atraviesa todas las capas de arriba a abajo** para UNA funcionalidad.
> Como una rebanada de pastel: un solo corte, pero con todas las capas.
>
> ```
> ┌─────────────────────────── el sistema completo ───────────────────────────┐
> │  CONTROLADOR │ producto █ │ persona    │ factura    │ ...las demás (v2)   │
> │  SERVICIO    │ producto █ │ persona    │ factura    │ ...                 │
> │  REPOSITORIO │ producto █ │ persona    │ factura    │ ...                 │
> │  BD          │ producto █ │ persona    │ factura    │ ...                 │
> └──────────────┴─────▲──────┴────────────┴────────────┴─────────────────────┘
>                      └── la v1 ES esta rebanada: funciona de punta a punta
> ```
>
> Ventaja: algo funciona **desde la v1** y la arquitectura queda validada —
> si las capas encajan para `producto`, las siguientes rebanadas (v2) caen en
> surcos ya hechos.

La v1 es pequeña a propósito: su valor no está en la funcionalidad sino en
dejar el **esqueleto arquitectónico correcto** sobre el que las versiones
siguientes agregan tablas (v2), motores (v3, v4) y el
frontend (v5) **sin reescribir lo construido**.

## 2. Alcance

**Incluye:**
- CRUD de `producto`: listar, obtener por código, crear, reemplazar,
  actualizar parcialmente, eliminar.
- **Modelo clásico**: la clase `Producto` con las 4 propiedades **privadas**,
  getters y setters (encapsulamiento) y `toArray()` para el JSON — el dato
  viaja como objeto, no como array anónimo.
- **Validación en el controlador** (la frontera HTTP), con un método por
  verbo: PHP puro no trae validación integrada — se construye a mano, y eso
  es contenido del curso.
- Capas con interfaces: `IRepositorioProducto` (interface PHP) implementada
  por `RepositorioProductoMariaDB`; el servicio depende de la interfaz.
- Configuración por variables de entorno (DSN de PDO, usuario, clave).
- **Un solo comando** (Artículo 4 de la constitución): `docker-compose.yml`
  mínimo — MariaDB + la API con su `Dockerfile` — de modo que
  `docker compose up -d --build` deja todo funcionando.
- Endpoint `/` de diagnóstico.

**No incluye (y es deliberado — ver [mapa de versiones](../0_mapa_versiones.md)):**
- **Ningún frontend** (llega en v5).
- Endpoints para otras entidades (v2) — las otras 11 tablas EXISTEN en la BD,
  pero el código de la v1 solo puede nombrar `producto`.
- Otros motores y la fábrica de repositorios (v3, v4).
- Frameworks, Composer y librerías externas (constitución, Artículo 2).
- Autenticación y uso del trigger/SPs de facturación (los aprovechará la v2).

## 3. Requisitos funcionales

> La v1 usa **los cinco verbos HTTP** (GET, POST, PUT, PATCH, DELETE) y las
> **tres vías de envío de datos**: parámetro de ruta (`/{codigo}`), query
> string (`?limite=N`) y body JSON. Es parte del objetivo didáctico.

### RF1 — Listar productos (GET + query string)
`GET /api/producto` → 200 con envoltura `{tabla, limite, total, datos:[…]}`.
- Query param opcional `limite` (entero > 0, por defecto 1000).
- Tabla vacía → **204** sin cuerpo.

### RF2 — Obtener por código (GET + parámetro de ruta)
`GET /api/producto/{codigo}` → 200 con el producto; inexistente → 404.

### RF3 — Crear producto (POST + body)
`POST /api/producto` con body JSON validado por la **validación del controlador**
(`codigo` 1–10 caracteres, `nombre` no vacío, `stock` entero ≥ 0,
`valorunitario` numérico ≥ 0 — todos obligatorios).
Éxito → 200 `{estado, mensaje}`; body inválido → **422 con la lista de
errores**; código duplicado → 500 con el error del motor en `detalle`.

### RF4 — Reemplazar producto (PUT + body completo)
`PUT /api/producto/{codigo}` con body de **todos los campos obligatorios**
(`nombre`, `stock`, `valorunitario`): PUT reemplaza el recurso completo —
omitir un campo es 422, no "dejarlo como estaba".
Devuelve `filasAfectadas`; código inexistente → 404.

### RF5 — Actualizar parcialmente (PATCH + body parcial)
`PATCH /api/producto/{codigo}` con body de **campos opcionales**: solo se
modifican los enviados (cada uno validado si llega). Es el contraste
didáctico con PUT. Devuelve `filasAfectadas`; inexistente → 404;
body vacío → 400.

### RF6 — Eliminar producto (DELETE)
`DELETE /api/producto/{codigo}`. Devuelve `filasEliminadas`;
inexistente → 404.

### RF7 — Diagnóstico
`GET /` → JSON con mensaje, versión (`"v1"`) y la ruta de los contratos.

## 4. Requisitos no funcionales

- **RNF1 — Capas estrictas:** el controlador no toca SQL; el servicio no
  conoce HTTP ni el motor; el repositorio no conoce HTTP. Contratos con
  `interface` de PHP.
- **RNF2 — PHP puro:** sin framework, sin Composer, sin `vendor/` (Artículo 2).
- **RNF3 — SQL SIEMPRE en prepared statements de PDO**; nada de concatenar
  valores.
- **RNF4 — Errores uniformes:** `{estado, mensaje, detalle}` (y `errores:[…]`
  en el 422); InvalidArgumentException→400 · NoEncontradoExcepcion→404 ·
  PDOException y demás→500.
- **RNF5 — Sin anticipación:** ni fábrica multi-motor ni selección de motor
  en v1 (los introduce la v3 cuando exista el segundo motor).

## 5. Criterios de aceptación

1. **`docker compose up -d --build` — un solo comando —** deja corriendo la
   BD (creada con el `db/init.sql` provisto: 12 tablas) y la API; `GET /`
   responde el JSON de diagnóstico. Guardar un `.php` y refrescar el
   navegador muestra el cambio (sin reiniciar nada: PHP reinterpreta).
2. `GET /api/producto` devuelve los 8 productos de ejemplo con
   `{tabla:"producto", total:8, datos:[…]}`, y `GET /api/producto?limite=3`
   devuelve exactamente 3.
3. `GET /api/producto/PR001` devuelve la Laptop Lenovo; `/api/producto/PR999`
   responde 404 con mensaje claro.
4. Ciclo completo con los 5 verbos: `POST` crea `PR009` → `PUT` lo reemplaza
   completo → `PATCH` le cambia solo el stock → `GET` lo confirma → `DELETE`
   lo elimina, y un segundo `DELETE` responde 404. Además, un `PUT` sin el
   campo `nombre` responde 422 (reemplazo completo) mientras el mismo body en
   `PATCH` responde 200 (parcial) — la diferencia entre ambos verbos.
5. `POST` con `stock: -5` o sin `nombre` → **422 con la lista de errores**
   (lo rechaza la validación del controlador, nunca llega a la BD); `POST` con código
   duplicado → 500 con el error del motor.
6. Prueba de capas (la evidencia de que la arquitectura quedó bien): el
   servicio se puede probar con un repositorio **falso** en memoria que
   implemente `IRepositorioProducto`, sin MariaDB corriendo
   (`php pruebas/prueba_capas.php` o script equivalente).

## 6. Glosario mínimo

| Término | Significado |
|---|---|
| **Documento (del kit)** | Texto que DESCRIBE qué/cómo construir; se lee y de él sale el código |
| **Artefacto (provisto)** | Archivo que se entrega LISTO y se usa tal cual, sin generarlo ni modificarlo — en v1: `db/init.sql` (la BD completa) |
| **Criterio de aceptación** | Prueba verificable que decide si la versión está terminada (no es opinión) |
| **Rebanada vertical** | Un recorte del sistema que atraviesa TODAS las capas (HTTP→negocio→datos) aunque cubra una sola entidad |
| **Front controller** | El único `.php` que recibe TODAS las peticiones (`index.php`) y las enruta al controlador que corresponde |

## 7. Definición de TERMINADA

Los 6 criterios pasan → commit + tag `v1` → recién entonces se escribe la spec
de la v2 ([mapa](../0_mapa_versiones.md)).

## 8. Clarificaciones

> **Qué es esta sección:** el registro de las ambigüedades detectadas ANTES
> de planear, con la respuesta que se acordó y su razón. Es **la compuerta
> 1** del método (ver [SDD_SPECKIT](../../../SDD_SPECKIT.md)): mientras
> quede un `[NECESITA ACLARACIÓN: …]` en los requisitos de arriba, esta
> versión no pasa a la planeación.
>
> Las entradas de abajo se reconstruyeron **al cerrar la versión**, a
> partir de las decisiones que sus propios contratos ya dejaban fijadas.
> De aquí en adelante esta sección se llena **en vivo**, antes del
> `3_plan.md` — que es como debe ser.

| # | La pregunta | La respuesta acordada, con su razón | Dónde quedó |
|---|---|---|---|
| C1 | El listado sin filas, ¿es un error o un resultado? | Un resultado: **204 sin cuerpo**. Vacío no es error. | RF de listar · contrato del `GET` |
| C2 | `?limite=0` o negativo, ¿422 o 400? | **400**: la FORMA del dato es correcta (sí es un entero); lo que se rompe es una regla de negocio. El 422 se reserva para el body mal formado. | Contrato del `GET` · convenciones |
| C3 | Crear con una llave que ya existe, ¿409 o 500? | **500**, con el error del motor en `detalle`: la llave la defiende la BD, no la API. Convertirlo en 409 sería lógica de negocio que esta versión no pide. | Convenciones de error · contrato del `POST` |
| C4 | `PATCH` con el body vacío, ¿200 sin hacer nada, o error? | **400**: pedir una actualización sin decir qué actualizar es una regla de negocio rota. | Contrato del `PATCH` |

**Cómo se escribe una entrada nueva:** la pregunta tal como se hizo (no
"revisar el borrado", sino "¿físico o lógico?"), la respuesta **con su
razón**, y el documento donde quedó plasmada. Si la respuesta cambia un
requisito, se corrige el requisito allá arriba: esta sección lo registra,
no lo reemplaza.
