# Proyecto PHP — construcción por versiones

Proyecto de curso (USB Medellín). Aquí NO se descarga un sistema terminado:
**se construye un sistema real por versiones en PHP puro**, guiado por
especificaciones. El repositorio siempre contiene la **versión en curso,
funcionando** — usted la ejecuta, la estudia y luego la **reconstruye desde
cero** en su propio proyecto.

> 🐳 Esta variante corre sobre **Docker**. Para las salas SIN Docker existe
> el repositorio gemelo
> [proyecto_php_sin_docker](https://github.com/ccastro2050/proyecto_php_sin_docker)
> (XAMPP + PHP 8.3) — misma API, misma spec, otra infraestructura.

---

## 1. Cómo le trabaja el estudiante (léame primero)

### Qué necesita instalado (una sola vez)

| Herramienta | Para qué |
|---|---|
| **Git** | Clonar el repositorio y traer versiones nuevas |
| **Docker Desktop** | La BD y la API corren en contenedores (no se instala MariaDB ni PHP) |
| **VS Code** | El editor — y su terminal integrada (*Terminal → New Terminal*) |

> PHP local es **opcional** (solo para desarrollar fase a fase sin Docker):
> PHP 8.3 con la extensión `pdo_mysql` habilitada.

### Primera vez: cargar y EJECUTAR la versión (un solo comando)

En la terminal integrada de VS Code (*Terminal → New Terminal*, PowerShell):

> ⚠️ **ANTES de clonar — solo si usted ya corrió OTRO proyecto de estos
> cursos en este PC:** puede quedar un contenedor viejo encendido ocupando
> el puerto 8022 (pasa al reiniciar el PC: la API vieja revive sin su
> base de datos y "secuestra" el puerto — el contenedor huérfano). El
> síntoma: Swagger abre, pero todo responde 500 con *"No address
> associated with hostname"*, y usted cree que el error es de ESTE
> proyecto cuando en realidad está hablando con el viejo. Verifíquelo y
> apáguelo primero:
>
> ```powershell
> docker ps --filter "name=proyecto_"
> # ↑ VERIFICAR: ¿aparece algún proyecto del curso todavía encendido?
> docker ps --filter "name=proyecto_" -q | ForEach-Object { docker stop $_ }
> # ↑ LIMPIAR: apaga TODOS los contenedores del curso de una sola vez
> ```
>
> La limpieza no borra nada (los datos quedan en sus volúmenes) y
> funciona aunque ya no tenga la carpeta vieja. También sirve el botón
> Stop de Docker Desktop. Solo entonces continúe.

```powershell
git clone https://github.com/ccastro2050/proyecto_php.git
cd proyecto_php
docker compose up -d --build
```

**Eso es todo.** La primera vez tarda unos minutos (descarga imágenes). Al
terminar quedan corriendo la base de datos (bdfacturas completa en MariaDB) y
la API:

| Qué | Dónde |
|---|---|
| **API Facturas** — diagnóstico | http://localhost:8022/ |
| Listar productos | http://localhost:8022/api/producto |
| **phpMyAdmin** (administrar MariaDB desde el navegador) | http://localhost:8101 |
| MariaDB (para SQLTools/DBeaver, opcional) | `localhost:13326` · `paradigmas`/`paradigmas123` |

Pruebe la joya didáctica de la v1: PUT con solo `{"stock": 99}` → 422; el
mismo body en PATCH → 200. Esa diferencia es parte de lo que enseña la
versión (contratos exactos en el spec kit).

> ℹ️ Este proyecto usa los puertos 8022, 8101 y 13326: si alguno ya está
> ocupado en su máquina, cámbielo en `docker-compose.yml` (el lado izquierdo
> del `"puerto:puerto"`).

### Los días siguientes (volver a encender)

```powershell
docker compose up -d        # segundos; los datos se conservan
```

### Cuando hay cambios

| Qué cambió | Qué hacer |
|---|---|
| **Usted edita un `.php`** | **Nada** — el código está montado como volumen y PHP reinterpreta cada petición: guardar y refrescar (F5) |
| **El profesor publicó una versión nueva** | `git pull` y `docker compose up -d --build` |
| **Cambió el `Dockerfile`** | `docker compose up -d --build` (reconstruye la imagen) |
| **Quiere resetear la BD** a sus datos originales | `docker compose down -v` y luego `docker compose up -d` (⚠️ borra los datos) |
| **Apagar todo** | `docker compose down` (los datos se conservan) |

### Y ahora, SU trabajo: reconstruirla desde cero

Ejecutar la versión del repo es solo el punto de partida. Lo que se evalúa es
**reconstruirla usted mismo, en una carpeta propia (fuera del clon)**,
siguiendo las especificaciones — con o sin ayuda de IA:

> 🤖 ¿Va a trabajar con IA? Siga la **[Guía para construir la versión con
> IA](docs/GUIA_IA.md)** — cubre los dos caminos con su prompt exacto listo
> para copiar: **chat web** (Gemini, DeepSeek, ChatGPT: qué archivos subirle)
> e **IDE agéntico** (Antigravity, Cursor, Claude Code: cómo supervisar al
> agente).

### Conceptos resumidos (los que acaba de usar)

| Concepto | En una frase |
|---|---|
| **Clonar** | Descargar el repositorio con su historial; `git pull` trae lo nuevo |
| **Contenedor** | BD y API corren en "cajas" de Docker: nada que instalar, se borran y recrean sin miedo |
| **docker compose** | UN archivo declara todo el sistema y UN comando lo levanta (`up -d`) |
| **Volumen** | Donde viven los datos: `down` los conserva, `down -v` los borra (reset) |
| **PHP reinterpreta** | No hay "reload": cada petición vuelve a leer los `.php` — guardar y refrescar ES el ciclo |
| **Spec kit** | Los documentos que dicen QUÉ/CÓMO/EN QUÉ ORDEN — la fuente de verdad |
| **Versión / tag** | Un incremento cerrado y verificado (`v1`, `v2`, …): se avanza solo en verde |

> Detalle de los conceptos Docker: [docs/CONCEPTOS_DOCKER.md](docs/CONCEPTOS_DOCKER.md).

---

## 2. Estructura del repositorio

Qué es cada carpeta y cada archivo, y para qué sirve:

```
proyecto_php/
├── docker-compose.yml           # TODO el sistema declarado: MariaDB + API + phpMyAdmin
│                                #   (el "un solo comando" del proyecto)
├── db/
│   ├── init.sql                 # Crea bdfacturas COMPLETA (12 tablas, triggers, datos).
│   │                            #   MariaDB lo ejecuta sola la PRIMERA vez (volumen vacío)
│   └── init_phpmyadmin.sql      # BD interna de phpMyAdmin (habilita el Diseñador)
│
├── backupdb/                    # Respaldos (dumps) de la BD — su README explica
│                                #   cómo hacer el backup y cómo restaurarlo
│
├── postman/                     # La colección de Postman lista para importar:
│                                #   los endpoints de la v1 con clics (no hay Swagger
│                                #   en PHP puro — Postman cumple ese papel)
│
├── api_facturas/                # LA API DE LA v1 — PHP puro, sin framework (puerto 8022)
│   ├── Dockerfile               # Su imagen: php:8.3-cli + extensión pdo_mysql
│   ├── index.php                # Front controller: TODA petición entra aquí y se enruta
│   ├── controladores/           # Capa 1 — HTTP: valida el body (422) y traduce a
│   │                            #   códigos de estado y JSON
│   ├── servicios/               # Capa 2 — negocio: interfaz, reglas y el ensamblador
│   │                            #   (la proto-fábrica que arma las capas)
│   ├── repositorios/            # Capa 3 — datos: interfaz + SQL con PDO para MariaDB
│   ├── modelos/                 # Producto: la clase entidad clásica (propiedades
│   │                            #   privadas + getters/setters + toArray)
│   ├── excepciones/             # NoEncontradoExcepcion (el servicio la lanza → 404)
│   └── pruebas/                 # prueba_capas.php: repositorio FALSO en memoria
│                                #   (demuestra que las capas se desacoplan de verdad)
├── docs/
│   ├── spec_kit/                # LAS ESPECIFICACIONES: constitución permanente +
│   │                            #   una carpeta de specs por versión (v1, v2, …)
│   ├── GUIA_IA.md               # Cómo reconstruir la versión desde 0 con ayuda de una IA
│   ├── PARADIGMA_POO.md         # Material conceptual: POO, SOLID+capas, ACID,
│   ├── SOLID_CAPAS_PATRONES.md         #   Docker y SDD (un .md por tema)
│   ├── PRINCIPIOS_ACID.md       #
│   ├── CONCEPTOS_DOCKER.md      #
│   ├── SDD_SPECKIT.md           #
│   ├── TUTORIAL_PHPMYADMIN.md   # Tutoriales de administración de la BD, paso a paso
│   ├── TUTORIAL_VSCODE_SQLTOOLS.md  #   con capturas reales
│   └── img_phpmyadmin/ img_sqltools/  # Las capturas de esos tutoriales
│
├── .gitignore / .gitattributes  # Higiene del repo (ignora .session.sql, normaliza EOL)
└── README.md                    # Este archivo
```

La regla de lectura: **el sistema vive en `docker-compose.yml`**, la API
vive en `api_facturas/` (una carpeta por capa), y **todo lo que explica**
vive en `docs/`. Cuando lleguen las versiones siguientes, aquí aparecerán
más carpetas de componentes (y el compose crecerá con ellas).

## 3. La ruta de versiones

```
v1  api_facturas (PHP puro): CRUD de producto, solo MariaDB   ← USTED ESTÁ AQUÍ (cerrada: tag v1)
v2  más tablas (persona, factura maestro-detalle…)
v3  segundo motor (PostgreSQL) — nace la fábrica de repositorios
v4  tercer motor (SQL Server) + compose completo
v5  frontend PHP
```

La regla del juego: la **constitución** es permanente, cada versión tiene su
propia spec, y una versión está TERMINADA solo cuando pasa sus criterios de
aceptación (se cierra con tag). Detalle completo:
**[mapa de versiones](docs/spec_kit/versiones/0_mapa_versiones.md)**.

## 4. Las especificaciones de la versión actual (v1)

| Documento | Qué contiene |
|---|---|
| [Constitución](docs/spec_kit/1_constitution.md) | Las reglas permanentes del proyecto (PHP puro, capas, un comando) |
| [2_spec.md](docs/spec_kit/versiones/v1_producto_mariadb/2_spec.md) | QUÉ construir y los 6 criterios de aceptación |
| [3_plan.md](docs/spec_kit/versiones/v1_producto_mariadb/3_plan.md) | CÓMO: stack, carpetas, capas e interfaces |
| [4_research.md](docs/spec_kit/versiones/v1_producto_mariadb/4_research.md) | Las decisiones y sus alternativas descartadas *(lectura opcional)* |
| [5_data_model.md](docs/spec_kit/versiones/v1_producto_mariadb/5_data_model.md) | La BD completa (dada) y la tabla `producto` que usa la v1 |
| [6_contracts.md](docs/spec_kit/versiones/v1_producto_mariadb/6_contracts.md) | Los 7 endpoints con formatos exactos (5 verbos HTTP) |
| [7_quickstart.md](docs/spec_kit/versiones/v1_producto_mariadb/7_quickstart.md) | Smoke test para validar lo construido |
| [8_tasks.md](docs/spec_kit/versiones/v1_producto_mariadb/8_tasks.md) | Las fases de construcción, en orden |

## 5. Material conceptual del curso

| Documento | Qué cubre |
|---|---|
| [El flujo de una petición](docs/FLUJO_DE_UNA_PETICION.md) | **Léalo primero:** dónde está el GET, dónde se captura el POST, y el viaje completo de una petición por las capas — con los comandos para probar los 5 verbos |
| [Colección de Postman](postman/README.md) | Los 13 endpoints de la v1 listos para importar y probar con clics — incluida la pareja PUT=422 vs PATCH=200 |
| [SDD y Spec Kit](docs/SDD_SPECKIT.md) | La metodología con la que se trabaja este curso: la spec manda sobre el código |
| [Calidad de las pruebas](docs/CALIDAD_DE_PRUEBAS.md) | Cobertura, la métrica CRAP y mutation testing: cómo saber si sus pruebas de verdad protegen — y por qué hoy es reto opcional, no alcance del proyecto |
| [Programación asincrónica](docs/PROGRAMACION_ASINCRONICA.md) | Qué resuelve el async/await en la web, qué se daña sin él (con diagramas), y cómo se ve en el código de este proyecto |
| [El paradigma P.O.O. en PHP](docs/PARADIGMA_POO.md) | Qué es un paradigma, los 4 pilares, y las `interface` de PHP + la validación como frontera |
| [SOLID, capas y patrones de diseño](docs/SOLID_CAPAS_PATRONES.md) | Los 5 principios y las capas — y en qué versión se demuestra cada uno |
| [Principios ACID](docs/PRINCIPIOS_ACID.md) | Las 4 garantías transaccionales, por qué una facturación las exige, y el contraste con BASE |
| [Conceptos de Docker](docs/CONCEPTOS_DOCKER.md) | Imagen, contenedor, volumen, compose (con el `docker-compose.yml` del proyecto explicado línea por línea) y por qué NO se necesita Kubernetes |
| [Tutorial phpMyAdmin](docs/TUTORIAL_PHPMYADMIN.md) | Administrar bdfacturas desde el navegador: estructura, Diseñador, SQL, edición y respaldo — paso a paso con capturas |
| [Tutorial SQLTools (VS Code)](docs/TUTORIAL_VSCODE_SQLTOOLS.md) | La misma BD sin salir del editor: instalación, conexión, explorar y consultar — paso a paso con capturas |

---

*Proyecto PHP · USB Medellín · Base de datos bdfacturas (facturación + RBAC).*
