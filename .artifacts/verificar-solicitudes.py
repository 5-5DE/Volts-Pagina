"""Prueba HTTP del flujo de solicitudes; limpia únicamente las cuentas temporales que crea."""

import http.cookiejar
from html.parser import HTMLParser
import json
from pathlib import Path
import re
import subprocess
import sys
import urllib.error
import urllib.parse
import urllib.request
import uuid


ROOT = Path(__file__).resolve().parents[1]
BASE = "http://localhost/Proyecto/"
PHP = r"C:\xampp\php\php.exe"
PASSWORD = "Prueba-solicitudes-2026!"
EMAILS = ["prueba-solicitudes-" + uuid.uuid4().hex + "@example.com" for _ in range(2)]
CODIGO_PRUEBA = "prueba-catalogo-" + uuid.uuid4().hex
checks = 0
sys.stdout.reconfigure(encoding="utf-8")


class Inputs(HTMLParser):
    def __init__(self, body):
        super().__init__()
        self.values = {}
        self.feed(body)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "input" and attrs.get("name"):
            self.values[attrs["name"]] = attrs.get("value", "")


def check(condition, message):
    global checks
    assert condition, message
    checks += 1
    print("OK:", message)


def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def request(browser, path, data=None):
    encoded = urllib.parse.urlencode(data).encode() if data is not None else None
    with browser.open(BASE + path, data=encoded, timeout=10) as response:
        body = response.read().decode("utf-8")
        assert response.status == 200
        assert not re.search(r"(?:Fatal error:|Warning:|Parse error:)", body), body[:300]
        return response.url, body


def database(action, email):
    code = r'''
    require 'config/conexion.php';
    $correo = $argv[2];
    if (!preg_match('/^prueba-solicitudes-[a-f0-9]{32}@example[.]com$/D', $correo)) { exit(2); }
    $codigo = $argv[3];
    if (!preg_match('/^prueba-catalogo-[a-f0-9]{32}$/D', $codigo)) { exit(2); }
    if ($argv[1] === 'catalogo') {
        echo json_encode($conexion->query('SELECT id, codigo, nombre, precio, activo FROM paquetes ORDER BY orden, id')->fetchAll());
        exit;
    }
    if ($argv[1] === 'crear_paquete') {
        $q = $conexion->prepare('INSERT INTO paquetes (codigo, nombre, descripcion, incluye, precio, orden) VALUES (?, ?, ?, ?, ?, ?)');
        $q->execute([$codigo, 'Paquete temporal de prueba', 'Descripción temporal para verificar el catálogo.', 'Incluye una prueba local.', '12.34', 65535]);
        echo json_encode(['id' => $conexion->lastInsertId()]);
        exit;
    }
    if ($argv[1] === 'actualizar_paquete') {
        $q = $conexion->prepare('UPDATE paquetes SET nombre = ?, precio = ? WHERE codigo = ?');
        $q->execute(['Paquete temporal actualizado', '56.78', $codigo]);
        echo json_encode(['modificados' => $q->rowCount()]);
        exit;
    }
    if ($argv[1] === 'desactivar_paquete') {
        $q = $conexion->prepare('UPDATE paquetes SET activo = 0 WHERE codigo = ?');
        $q->execute([$codigo]);
        echo json_encode(['modificados' => $q->rowCount()]);
        exit;
    }
    if ($argv[1] === 'comprobar_relacion') {
        try {
            $q = $conexion->prepare('DELETE FROM paquetes WHERE codigo = ?');
            $q->execute([$codigo]);
            echo json_encode(['bloqueado' => false]);
        } catch (PDOException $e) {
            echo json_encode(['bloqueado' => ($e->errorInfo[1] ?? null) === 1451]);
        }
        exit;
    }
    if ($argv[1] === 'cleanup') {
        $conexion->beginTransaction();
        $q = $conexion->prepare('DELETE sp FROM solicitud_paquetes sp JOIN solicitudes s ON s.id = sp.solicitud_id JOIN usuarios u ON u.id = s.usuario_id WHERE u.correo = ?');
        $q->execute([$correo]);
        $q = $conexion->prepare('DELETE s FROM solicitudes s JOIN usuarios u ON u.id = s.usuario_id WHERE u.correo = ?');
        $q->execute([$correo]);
        $q = $conexion->prepare('DELETE FROM usuarios WHERE correo = ?');
        $q->execute([$correo]);
        $usuariosEliminados = $q->rowCount();
        $q = $conexion->prepare('DELETE FROM paquetes WHERE codigo = ?');
        $q->execute([$codigo]);
        $conexion->commit();
        echo json_encode(['usuarios_eliminados' => $usuariosEliminados]);
    } else {
        $q = $conexion->prepare('SELECT id, correo, nombre, telefono FROM usuarios WHERE correo = ?');
        $q->execute([$correo]);
        $usuario = $q->fetch();
        $q = $conexion->prepare('SELECT s.* FROM solicitudes s JOIN usuarios u ON u.id = s.usuario_id WHERE u.correo = ? ORDER BY s.id');
        $q->execute([$correo]);
        $solicitudes = $q->fetchAll();
        $q = $conexion->prepare('SELECT sp.* FROM solicitud_paquetes sp JOIN solicitudes s ON s.id = sp.solicitud_id JOIN usuarios u ON u.id = s.usuario_id WHERE u.correo = ? ORDER BY sp.codigo_paquete');
        $q->execute([$correo]);
        echo json_encode(['usuario' => $usuario, 'solicitudes' => $solicitudes, 'paquetes' => $q->fetchAll()]);
    }
    '''
    result = subprocess.run([PHP, "-r", code, action, email, CODIGO_PRUEBA], cwd=ROOT, capture_output=True, encoding="utf-8")
    if result.returncode:
        raise RuntimeError(result.stderr or result.stdout)
    return json.loads(result.stdout)


