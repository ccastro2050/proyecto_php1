# SDD y Spec Kit — desarrollo guiado por especificaciones

> Documento conceptual del curso. Aquí está el **porqué** de la forma en que se
> trabaja este proyecto: primero la especificación, después el código, versión
> por versión.

---

## 🎬 Antes de leer: el video del método

[![Video: Spec Kit de GitHub — el desarrollo guiado por especificaciones está matando al vibe coding](https://img.youtube.com/vi/_MmsQMLg6yU/maxresdefault.jpg)](https://youtu.be/_MmsQMLg6yU)

> **▶️ [Spec Kit de GitHub: cómo el SDD está matando al "vibe coding"](https://youtu.be/_MmsQMLg6yU)**
> — episodio del podcast *BIM Praxis* (~16 min; voces generadas con
> NotebookLM). Cuenta, con otras palabras, EXACTAMENTE el método de este
> repositorio. **Resumen:**
>
> 1. **El "vibe coding" no tiene cimientos:** pedirle a la IA "hazme una
>    app" en dos líneas parece magia las primeras iteraciones, pero a la
>    tercera o cuarta el proyecto colapsa — dependencias circulares,
>    lógica destrozada. La causa técnica es la **degradación del
>    contexto**: el modelo prioriza lo último que usted dijo y pierde la
>    estrategia global.
> 2. **La constitución es el ancla:** un archivo con las leyes
>    innegociables del proyecto que se inyecta en CADA llamada a la IA.
>    Neutraliza el sesgo estadístico del modelo ("a la mínima te quiere
>    meter un React y una base de datos") y bloquea lo prohibido aunque
>    la conversación sea larga.
> 3. **La spec define el QUÉ sin tecnología** (historias de usuario y
>    criterios de aceptación), y la IA no asiente como un ejecutor
>    servicial: busca ambigüedades y casos límite que usted no pensó —
>    se pone la gorra de arquitecto.
> 4. **El plan y las tareas** convierten la spec en arquitectura técnica
>    y en un grafo de dependencias (qué depende de qué, qué puede ir en
>    paralelo), con la disciplina de escribir la prueba ANTES del código.
> 5. **El código pasa a ser un subproducto:** si toda la lógica vive en
>    los `.md`, cambiar de stack es regenerar — lo que vale oro es la
>    especificación. La competencia clave del profesional deja de ser
>    memorizar sintaxis y pasa a ser **claridad de pensamiento
>    estructural**: definir arquitecturas y comunicarse sin ambigüedades.
>
> **La traducción a este repositorio:** la "constitución" del video es
> nuestro `1_constitution.md`; su *specify* es `2_spec.md` (con historias
> y criterios de aceptación); su *plan* es `3_plan.md`; sus *tasks* son
> `8_tasks.md` con las fases verificables. Usted ya está trabajando así.

## 1. El problema que resuelve: el "vibe coding"

Programar con IA (o sin ella) improvisando — pedir "hazme una API", aceptar lo
que salga, parchar sobre lo parchado — produce software que *parece* funcionar
pero nadie puede explicar, verificar ni extender. El síntoma clásico: nadie
sabe decir si el sistema está "terminado", porque nunca se dijo qué debía hacer.

**SDD ataca ese problema invirtiendo el orden: la especificación es el
artefacto principal y el código es su expresión.** Si el código hace algo que
la spec no dice, uno de los dos está mal — y se corrige.

## 2. ¿Qué es SDD (Spec-Driven Development)?

Una práctica de ingeniería (Thoughtworks la señala como una de las prácticas
clave de la era de la IA) con una regla central y una cadena de artefactos:

```
CONSTITUCIÓN  →  ESPECIFICACIÓN  →  PLAN  →  TAREAS  →  CÓDIGO  →  VERIFICACIÓN
(reglas         (QUÉ y para        (CÓMO    (en qué    (el        (¿cumple los
 permanentes)    quién)             técnico) orden)     resultado)  criterios?)
```

- La spec dice **QUÉ**, sin tecnología; el plan dice **CÓMO**; las tareas dicen
  **EN QUÉ ORDEN**, en fases verificables.
- La spec **se versiona con git igual que el código** y siempre refleja el
  estado actual. Si el código cambió y la spec no, hay **deuda de
  especificación** — tan grave como la deuda técnica.
- No es la cascada (waterfall) de vuelta: las specs se escriben **por
  incrementos pequeños** que se implementan y verifican de inmediato, no un
  documentote de 200 páginas antes de la primera línea de código.

## 3. ¿Qué es GitHub Spec Kit?

Un toolkit open source (MIT) de GitHub que **operacionaliza** SDD para
trabajar con agentes de IA: el CLI `specify` crea la estructura y los comandos
guían el flujo:

```
/speckit.constitution → /speckit.specify → /speckit.clarify
→ /speckit.plan → /speckit.tasks → /speckit.implement
```

Sus artefactos (constitution, spec, plan, research, data-model, contracts,
quickstart, tasks) son exactamente los que este curso replica **a mano** con
numeración didáctica (`1_constitution.md` … `8_tasks.md`).

**La distinción para el examen:** SDD es la *metodología* (el "qué hacer");
Spec Kit es una *herramienta* que la implementa (el "con qué"). La misma
relación que hay entre "control de versiones" y "Git". Se puede hacer SDD sin
Spec Kit — este curso lo demuestra — pero no tiene sentido Spec Kit sin SDD.

## 4. Cómo lo aplica ESTE curso (ejemplo resumido)

El proyecto se construye **por versiones**, cada una con su propia spec:

- **Constitución permanente:** [spec_kit/1_constitution.md](spec_kit/1_constitution.md)
  — las reglas que ninguna versión puede violar.
- **Mapa de versiones:** [spec_kit/versiones/0_mapa_versiones.md](spec_kit/versiones/0_mapa_versiones.md)
  — la ruta v1→v6 y las reglas de avance.
- **La versión en curso:** [spec_kit/versiones/v1_producto_mariadb/](spec_kit/versiones/v1_producto_mariadb/2_spec.md)
  — la spec de la v1 ES el documento que se le entrega a la IA (o al
  estudiante) para construirla.

Un fragmento real de la spec de la v1 (note el estilo: verificable, con
criterios medibles):

```markdown
### RF5 — Actualizar parcialmente (PATCH + body parcial)
`PATCH /api/producto/{codigo}` con body parcial (campos opcionales):
solo se modifican los enviados. Devuelve filasAfectadas; inexistente → 404.

## Criterios de aceptación
4. … un `PUT` sin el campo `nombre` responde 422 (reemplazo completo)
   mientras el mismo body en `PATCH` responde 200 (parcial).
```

### 4.1 Los 8 documentos del kit, en una tabla

| # | Documento | Pregunta que responde | Qué encuentra adentro |
|---|---|---|---|
| 1 | `1_constitution.md` | ¿Qué reglas NUNCA se negocian? | Los artículos permanentes (capas, SQL parametrizado con :parametros, español, "un solo comando", cierre por tags). Es UNO para todas las versiones. |
| 2 | `2_spec.md` | ¿QUÉ se construye y cómo se sabe que quedó bien? | Propósito, alcance (incluye / NO incluye), requisitos funcionales y los **criterios de aceptación** medibles que definen "terminada". |
| 3 | `3_plan.md` | ¿CÓMO: stack, estructura, diseño? | Inventario de archivos (nuevos y los que CRECEN), estructura de carpetas y el diseño aterrizado a código. |
| 4 | `4_research.md` | ¿POR QUÉ así y no de otra forma? | Las decisiones numeradas (D1, D2…) con las **alternativas descartadas** — la memoria del proyecto. |
| 5 | `5_data_model.md` | ¿Qué datos hay y qué puede tocar esta versión? | Tablas, llaves y semillas; y la frontera: qué calcula la BD y qué tiene PROHIBIDO escribir la API. |
| 6 | `6_contracts.md` | ¿Cuáles son los endpoints EXACTOS? | Cada endpoint con verbo, URL, body y TODOS sus códigos con el JSON exacto — exigible sin leer el código. |
| 7 | `7_quickstart.md` | ¿Cómo se arranca y se valida rápido? | El arranque en un comando y el **smoke test**: los comandos que recorren los criterios con el valor esperado al lado. |
| 8 | `8_tasks.md` | ¿En qué ORDEN se construye? | Fases verificables, cada una con su "**Verificar:**" — no se avanza con una fase en rojo. |

### 4.2 Los 8 documentos: qué es, para qué sirve y cómo se hace cada uno

**1. `1_constitution.md` — la ley permanente.**
**Qué es:** los artículos innegociables del proyecto; se escribe UNA vez y
rige TODAS las versiones (en Spec Kit lo genera `/speckit.constitution`;
aquí se escribe a mano).
**Para qué sirve:** ancla el proyecto — y a la IA. Cuando alguien (humano o
agente) proponga "metamos tal cosa", la constitución responde ANTES de
discutir; por eso neutraliza el sesgo del modelo hacia lo que más vio en su
entrenamiento.
**Cómo se hace:** liste las decisiones que NO van a cambiar en el semestre
(capas, seguridad, idioma, forma de cerrar versiones); redáctelas como
artículos numerados, cortos y verificables; si un artículo no se puede
violar "por accidente", no necesita estar.

```markdown
## Artículo 3 — SQL siempre parametrizado
Los valores viajan como :parametros de PDO->prepare(); JAMÁS se
concatenan. `"WHERE codigo = '$codigo'"` es inyección esperando turno.
```

**2. `2_spec.md` — el QUÉ.**
**Qué es:** la especificación funcional de UNA versión: propósito, alcance,
requisitos funcionales (RF numerados) y criterios de aceptación.
**Para qué sirve:** define "terminada" de forma MEDIBLE — es el documento
que se le entrega a la IA o al estudiante para construir la versión, y el
que decide si pasó o no.
**Cómo se hace:** propósito en dos frases; RFs numerados SIN tecnología
(qué, no cómo); por cada RF, criterios con valores concretos (cuántas
filas, qué código HTTP, qué mensaje); y un "NO incluye" explícito — frena
la anticipación, que es el vicio favorito de la IA.

```markdown
### RF5 — Actualizar parcialmente (PATCH + body parcial)
PATCH /api/producto/{codigo} con campos opcionales: solo se
modifican los enviados. Inexistente → 404; body vacío → 400.

## Criterios de aceptación
4. Un PUT sin el campo nombre responde 422 (reemplazo completo)
   mientras el MISMO body en PATCH responde 200 (parcial).
```

**3. `3_plan.md` — el CÓMO.**
**Qué es:** la traducción técnica de la spec: stack, inventario de archivos
y diseño de capas ya aterrizado a código.
**Para qué sirve:** que la arquitectura no se decida "sobre la marcha"
mientras se programa; una IA con plan no inventa estructura.
**Cómo se hace:** estructura de carpetas; tabla de archivos NUEVOS con su
papel; tabla de archivos que CRECEN y qué les crece (los intocables también
se declaran); y las decisiones de diseño de la versión — en la familia
diseño, con sus diagramas Mermaid.

```markdown
**Crecen (los únicos existentes que se tocan):**
| Archivo | Qué crece |
|---|---|
| `index.php` | ★ las rutas de la rebanada nueva |
| `servicios/ensamblador.php` | ★ el cableado de la rebanada nueva |
```

**4. `4_research.md` — el PORQUÉ.**
**Qué es:** el registro de decisiones (D1, D2…) con sus alternativas
descartadas — lo que la industria llama ADRs (Architecture Decision
Records).
**Para qué sirve:** memoria del proyecto: no se re-discute lo decidido, y
quien llegue después (incluida la IA) entiende por qué el sistema es así y
no de otra forma.
**Cómo se hace:** por cada decisión: contexto (el problema) → opciones
consideradas (a, b, c) → decisión con su razón → consecuencias que se
aceptan. Se escribe CUANDO se decide, no semanas después.

```markdown
## D4 — ¿Por qué PUT y PATCH separados?
**Alternativas:** (a) un solo endpoint "actualizar" · (b) PUT
(reemplazo completo) y PATCH (parcial) con peticiones distintas.
**Decisión: (b)** — la pareja enseña la semántica HTTP: el MISMO
body da 422 en PUT y 200 en PATCH.
```

**5. `5_data_model.md` — los datos y sus fronteras.**
**Qué es:** las tablas, columnas, llaves y semillas que ESTA versión usa, y
la frontera de responsabilidades entre la API y la BD.
**Para qué sirve:** evita el clásico "la API recalcula lo que la BD ya
calcula" — deja escrito qué columnas tiene PROHIBIDO tocar la API.
**Cómo se hace:** tabla por tabla (columna, tipo, regla); anote qué escribe
la BD sola (defaults, autonuméricos, triggers); semillas con valores
EXACTOS, porque el smoke test depende de ellas.

```markdown
| Tabla | PK | Semilla |
|---|---|---|
| producto | codigo | 8 filas (PR001 "Laptop…", stock 17, …) |

El stock lo mueve el TRIGGER al facturar: la API tiene PROHIBIDO
escribirlo directamente.
```

**6. `6_contracts.md` — el contrato HTTP exacto.**
**Qué es:** endpoint por endpoint: verbo, URL, body de ejemplo y TODOS los
códigos de respuesta con su JSON exacto.
**Para qué sirve:** es lo que un cliente (el front futuro, Postman, el
profesor) puede EXIGIR sin leer el código; al cerrar la versión, estos
contratos se congelan.
**Cómo se hace:** un bloque por endpoint; incluya los desenlaces de ERROR
(404, 422, 500) con su formato — el error también es contrato; los valores
de ejemplo salen de las semillas del 5_data_model.

```markdown
POST /api/producto
body { "codigo": "PR009", "nombre": "Webcam", "stock": 10,
       "valorunitario": 350000 }
→ 200 {estado, mensaje} · 422 si falta un campo o stock < 0 (con
  errores[]) · 500 si el código ya existe (PK duplicada, en detalle)
```

**7. `7_quickstart.md` — la validación en minutos.**
**Qué es:** el arranque en un comando más el smoke test: la lista de
comandos que recorre los criterios de aceptación con el valor esperado al
lado de cada uno.
**Para qué sirve:** "me funciona" deja de ser una opinión — cualquiera
valida la versión en minutos; y en las versiones siguientes se convierte en
la REGRESIÓN (lo viejo debe seguir pasando).
**Cómo se hace:** el comando de arranque; un comando por criterio, en
orden, con el resultado esperado como comentario; y una tabla "Si algo
falla" con las causas probables.

```bash
curl http://localhost:8022/api/producto        # total: 8
curl -i http://localhost:8022/api/producto/PR999   # → 404
```

**8. `8_tasks.md` — el orden, por fases verificables.**
**Qué es:** el plan de construcción dividido en fases, cada una con su
lista de tareas y su compuerta "**Verificar:**".
**Para qué sirve:** convierte el plan en un camino sin saltos: la compuerta
impide avanzar con una fase rota — es la versión artesanal del grafo de
dependencias que `/speckit.tasks` genera.
**Cómo se hace:** ordene de lo que no depende de nada hacia lo que depende
de todo (modelo → repositorio → servicio → controlador); cada fase termina
en un estado COMPROBABLE (`php -l` (sintaxis) y la API respondiendo); la verificación se escribe
como comando concreto, no como "revisar que funcione".

```markdown
## Fase 2 — El modelo y las peticiones
- [ ] modelos/Producto.php (la entidad: 4 propiedades)
- [ ] peticiones/ProductoCrear.php (validación: todo obligatorio)

**Verificar:** `php -l` en cada archivo, sin errores de sintaxis.
```

**La regla que une a los ocho:** si está en la spec y no en el código, el
código está incompleto; si está en el código y no en la spec, sobra — o
falta especificarlo.

**El ciclo de una versión:** leer la spec → seguir las tareas fase por fase →
correr el quickstart → si los criterios pasan, commit + tag (`v1`) → solo
entonces se escribe la spec de la siguiente versión.

## 5. Justificación (por qué molestarse)

1. **Verificabilidad:** "terminado" deja de ser una opinión — son los
   criterios de aceptación pasando.
2. **Comunicación:** la spec es el contrato entre profesor, estudiante e IA;
   los tres leen el mismo documento.
3. **IA dirigida:** un agente con una spec precisa produce código consistente;
   un agente con un prompt vago produce vibe coding con más pasos.
4. **Incrementalidad honesta:** cada versión es pequeña, entregable y deja el
   terreno listo — el crecimiento del sistema queda documentado, no adivinado.

**Advertencia honesta (estado del arte 2026):** SDD no es magia. Specs vagas o
desactualizadas devuelven el vibe coding con burocracia encima; y para cambios
triviales, el ceremonial completo es sobrecarga. El criterio profesional es
saber cuándo la spec paga — en este curso, siempre, porque el objetivo es
aprender la disciplina.

## 6. Referencias (verificadas)

1. GitHub — *Spec-driven development with AI: Get started with a new open
   source toolkit* (blog oficial, sep. 2025):
   <https://github.blog/ai-and-ml/generative-ai/spec-driven-development-with-ai-get-started-with-a-new-open-source-toolkit/>
2. Repositorio oficial `github/spec-kit` (MIT):
   <https://github.com/github/spec-kit>
3. Documentación oficial de Spec Kit: <https://github.github.com/spec-kit/>
4. Thoughtworks — *Spec-driven development: Unpacking one of 2025's key new
   AI-assisted engineering practices*:
   <https://www.thoughtworks.com/en-us/insights/blog/agile-engineering-practices/spec-driven-development-unpacking-2025-new-engineering-practices>
5. Thoughtworks Technology Podcast — *What is spec-driven development?*:
   <https://www.thoughtworks.com/insights/podcasts/technology-podcasts/what-is-spec-driven-development>
6. AWS Kiro — documentación de specs (EARS, requirements/design/tasks):
   <https://kiro.dev/docs/specs/>
7. En este repositorio: la [constitución](spec_kit/1_constitution.md), el
   [mapa de versiones](spec_kit/versiones/0_mapa_versiones.md) y el
   [spec kit de la v1](spec_kit/versiones/v1_producto_mariadb/2_spec.md).
