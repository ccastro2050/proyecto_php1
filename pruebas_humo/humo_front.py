# -*- coding: utf-8 -*-
"""Prueba de humo del FRONT (PHP, puerto 8020).

QUÉ COMPRUEBA, Y POR QUÉ AQUÍ SE PUEDE COMPROBAR TODO
=====================================================

Este front está hecho de formularios HTML corrientes: cada botón manda un POST
que un guion puede enviar igual que lo manda el navegador. Por eso esta prueba
llega **hasta el final** —crea, guarda de las dos maneras y elimina—, cosa que
en los fronts de Blazor de otros cursos no se puede hacer, porque allí los
clics viajan por una conexión persistente y no son peticiones sueltas.

O sea: la tecnología más sencilla resultó ser la más fácil de probar sin
manos. Vale la pena verlo, porque casi siempre se cuenta al revés.

Lo que se comprueba:

  1. cada pantalla responde por su DIRECCIÓN PROPIA;
  2. el menú lleva a esas direcciones, y ninguna tiene el nombre de la tabla
     como parámetro;
  3. lo que la pantalla muestra es lo que la API devolvió;
  4. la pantalla no le habla al usuario en jerga;
  5. el recorrido completo: agregar, guardar de las dos maneras y eliminar, y
     la diferencia entre los dos botones se ve en el RESULTADO;
  6. y lo que demuestra que son dos procesos: **con la API apagada la pantalla
     sigue en pie**, con su aviso adentro y sin un solo dato.

Uso:  python pruebas_humo/humo_front.py     (parado en la raíz del proyecto)
"""
import html
import http.cookiejar
import json
import random
import re
import string
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request

FRONT = "http://localhost:8020"
API = "http://localhost:8022"
fallos = []

# Las columnas que la tabla debe traer, en el idioma del usuario.
ETIQUETAS = ("Código", "Nombre", "Stock", "Valor unitario")

# Un código distinto en cada corrida. El borrado de esta versión es físico,
# así que repetir el mismo código funcionaría… mientras todas las corridas
# terminen bien. La que se interrumpe a la mitad deja la ficha puesta, y la
# siguiente falla al crearla por llave duplicada: un rojo que no tiene nada
# que ver con lo que se estaba probando. Un sufijo nuevo cada vez evita que
# una corrida rota contamine la siguiente.
SUFIJO = "".join(random.choices(string.digits, k=4))
CODIGO = "HU" + SUFIJO

