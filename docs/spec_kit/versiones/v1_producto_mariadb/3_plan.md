# Plan técnico — Versión 1: producto + MariaDB (PHP puro)

> **Versión 1** · CÓMO construir lo especificado en [2_spec.md](2_spec.md).
> El porqué de cada decisión: [4_research.md](4_research.md) · contratos
> exactos: [6_contracts.md](6_contracts.md) · orden: [8_tasks.md](8_tasks.md).

---

## 1. Stack

| Pieza | Elección | Por qué |
|---|---|---|
| Lenguaje | **PHP 8.3** (tipos estrictos: `declare(strict_types=1)`) | El lenguaje del curso; los tipos escalares y de retorno son parte del contenido |
| Framework | **NINGUNO** (PHP puro) | Constitución, Artículo 2: lo que se ve es PHP, no magia |
| Gestor de paquetes | **NINGUNO** (sin Composer, sin `vendor/`) | Cero dependencias = cero fricción y cero cajas negras |
| Enrutamiento | Front controller `index.php` (comparaciones simples de ruta con `if`) | Un router legible completo ES la lección |
| Acceso a datos | **PDO** (extensión `pdo_mysql`) con prepared statements | SQL visible y parametrizado; PDO es el estándar del lenguaje |
| Servidor (desarrollo) | **PHP built-in server** (`php -S 0.0.0.0:8022 index.php`) | Todo pasa por el front controller sin configurar Apache/nginx; suficiente y transparente para el curso |
| Contenedor | `php:8.3-cli` + `docker-php-ext-install pdo_mysql` | Imagen oficial; la extensión de MySQL/MariaDB se compila en el build |

## 2. Estructura de carpetas

```
(raíz del proyecto)
├── docker-compose.yml                # UN comando: mariadb + api-facturas (crece por versiones)
├── db/
│   └── init.sql                      # la BD completa, PROVISTA (se copia, no se genera)
└── api_facturas/
    ├── Dockerfile                    # php:8.3-cli + pdo_mysql (el compose lo construye)
    ├── index.php                     # front controller: recibe TODO y enruta
    ├── modelos/
    │   └── Producto.php              # el modelo: entidad clásica (propiedades privadas,
    │                                 #   getters/setters y toArray para el JSON)
    ├── controladores/
    │   └── ControladorProducto.php   # HTTP: lee request, VALIDA el body (422), llama
    │                                 #   servicio y arma la respuesta
    ├── servicios/
    │   ├── IServicioProducto.php     # interface del servicio
    │   ├── ServicioProducto.php      # reglas de negocio; recibe IRepositorioProducto
    │   └── ensamblador.php           # crearServicioProducto() — proto-fábrica (ver §4.3)
    ├── repositorios/
    │   ├── IRepositorioProducto.php  # interface: 5 métodos de datos
    │   └── RepositorioProductoMariaDB.php
    ├── excepciones/
    │   └── NoEncontradoExcepcion.php # la excepción de negocio que el controlador traduce a 404
    └── pruebas/
        └── prueba_capas.php          # criterio 6: el servicio con un repositorio falso, sin BD
```

## 3. Arquitectura en capas (flujo de una petición)

```
HTTP → index.php            (front controller: método + ruta → controlador)
     → ControladorProducto  (lee query/body, valida con el modelo → 422,
                             traduce excepciones a códigos HTTP)
     → IServicioProducto    (interfaz — reglas de negocio)
     → IRepositorioProducto (interfaz — el servicio no sabe qué motor hay detrás)
     → RepositorioProductoMariaDB (PDO + prepared statements)
     → MariaDB
```

**Regla de dependencias:** controlador → servicio → interfaz de repositorio.
Solo `ensamblador.php` conoce las clases concretas.

## 4. Decisiones de diseño clave

### 4.1 Interfaces nativas de PHP desde v1
```php
interface IRepositorioProducto
{
    public function obtenerTodos(int $limite): array;      // lista de objetos Producto
    public function obtenerPorCodigo(string $codigo): ?Producto;  // el modelo, o null
    public function crear(Producto $producto): bool;       // recibe el modelo
    public function actualizar(string $codigo, array $datos): int;  // la usan PUT y PATCH
    public function eliminar(string $codigo): int;
}
```
Las lecturas devuelven **objetos del modelo** (`Producto`); `actualizar` va
con array porque un PATCH puede traer solo algunos campos.
El servicio recibe **la interfaz** por constructor. Esto es lo que compra la
v3: un segundo motor será otra clase con `implements IRepositorioProducto`.

