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
| **v1** | [v1_producto_mariadb/](v1_producto_mariadb/2_spec.md) | SOLO `api_facturas` (PHP puro + PDO) con el CRUD de **producto** contra **MariaDB**. **Nada de front, ningún otro motor.** (La BD `bdfacturas` se crea COMPLETA desde el inicio — es infraestructura dada; la API solo toca `producto`.) | Arquitectura en capas con `interface` de PHP desde el día 1 |
| **v2** | v2_mas_tablas/ *(se especifica al terminar v1)* | api_facturas con persona, empresa, cliente, vendedor y factura (maestro-detalle + trigger), solo MariaDB | Validación por entidad; FKs, integridad referencial y lógica en la BD |
| **v3** | v3_segundo_motor/ | Lo mismo, ahora también contra **PostgreSQL** | Nace la configuración de motor y la **fábrica** — abierto/cerrado en acción: cero cambios en controladores y servicios |
| **v4** | v4_sqlserver_docker/ | Tercer motor (**SQL Server**), las 12 tablas, compose completo | Liskov entre repositorios; contenedores, volúmenes y healthchecks |
| **v5** | v5_front/ | Se suma el **frontend PHP** (puerto 8020) que consume la API | Separación de capas a nivel de sistema; el front no toca la BD |

## Reglas del trabajo por versiones

1. **La constitución no se toca entre versiones.** Si una versión exige cambiar
   una regla de la constitución, eso es una decisión mayor que se discute aparte.
2. **Cada carpeta de versión es autocontenida**: con la constitución + esa
   carpeta se puede construir la versión desde el estado anterior (v1 parte de
   cero) sin leer nada más.
3. **El código de una versión no anticipa a la siguiente**: en v1 NO se escribe
   la fábrica multi-motor "por si acaso" — se escribe la interfaz, y la fábrica
   llegará cuando un segundo motor la justifique (v3). **YAGNI con
   dirección**: *You Aren't Gonna Need It* ("no lo vas a necesitar") — no se
   escribe hoy lo que solo hará falta mañana, pero se sabe hacia dónde va.
4. **Cada versión termina en verde**: criterios de aceptación verificables,
   commit (y tag `v1`, `v2`, …) al cerrarla.
5. La spec de la versión siguiente **parte del estado real** dejado por la
   anterior — si el código divergió de la spec, primero se reconcilia
   (la spec siempre refleja el estado actual: deuda de especificación).