def register_and_login(browser, email):
    request(browser, "paginas/login.php?continuar=paquetes")
    _, body = request(browser, "paginas/registro.php")
    request(browser, "acciones/registro.php", dict(
        correo=email, password=PASSWORD, confirmar_password=PASSWORD,
        csrf_token=Inputs(body).values["csrf_token"],
    ))
    _, body = request(browser, "paginas/login.php")
    return request(browser, "acciones/auth.php", dict(
        correo=email, password=PASSWORD, csrf_token=Inputs(body).values["csrf_token"],
    ))


try:
    catalogo = {p["codigo"]: p for p in database("catalogo", EMAILS[0])}
    check(all(c in catalogo for c in ("contactos", "iluminacion", "revision")),
          "Los tres paquetes establecidos están en la base de datos")
    guest = client()
    _, body = request(guest, "paginas/paquetes.php")
    check("Iniciar sesión para continuar" in body and 'id="formulario-contacto"' not in body,
          "Un visitante puede ver paquetes y debe iniciar sesión para enviar")
    url, _ = request(guest, "acciones/solicitud.php", {"paquetes": '{"contactos":1}'})
    check("paginas/login.php" in url, "El servidor rechaza solicitudes sin sesión")
    try:
        request(guest, "scripts/migrar-solicitudes.php")
        raise AssertionError("La migración no debe ejecutarse desde la web")
    except urllib.error.HTTPError as error:
        check(error.code == 404, "La migración solo se puede ejecutar desde la terminal")

    browser = client()
    url, body = register_and_login(browser, EMAILS[0])
    check("paginas/paquetes.php" in url, "El registro y acceso regresan a la solicitud pendiente")
    fields = Inputs(body).values
    check("nombre" in fields and "telefono" in fields and fields.get("correo") == EMAILS[0]
          and re.search(r'<input id="correo-cuenta"[^>]*\breadonly\b', body),
          "La primera solicitud pide nombre y teléfono y muestra el correo de la cuenta como solo lectura")
    data = dict(
        csrf_token=fields["csrf_token"], clave_envio=fields["clave_envio"],
        nombre="Cliente de prueba <Volts>", telefono="614 123 4567",
        zona="Zona de prueba", descripcion="Instalar dos contactos y una lámpara de prueba.",
        servicio="Paquetes de trabajo", paquetes='{"contactos":2,"iluminacion":1}',
    )

    _, body = request(browser, "acciones/solicitud.php", {**data, "csrf_token": "invalido"})
    check("formulario venció" in body and data["descripcion"] in body,
          "Un token inválido conserva los datos y muestra un error")
    check(not database("inspect", EMAILS[0])["solicitudes"], "El envío rechazado no guarda solicitudes")

    for changes, error_text in [
        ({"nombre": "X"}, "entre 2 y 100"),
        ({"telefono": "12345"}, "10 a 15 dígitos"),
        ({"zona": "ab"}, "entre 3 y 150"),
        ({"descripcion": "corta"}, "entre 10 y 1000"),
        ({"paquetes": '{"contactos":11}'}, "máximo de 10"),
        ({"paquetes": '{"inventado":1}'}, "paquetes válidos"),
        ({"paquetes": '{"contactos":1.5}'}, "paquetes válidos"),
        ({"paquetes": '{"contactos":true}'}, "paquetes válidos"),
        ({"paquetes": "[]"}, "Vuelve a elegir"),
        ({"paquetes": "{"}, "Vuelve a elegir"),
        ({"paquetes": "{}"}, "Agrega al menos un paquete"),
        ({"paquetes": "{}", "servicio": "Inventado"}, "servicio válido"),
        ({"clave_envio": "invalida"}, "El formulario venció"),
    ]:
        _, body = request(browser, "acciones/solicitud.php", {**data, **changes})
        check(error_text in body, "Valida el servidor: " + error_text)
    stored = database("inspect", EMAILS[0])
    check(not stored["solicitudes"] and stored["usuario"]["nombre"] is None,
          "Los errores no crean solicitudes ni completan parcialmente el perfil")

    forged = {**data, "usuario_id": "99999999", "correo": "otro@example.com",
              "total_estimado": "0.01", "precio_unitario": "0.01", "estado": "terminada"}
    _, body = request(browser, "acciones/solicitud.php", forged)
    check('id="solicitud-confirmada"' in body and "VOL-" in body, "El envío correcto muestra su folio")
    stored = database("inspect", EMAILS[0])
    check(len(stored["solicitudes"]) == 1 and len(stored["paquetes"]) == 2,
          "Guarda una solicitud con sus dos paquetes")
    check(all(p["paquete_id"] == catalogo[p["codigo_paquete"]]["id"] for p in stored["paquetes"]),
          "Cada paquete solicitado está relacionado con el catálogo por su ID")
    first = stored["solicitudes"][0]
    check(first["usuario_id"] == stored["usuario"]["id"] and first["estado"] == "pendiente",
          "La cuenta y el estado los determina el servidor")
    check(first["total_estimado"] == "1150.00" and
          [(p["codigo_paquete"], p["cantidad"], p["precio_unitario"]) for p in stored["paquetes"]]
          == [("contactos", 2, "350.00"), ("iluminacion", 1, "450.00")],
          "Recalcula precios desde el catálogo y conserva precios y cantidades")
    check(stored["usuario"]["nombre"] == data["nombre"] and stored["usuario"]["telefono"] == data["telefono"],
          "El nombre y teléfono quedan guardados en el perfil")
    check("nombre" not in Inputs(body).values and "telefono" not in Inputs(body).values
          and "&lt;Volts&gt;" in body and "Cliente de prueba <Volts>" not in body,
          "Los siguientes formularios reutilizan el perfil y escapan los datos")

    _, body = request(browser, "acciones/solicitud.php", forged)
    check('id="solicitud-confirmada"' in body and len(database("inspect", EMAILS[0])["solicitudes"]) == 1,
          "Reenviar la misma solicitud devuelve su folio y evita duplicados")
    _, body = request(browser, "paginas/paquetes.php")
    check(len(database("inspect", EMAILS[0])["solicitudes"]) == 1, "Actualizar la página no repite el envío")
    fields = Inputs(body).values
    second = dict(csrf_token=fields["csrf_token"], clave_envio=fields["clave_envio"],
                  zona="Otra zona de prueba", descripcion="Revisar el cableado de la instalación.",
                  servicio="Mantenimiento", paquetes="{}",
                  nombre="Nombre que no debe reemplazar el perfil", telefono="123")
    _, body = request(browser, "acciones/solicitud.php", second)
    stored = database("inspect", EMAILS[0])
    check(len(stored["solicitudes"]) == 2 and stored["solicitudes"][1]["tipo_servicio"] == "Mantenimiento"
          and stored["solicitudes"][1]["total_estimado"] == "0.00",
          "Permite otro trabajo sin paquetes usando el perfil ya guardado")
    check(stored["usuario"]["nombre"] == data["nombre"] and stored["usuario"]["telefono"] == data["telefono"],
          "Un envío posterior no sobrescribe los datos de contacto existentes")
    check("El costo del trabajo se confirmará" in body, "Un trabajo sin paquete queda pendiente de cotización")

    other = client()
    _, body = register_and_login(other, EMAILS[1])
    fields = Inputs(body).values
    _, body = request(other, "acciones/solicitud.php", {
        **data, "csrf_token": fields["csrf_token"], "usuario_id": stored["usuario"]["id"],
    })
    other_stored = database("inspect", EMAILS[1])
    check(len(other_stored["solicitudes"]) == 1
          and other_stored["solicitudes"][0]["usuario_id"] == other_stored["usuario"]["id"]
          and len(database("inspect", EMAILS[0])["solicitudes"]) == 2,
          "Cada cuenta guarda sus propias solicitudes aunque se manipule usuario_id")

    paquete_prueba = database("crear_paquete", EMAILS[0])
    _, body = request(browser, "paginas/paquetes.php")
    check(f'data-paquete-id="{CODIGO_PRUEBA}"' in body and 'data-paquete-precio="12.34"' in body,
          "Un paquete nuevo aparece desde la base de datos sin editar PHP")
    fields = Inputs(body).values
    temporal = {**data, "csrf_token": fields["csrf_token"], "clave_envio": fields["clave_envio"],
                "paquetes": json.dumps({CODIGO_PRUEBA: 2})}
    request(browser, "acciones/solicitud.php", temporal)
    stored = database("inspect", EMAILS[0])
    historico = [p for p in stored["paquetes"] if p["codigo_paquete"] == CODIGO_PRUEBA]
    check(len(historico) == 1 and str(historico[0]["paquete_id"]) == str(paquete_prueba["id"])
          and historico[0]["precio_unitario"] == "12.34"
          and stored["solicitudes"][-1]["total_estimado"] == "24.68",
          "Una solicitud usa el precio e ID del paquete nuevo")

    database("actualizar_paquete", EMAILS[0])
    _, body = request(browser, "paginas/paquetes.php")
    check("Paquete temporal actualizado" in body and 'data-paquete-precio="56.78"' in body,
          "Cambiar el nombre y precio en la base se refleja en la página")
    stored = database("inspect", EMAILS[0])
    historico = [p for p in stored["paquetes"] if p["codigo_paquete"] == CODIGO_PRUEBA][0]
    check(historico["nombre_paquete"] == "Paquete temporal de prueba"
          and historico["precio_unitario"] == "12.34"
          and stored["solicitudes"][-1]["total_estimado"] == "24.68",
          "Los cambios del catálogo conservan el nombre y precio de las solicitudes anteriores")
    fields = Inputs(body).values
    request(browser, "acciones/solicitud.php", {**temporal, "clave_envio": fields["clave_envio"]})
    stored = database("inspect", EMAILS[0])
    check(len(stored["solicitudes"]) == 4 and stored["solicitudes"][-1]["total_estimado"] == "113.56",
          "Las solicitudes nuevas utilizan el precio actualizado")

    migration = subprocess.run([PHP, "scripts/migrar-solicitudes.php"], cwd=ROOT, capture_output=True, encoding="utf-8")
    assert migration.returncode == 0, migration.stderr
    catalogo_despues = {p["codigo"]: p for p in database("catalogo", EMAILS[0])}
    check(all(catalogo_despues[c] == p for c, p in catalogo.items())
          and catalogo_despues[CODIGO_PRUEBA]["precio"] == "56.78",
          "Repetir la migración conserva el catálogo sin duplicar ni sobrescribir datos")

    database("desactivar_paquete", EMAILS[0])
    _, body = request(browser, "paginas/paquetes.php")
    check(f'data-paquete-id="{CODIGO_PRUEBA}"' not in body,
          "Un paquete desactivado deja de aparecer en la página")
    fields = Inputs(body).values
    _, body = request(browser, "acciones/solicitud.php", {**temporal, "clave_envio": fields["clave_envio"]})
    check("paquetes válidos" in body and len(database("inspect", EMAILS[0])["solicitudes"]) == 4,
          "El servidor rechaza selecciones antiguas de un paquete desactivado")
    _, body = request(browser, "acciones/solicitud.php", temporal)
    check('id="solicitud-confirmada"' in body and len(database("inspect", EMAILS[0])["solicitudes"]) == 4,
          "Un reintento de una solicitud ya guardada conserva su folio aunque el paquete se desactive")
    check(database("comprobar_relacion", EMAILS[0])["bloqueado"],
          "La relación de la base impide borrar un paquete que tiene solicitudes")

    print(f"Resultado: {checks} comprobaciones correctas.")
finally:
    for email in EMAILS:
        result = database("cleanup", email)
        print("Cuenta temporal eliminada:", result["usuarios_eliminados"])