### 4.2 La validación del body vive en el controlador (la frontera HTTP)
PHP puro no trae validación integrada: se construye — y construirla enseña
más que recibirla gratis de un framework. El **controlador** trae los
métodos privados que revisan el body y devuelven la lista de errores
(vacía = válido):

- `validarCodigo(array $datos): array`     → el código: texto de 1 a 10 caracteres
- `validarCampos($datos, obligatorios: true)`  → POST/PUT: los 3 campos deben venir
- `validarCampos($datos, obligatorios: false)` → PATCH: valida SOLO los enviados
- `filtrarColumnas(array $datos): array`   → lista blanca: bota campos desconocidos

El **modelo** (`modelos/Producto.php`) es la clase entidad clásica: las 4
propiedades privadas, sus getters/setters y `toArray()`. El repositorio lo
construye al leer de la BD y así el dato viaja como objeto, no como array
anónimo.

Reglas: `codigo` 1–10 caracteres · `nombre` no vacío · `stock` entero ≥ 0 ·
`valorunitario` numérico ≥ 0. Si hay errores, el controlador responde
**422** con `{estado, mensaje, errores:[…]}` y nada llega al servicio ni a
la BD. (El body vacío en PATCH es 400 y lo decide el **servicio**: no es un
problema de forma sino de regla de negocio.)

### 4.3 `ensamblador.php`: la proto-fábrica honesta
```php
function crearServicioProducto(): IServicioProducto
{
    $repositorio = new RepositorioProductoMariaDB(
        getenv('DB_DSN'), getenv('DB_USUARIO'), getenv('DB_CLAVE')
    );
    return new ServicioProducto($repositorio);
}
```
Sin arrays de motores ni selección: v1 tiene UN motor y el código lo dice.
Cuando v3 agregue PostgreSQL, **solo este archivo** se convierte en la fábrica
real — controladores y servicios no se tocan (ese es el examen de la v3).

### 4.4 SQL del repositorio (PDO, siempre preparado)
```sql
SELECT codigo, nombre, stock, valorunitario FROM producto ORDER BY codigo LIMIT :limite
SELECT … WHERE codigo = :codigo
INSERT INTO producto (codigo, nombre, stock, valorunitario) VALUES (:codigo, :nombre, :stock, :valorunitario)
UPDATE producto SET … WHERE codigo = :codigo      -- los campos que lleguen (PUT: los 3; PATCH: los enviados)
DELETE FROM producto WHERE codigo = :codigo
```
- Conexión PDO **perezosa** (se abre en el primer uso y se reutiliza) con
  `PDO::ERRMODE_EXCEPTION` y **`PDO::ATTR_EMULATE_PREPARES => false`**
  (prepared statements REALES del servidor).
- **Detalle MariaDB #1:** el `:limite` del `LIMIT` debe enlazarse como entero —
  `bindValue(':limite', $limite, PDO::PARAM_INT)` — o el motor rechaza la
  consulta.
- **Detalle MariaDB #2 (trampa clásica):** por defecto, `rowCount()` de un
  UPDATE cuenta filas **cambiadas**, no **encontradas** — un PATCH que escribe
  el mismo valor reportaría 0 filas y parecería un 404. Se corrige en la
  conexión con **`PDO::MYSQL_ATTR_FOUND_ROWS => true`**.
- El SET del UPDATE se arma solo con columnas que vienen del modelo
  (lista blanca), nunca con claves del cliente.
- El driver mysql devuelve `DECIMAL` como string → el repositorio castea:
  `stock → (int)`, `valorunitario → (float)` al serializar.

### 4.5 Traducción de excepciones a HTTP (en el controlador)
| Excepción | HTTP |
|---|---|
| (body con errores de forma — no es excepción) | 422 |
| `InvalidArgumentException` (regla de negocio: límite ≤ 0, body vacío) | 400 |
| `NoEncontradoExcepcion` (código inexistente) | 404 |
| `PDOException` y cualquier otra | 500 (mensaje del motor en `detalle`) |

