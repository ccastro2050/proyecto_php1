# Constitución del Proyecto PHP

> Principios **innegociables** que gobiernan todo el proyecto. Esta
> constitución es **permanente**: describe el sistema COMPLETO al que se llega
> al final, y no cambia entre versiones.
>
> El proyecto se construye **por versiones** (desarrollo incremental guiado por
> especificaciones): ver el [mapa de versiones](versiones/0_mapa_versiones.md).
> Cada artículo aplica desde la versión que introduce su alcance — por ejemplo,
> en la v1 solo existe `api_facturas` con MariaDB, así que los artículos
> sobre el front, los otros motores y el compose completo son la META, no el
> estado actual.

---

## Artículo 1 — Propósito didáctico ante todo

Este proyecto existe para **enseñar PHP construyendo software real con
arquitectura** a estudiantes universitarios. Ante cualquier disyuntiva entre
"lo más profesional" y "lo más claro para aprender", gana la claridad:

- Todo el código, comentarios, mensajes y documentación se escriben en **español**.
- Cada archivo abre con un comentario que explica su papel en la arquitectura.
- Se prefiere código explícito y repetitivo-pero-legible sobre metaprogramación compacta.

## Artículo 2 — PHP puro: sin framework y sin Composer

- **PHP 8.3+ "vanilla"**: cero dependencias externas, cero `composer.json`,
  cero `vendor/`. Lo que el estudiante ve es PHP del lenguaje, no magia de un
  framework.
- El enrutamiento vive en UN front controller (`index.php`) legible completo.
- Los contratos entre capas son **`interface` nativas de PHP** — el lenguaje
  las trae de fábrica; aquí se usan de verdad.
- El acceso a datos es **PDO** con *prepared statements*: el SQL queda
  **visible** (nada de ORM que lo esconda).

## Artículo 3 — Arquitectura de 3 capas estricta

```
CAPA 1: FRONT (PHP, :8000)    — solo pinta HTML y llama APIs; NUNCA toca la BD
CAPA 2: API (PHP)            — api_facturas :8022
CAPA 3: DATOS                 — PostgreSQL | MariaDB | SQL Server (bdfacturas)
```

- El front **no abre conexiones de base de datos**; solo habla HTTP con las APIs.
- Las APIs no generan HTML; solo JSON.
- Dentro de cada API: controlador → servicio → repositorio, comunicados por
  interfaces; solo el ensamblador conoce clases concretas.

## Artículo 4 — Un solo comando para arrancar

`docker compose up -d --build` debe dejar TODO funcionando. Sin pasos
manuales, sin instalar PHP ni PostgreSQL local más allá de Docker. Los
estudiantes tienen máquinas heterogéneas: el entorno vive completo en
contenedores.

## Artículo 5 — Independencia del motor de base de datos

- El motor activo se elige con **configuración**, nunca con cambios de código.
- Los tres motores contienen la **misma base de datos** (`bdfacturas_*_local`):
  mismas 12 tablas, mismos datos, mismos triggers y procedimientos.
- Todo acceso a datos pasa por interfaces + un punto único de ensamblaje
  (inversión de dependencias — la D de SOLID).

## Artículo 6 — Persistencia y reproducibilidad

- Los datos viven en **volúmenes** Docker: sobreviven a `docker compose down`.
- `docker compose down -v` devuelve la BD a su estado original (`db/init.sql`
  se re-ejecuta sobre volumen vacío). Ese es el "botón de pánico" oficial.

## Artículo 7 — Desarrollo con recarga natural

El código se monta como volumen dentro del contenedor. En PHP no hay "reload"
que configurar: **cada petición HTTP reinterpreta los archivos** — guardar un
`.php` y refrescar el navegador ES el ciclo de desarrollo. Reconstruir la
imagen (`--build`) solo es necesario si cambia el `Dockerfile`.

## Artículo 8 — Convenciones fijas

| Cosa | Convención |
|---|---|
| Puertos públicos | front 8020 · · api_facturas **8022** |
| Puertos de BD hacia el host | PostgreSQL **15452** · MariaDB **13326** · SQL Server **11453** |
| Hosts internos (entre contenedores) | `postgres:5432` · `mariadb:3306` · `sqlserver:1433` |
| Credenciales BD | usuario `paradigmas` / clave `paradigmas123` (SQL Server: `sa` / `Paradigmas123!`) |
| Bases de datos | `bdfacturas_postgres_local` · `bdfacturas_mariadb_local` · `bdfacturas_sqlserver_local` |
| Nombres de código | Clases e interfaces en PascalCase con prefijo `I` para interfaces; métodos y variables en camelCase; archivos = nombre de su clase |
| Estructura por API | `index.php` · `modelos/` · `controladores/` · `servicios/` · `repositorios/` · `excepciones/` |

## Artículo 9 — Seguridad en su justa medida académica

- Los valores SQL siempre van en **prepared statements** (nunca concatenados).
- Las contraseñas de usuarios de la aplicación se almacenan con **hash**
  (nunca texto plano en código nuevo).
- Las credenciales de infraestructura (paradigmas/paradigmas123) son públicas
  y didácticas **a propósito**: este entorno jamás se despliega a producción.
