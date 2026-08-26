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