### 4.6 El front controller (`index.php`)
1. `declare(strict_types=1)` + `require` de las clases (sin autoloader: la
   lista de requires ES el inventario del proyecto).
2. `header('Content-Type: application/json; charset=utf-8')`.
3. Lee `$_SERVER['REQUEST_METHOD']` y el path de `REQUEST_URI`.
4. Enruta con comparaciones simples: `$ruta === '/'` (diagnóstico) ·
   `$ruta === '/api/producto'` (colección) ·
   `str_starts_with($ruta, '/api/producto/')` + `substr` para sacar el
   código — y 404 para todo lo demás. Dentro de cada ruta, `if/elseif`
   por verbo (405 si el verbo no aplica).
5. El body JSON se lee UNA vez: `json_decode(file_get_contents('php://input'), true)`.

## 5. Docker: un solo comando desde v1

La constitución (Artículo 4) manda: `docker compose up -d --build` deja TODO
funcionando. En v1 eso son **dos servicios**:

```yaml
services:
  mariadb:             # mariadb:11 + db/init.sql (la BD completa)
    # volumen mariadbdata (persistencia) · puerto 13326 al host · healthcheck healthcheck.sh
  api-facturas:        # build: ./api_facturas (su Dockerfile)
    # código montado como volumen → guardar un .php = refrescar (PHP reinterpreta)
    # command: php -S 0.0.0.0:8022 index.php
    # DB_DSN apunta al host interno "mysql:host=mariadb;port=3306;..."
    # depends_on: mariadb con condition: service_healthy
volumes:
  mariadbdata:
```

`api_facturas/Dockerfile`: `php:8.3-cli` → instalar `libpq-dev` y compilar
`pdo_mysql` → copiar el código → `CMD php -S 0.0.0.0:8022 index.php`.

**Durante la construcción fase a fase** también se puede correr local si se
tiene PHP 8.3 con pdo_mysql (`php -S localhost:8022 index.php` con las
variables de entorno hacia `localhost:13326`) — el compose es la forma
oficial de entrega.

## 6. Convenciones

Las de la constitución: todo en español, comentario de apertura por archivo,
clases PascalCase (prefijo `I` en interfaces), métodos y variables camelCase,
un archivo por clase, `declare(strict_types=1)` en todo archivo PHP.

## 7. Chequeo de constitución

> **La compuerta 2** del método (ver [SDD_SPECKIT](../../../SDD_SPECKIT.md)):
> antes de pasar a `8_tasks.md` se revisa la
> [constitución](../../1_constitution.md) **artículo por artículo**. Si algo
> no cumple, o se corrige el plan, o se enmienda la constitución. Nunca se
> deja pasar "por esta vez".

| Artículo | Cómo lo cumple esta versión |
|---|---|
| **1** — Propósito didáctico ante todo | Todo en español y comentado para principiantes; se prefiere lo explícito y legible sobre lo compacto. |
| **2** — PHP puro: sin framework y sin Composer | PHP puro: sin framework y sin Composer. Lo que esta versión agrega no introduce dependencias. |
| **3** — Arquitectura de 3 capas estricta | Las capas que esta versión construye respetan la separación estricta (§3 de este plan): el front no toca la BD y la API no devuelve HTML. |
| **4** — Un solo comando para arrancar | `docker compose up -d --build` deja funcionando lo que esta versión declara (§5 de este plan). |
| **5** — Independencia del motor de base de datos | El acceso a datos pasa por interfaces. Si esta versión trae un solo motor, la independencia todavía es **meta**, no estado. |
| **6** — Persistencia y reproducibilidad | Los datos viven en volúmenes; `docker compose down -v` devuelve la BD a su estado original. |
| **7** — Desarrollo con recarga natural | PHP reinterpreta cada petición: guardar el archivo y refrescar es el ciclo, sin reconstruir la imagen. |
| **8** — Convenciones fijas | Puertos, rutas, sobre de respuesta y catálogo de errores, tal como los fija el artículo. |
| **9** — Seguridad en su justa medida académica | Credenciales didácticas y sin secretos reales; la seguridad se mantiene en la medida que el artículo define. |

**Complejidad justificada:** si esta versión se desvía de algún artículo,
la desviación va aquí, con la alternativa más simple que se descartó y por
qué no sirvió. Sin desviaciones anotadas, se entiende que no las hay.
