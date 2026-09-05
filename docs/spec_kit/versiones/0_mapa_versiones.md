# Mapa de versiones — Proyecto PHP

> **Cómo se trabaja este proyecto: por versiones (desarrollo incremental guiado
> por especificaciones).** Así maneja SDD el crecimiento de un sistema: la
> **constitución es permanente** ([../1_constitution.md](../1_constitution.md))
> y cada versión tiene **su propia especificación, plan y tareas** en una
> carpeta `vN_nombre/`. La spec de una versión es EL documento que se le
> entrega a la IA (o al estudiante) para construir ESA versión — ni más ni menos.
>
> Regla de avance: una versión está TERMINADA cuando pasa todos los criterios
> de aceptación de su `2_spec.md`. Solo entonces se escribe (o se aborda) la
> spec de la siguiente. En la rama `main` solo vive la versión en curso.

---

## La ruta (motor primero; el sistema crece componente a componente)

| Versión | Carpeta | Qué EXISTE al terminarla | Qué concepto nuevo enseña |
|---|---|---|---|
| **v1** | [v1_producto_mariadb/](v1_producto_mariadb/2_spec.md) | El CRUD de **producto** de punta a punta: `api_facturas` (PHP puro + PDO) contra **MariaDB** **y su front** (PHP, puerto 8020) con las pantallas de esa tabla. Ningún otro motor, ninguna otra tabla. (La BD `bdfacturas` se crea COMPLETA desde el inicio — es infraestructura dada; el código solo toca `producto`.) | Arquitectura en capas con `interface` de PHP desde el día 1, **y la separación front/API a nivel de sistema** |
| **v2** | v2_mas_tablas/ *(se especifica al terminar v1)* | persona, empresa, cliente, vendedor y factura (maestro-detalle + trigger), **con sus pantallas**, solo MariaDB | Validación por entidad; FKs, integridad referencial y lógica en la BD |
| **v3** | v3_segundo_motor/ | Lo mismo, ahora también contra **PostgreSQL** | Nace la configuración de motor y la **fábrica** — abierto/cerrado en acción: cero cambios en controladores, servicios **ni pantallas** |
| **v4** | v4_sqlserver_docker/ | Tercer motor (**SQL Server**), las 12 tablas, compose completo | Liskov entre repositorios; contenedores, volúmenes y healthchecks |

> **Antes había una v5 que era «el front».** Ya no: el front de cada versión
> va **dentro** de esa versión (Artículo 1.1 de la
> [constitución](../1_constitution.md)). Dejarlo para el final hacía que los
> desajustes entre la API y la pantalla se descubrieran con cuatro versiones
> de API encima — y entonces ya no se corrigen, se rehacen.
>
> Fíjese además en lo que le pasa a la v3 por haberlo movido: «cero cambios en
> controladores y servicios» ahora dice **«ni pantallas»**. Cambiar de motor
> sin que la pantalla se entere es una promesa mucho más fuerte… y solo se
> puede comprobar si la pantalla existe.

## Reglas del trabajo por versiones

1. **La constitución no se toca entre versiones.** Si una versión exige cambiar
   una regla de la constitución, eso es una decisión mayor que se discute aparte.
2. **Cada carpeta de versión es autocontenida**: con la constitución + esa
   carpeta se puede construir la versión desde el estado anterior (v1 parte de
   cero) sin leer nada más.
3. **Una versión incluye su front.** No está terminada cuando la API responde:
   está terminada cuando la pantalla de esa versión muestra lo que la API
   devuelve, y sigue en pie —con su aviso— cuando la API no responde
   (Artículo 1.1 de la [constitución](../1_constitution.md)).
4. **El código de una versión no anticipa a la siguiente**: en v1 NO se escribe
   la fábrica multi-motor "por si acaso" — se escribe la interfaz, y la fábrica
   llegará cuando un segundo motor la justifique (v3). **YAGNI con
   dirección**: *You Aren't Gonna Need It* ("no lo vas a necesitar") — no se
   escribe hoy lo que solo hará falta mañana, pero se sabe hacia dónde va.
5. **Cada versión termina en verde**: criterios de aceptación verificables,
   commit (y tag `v1`, `v2`, …) al cerrarla.
6. La spec de la versión siguiente **parte del estado real** dejado por la
   anterior — si el código divergió de la spec, primero se reconcilia
   (la spec siempre refleja el estado actual: deuda de especificación).