# Una sesión con galletas: el front deja los avisos en la sesión de PHP, y sin
# guardar la galleta no se vería ninguno.
navegador = urllib.request.build_opener(
    urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def ver(url):
    """Un GET, como el que hace el navegador al escribir la dirección."""
    try:
        with navegador.open(url, timeout=20) as r:
            return r.status, r.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode("utf-8", "replace")
    except Exception as e:
        return 0, str(e)


def enviar(ruta, campos):
    """Un POST de formulario: exactamente lo que manda el botón.

    Fíjese en el tipo de contenido: `x-www-form-urlencoded`, que es como
    hablan los formularios. El JSON lo pone el front cuando le habla a la API
    —eso pasa del otro lado del contenedor—, y el usuario nunca lo escribe.
    """
    datos = urllib.parse.urlencode(campos).encode()
    peticion = urllib.request.Request(FRONT + ruta, data=datos)
    try:
        with navegador.open(peticion, timeout=20) as r:
            return r.status, r.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode("utf-8", "replace")
    except Exception as e:
        return 0, str(e)


def visible(pagina):
    """El texto que el usuario VE: sin etiquetas y con las tildes de verdad.

    Comprobar sobre el HTML crudo da falsos positivos de los dos lados: un
    «500» puede estar dentro de un precio, y el aviso «no está disponible»
    llega escrito `est&#xE1;`, así que buscar la «á» literal no lo encuentra.
    Una prueba de pantalla comprueba **lo que se ve**, no el código fuente de
    la página.
    """
    sin_script = re.sub(r"(?is)<(script|style)[^>]*>.*?</\1>", " ", pagina)
    sin_etiquetas = re.sub(r"<[^>]*>", " ", sin_script)
    return re.sub(r"\s+", " ", html.unescape(sin_etiquetas))


def revisar(nombre, condicion, detalle=""):
    marca = "[OK]    " if condicion else "[FALLO] "
    print(marca + nombre + " " + detalle[:140])
    if not condicion:
        fallos.append(nombre)


def esperar_api(segundos=180):
    """Espera a que la API responda antes de comprobar nada contra ella.

    Acepta 200 y **204**: un 204 es la API respondiendo que la tabla está
    vacía, y eso es una respuesta válida, no una API a medio arrancar.
    Hace falta porque este mismo guion la apaga y la enciende en la sección 6.
    """
    for _ in range(segundos // 3):
        if ver(API + "/api/producto?limite=1")[0] in (200, 204):
            return True
        time.sleep(3)
    return False


if not esperar_api():
    print("La API no respondió. ¿Está levantado el sistema?")
    print("   docker compose up -d --build")
    raise SystemExit(1)

print("=== 1. Las pantallas responden, cada una por su dirección ===")
for ruta, titulo in [("/", "Facturas"),
                     ("/productos", "Productos"),
                     ("/productos/nuevo", "Agregar un producto")]:
    c, t = ver(FRONT + ruta)
    revisar(ruta.ljust(22) + " responde y dice «" + titulo + "»",
            c == 200 and titulo in visible(t))

c, t = ver(FRONT + "/pantalla-que-no-existe")
revisar("una dirección inventada da 404, no una página en blanco",
        c == 404 and "no existe" in visible(t))

print()
print("=== 2. El menú lleva a la pantalla, con una dirección de verdad ===")
c, t = ver(FRONT + "/")
revisar("el menú tiene el enlace", 'href="/productos"' in t)
revisar("y NINGUNA dirección tiene el nombre de la tabla como parámetro",
        "{tabla}" not in t and "{catalogo}" not in t and "?tabla=" not in t)

print()
print("=== 3. La pantalla trae los datos que dio la API ===")
c, api = ver(API + "/api/producto?limite=5")
filas = json.loads(api)["datos"] if c == 200 else []
c, t = ver(FRONT + "/productos")
if not filas:
    print("[--]    la tabla `producto` está vacía: no hay datos que comparar")
    revisar("y aun vacía, la pantalla responde y ofrece «Agregar»",
            "Agregar" in visible(t))
else:
    revisar("la API responde", True, str(len(filas)) + " filas")
    revisar("y esos mismos datos se ven en la pantalla",
            all(str(f["codigo"]) in visible(t) for f in filas[:3]))
    revisar("la tabla trae sus columnas",
            all(x in visible(t) for x in ETIQUETAS))

print()
print("=== 4. Lo que la pantalla NO debe decirle al usuario ===")
# La jerga se busca como TOKEN TÉCNICO, no como palabra suelta: «producto» es
# el nombre de la tabla Y una palabra que el usuario dice todos los días.
# Jerga de verdad es la RUTA de la API, los verbos y los nombres de motores.
JERGA = ["PUT", "PATCH", "DELETE", "/api/", "PDO", "SELECT",
         "endpoint", "localhost:", "MariaDB"]
for ruta in ("/", "/productos", "/productos/nuevo"):
    c, t = ver(FRONT + ruta)
    visto = [j for j in JERGA if j in visible(t)]
    revisar(ruta.ljust(22) + " sin jerga", not visto, str(visto))

print()
print("=== 5. EL RECORRIDO COMPLETO, botón por botón ===")

# --- Agregar ---
c, t = enviar("/productos/nuevo", {
    "codigo": CODIGO, "nombre": "Producto de humo " + SUFIJO,
    "stock": "7", "valorunitario": "1500.50"})
revisar("agregar " + CODIGO, "Se agregó" in visible(t))
revisar("  y ya aparece en el listado", CODIGO in visible(t))

# --- Guardar la ficha completa ---
c, t = enviar("/productos/" + CODIGO + "/editar", {
    "verbo": "completa", "nombre": "Renombrado " + SUFIJO,
    "stock": "9", "valorunitario": "1600"})
revisar("«Guardar la ficha completa» guarda", "Se guardó" in visible(t))
revisar("  y el nombre nuevo se ve", "Renombrado " + SUFIJO in visible(t))

# --- La ficha completa con un dato en blanco: se rechaza ---
c, t = enviar("/productos/" + CODIGO + "/editar", {
    "verbo": "completa", "nombre": "", "stock": "9", "valorunitario": "1600"})
revisar("la ficha completa con el nombre en blanco se RECHAZA",
        "Se guardó" not in visible(t))
revisar("  y el aviso lo dice sin números de estado ni jerga",
        not any(j in visible(t) for j in ("422", "PUT", "/api/")))

# --- Solo lo que cambié: el MISMO formulario a medio llenar ---
c, t = enviar("/productos/" + CODIGO + "/editar", {
    "verbo": "parcial", "nombre": "", "stock": "42", "valorunitario": ""})
revisar("«Guardar solo lo que cambié», con lo demás en blanco, SÍ guarda",
        "Se guardó" in visible(t))

# Y aquí está la lección, comprobada contra los datos: cambió el stock y el
# nombre se quedó como estaba. Lo que no se envía, no se toca.
c, api = ver(API + "/api/producto/" + CODIGO)
ficha = json.loads(api) if c == 200 else {}
revisar("  el stock cambió a 42", str(ficha.get("stock")) == "42",
        "stock=" + str(ficha.get("stock")))
revisar("  y el nombre NO se borró: lo que no se envía, no se toca",
        ficha.get("nombre") == "Renombrado " + SUFIJO,
        "nombre=" + repr(ficha.get("nombre")))

# --- Eliminar ---
c, t = enviar("/productos/" + CODIGO + "/eliminar", {})
revisar("eliminar", "Se eliminó" in visible(t))

# Y aquí un tropiezo que valió la pena, porque enseña a leer una prueba en
# rojo. Comprobar «el código ya no está en la página» JUSTO DESPUÉS de
# eliminar sale en rojo con el sistema funcionando perfectamente: el aviso
# dice «Se eliminó el producto HU4325», o sea que **el código sigue en la
# página, dentro del aviso**. Lo que estaba mal era la prueba.
#
# Se pide la pantalla otra vez. En esa segunda visita el aviso ya no está
# —se muestra una sola vez—, así que si el código apareciera sería en la
# tabla de verdad. Dos comprobaciones por el precio de una.
c, t = ver(FRONT + "/productos")
revisar("  el aviso se muestra UNA vez y no se repite",
        "Se eliminó" not in visible(t))
revisar("  y la ficha ya no está en el listado", CODIGO not in visible(t))

print()
print("=== 6. LA PRUEBA DE LOS DOS PROCESOS: se apaga la API ===")
print("    (esto tarda unos segundos)")
subprocess.run(["docker", "compose", "stop", "api-facturas"],
               capture_output=True, text=True)
time.sleep(3)

c, t = ver(FRONT + "/productos")
revisar("la pantalla SIGUE respondiendo con la API apagada", c == 200)
revisar("  y muestra el aviso dentro de la aplicación",
        "no está disponible" in visible(t))
revisar("  con su menú y su marco intactos",
        "Productos" in visible(t) and "Inicio" in visible(t))
# Ésta es LA comprobación de la constitución: MariaDB sigue encendida y con
# los datos ahí. Si el front pudiera llegar a la base por su cuenta, la tabla
# se seguiría viendo. No se ve. Por eso son dos procesos y no uno partido.
revisar("  y SIN un solo dato: el front no puede llegar a la base solo",
        "PR001" not in visible(t))

subprocess.run(["docker", "compose", "start", "api-facturas"],
               capture_output=True, text=True)
print("    API encendida otra vez; esperando a que responda…")
esperar_api(90)
c, t = ver(FRONT + "/productos")
revisar("y al volver la API, la pantalla vuelve a traer los datos",
        "PR001" in visible(t))

print()
if fallos:
    print("=== RESULTADO: " + str(len(fallos)) + " FALLO(S) ===")
    for f in fallos:
        print("   -", f)
    raise SystemExit(1)

print("=== RESULTADO: TODO EN VERDE ===")
print()
print("Se recorrieron las cuatro operaciones desde la PANTALLA, con los")
print("mismos POST que manda el navegador. Queda para una persona lo que")
print("un guion no ve: que se entienda. Está en 7_quickstart.md.")
