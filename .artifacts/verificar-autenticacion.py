"""Prueba local del registro y acceso; elimina solo la cuenta temporal que crea."""

import http.cookiejar
import json
from pathlib import Path
import re
import subprocess
import sys
import urllib.parse
import urllib.request
import uuid


ROOT = Path(__file__).resolve().parents[1]
BASE = "http://localhost/Proyecto/"
PHP = r"C:\xampp\php\php.exe"
EMAIL = "prueba-codex-" + uuid.uuid4().hex + "@example.com"
PASSWORD = " Prueba-segura-2026! "
cookies = http.cookiejar.CookieJar()
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies))
checks = 0
sys.stdout.reconfigure(encoding="utf-8")


def check(condition, message):
    global checks
    assert condition, message
    checks += 1
    print("OK:", message)


def request(path, data=None):
    encoded = urllib.parse.urlencode(data).encode() if data is not None else None
    with client.open(BASE + path, data=encoded, timeout=10) as response:
        body = response.read().decode("utf-8")
        assert response.status == 200, response.status
        assert not re.search(r"<(?:b|br)[^>]*>\s*(?:Warning|Fatal error|Notice)", body)
        return response.url, body, response.headers


def token(body):
    match = re.search(r'name="csrf_token" value="([a-f0-9]{64})"', body)
    assert match, "Falta token CSRF"
    return match.group(1)


def database(action):
    # Usa la conexión del proyecto y consultas preparadas, sin imprimir credenciales.
    code = r'''
    require 'config/conexion.php';
    $correo = $argv[2];
    if (!preg_match('/^prueba-codex-[a-f0-9]{32}@example[.]com$/', $correo)) { exit(2); }
    if ($argv[1] === 'cleanup') {
        $q = $conexion->prepare('DELETE FROM usuarios WHERE correo = ?');
        $q->execute([$correo]);
        echo json_encode(['eliminados' => $q->rowCount()]);
    } else {
        $q = $conexion->prepare('SELECT password_hash FROM usuarios WHERE correo = ?');
        $q->execute([$correo]);
        $rows = $q->fetchAll();
        echo json_encode([
            'cantidad' => count($rows),
            'hash_valido' => count($rows) === 1 && password_verify($argv[3], $rows[0]['password_hash']),
            'texto_plano' => count($rows) === 1 && $rows[0]['password_hash'] === $argv[3]
        ]);
    }
    '''
    result = subprocess.run(
        [PHP, "-r", code, action, EMAIL, PASSWORD], cwd=ROOT,
        capture_output=True, encoding="utf-8"
    )
    if result.returncode:
        raise RuntimeError(result.stderr or result.stdout)
    return json.loads(result.stdout)


try:
    _, home, _ = request("index.php")
    check('href="paginas/login.php">Iniciar sesión</a>' in home, "El botón existente abre el acceso")
    _, login, headers = request("paginas/login.php")
    check('¿Aún no tienes cuenta? <a href="registro.php">Regístrate</a>' in login, "El acceso ofrece el enlace al registro")
    check("HttpOnly" in headers.get("Set-Cookie", "") or any(cookie.name == "PHPSESSID" for cookie in cookies), "La sesión usa una cookie")
    _, registration, _ = request("paginas/registro.php")
    csrf = token(registration)
    data = dict(correo=EMAIL, password=PASSWORD, confirmar_password=PASSWORD)

    _, body, _ = request("acciones/registro.php", data)
    check("formulario venció" in body, "Rechaza registro sin token CSRF")
    check(database("inspect")["cantidad"] == 0, "El registro rechazado no escribe en la base")

    data["csrf_token"] = csrf
    for values, expected in [
        ({"correo": "correo-invalido"}, "correo electrónico válido"),
        ({"password": "corta", "confirmar_password": "corta"}, "al menos 8 caracteres"),
        ({"password": "a" * 73, "confirmar_password": "a" * 73}, "demasiado larga"),
        ({"confirmar_password": "Otra-clave-2026"}, "contraseñas no coinciden"),
        ({"password": "clave\x00invalida", "confirmar_password": "clave\x00invalida"}, "caracteres válidos"),
        ({"correo[]": EMAIL, "correo": ""}, "correo electrónico válido"),
    ]:
        _, body, _ = request("acciones/registro.php", {**data, **values})
        check(expected in body, "Valida registro: " + expected)

    url, body, _ = request("acciones/registro.php", {**data, "correo": "  " + EMAIL.upper() + "  "})
    check(url.endswith("paginas/login.php") and "Tu cuenta se creó correctamente" in body, "Registro completo y confirmación en el acceso")
    check(EMAIL in body and PASSWORD not in body, "Conserva el correo y no devuelve la contraseña")
    stored = database("inspect")
    check(stored["cantidad"] == 1 and stored["hash_valido"] and not stored["texto_plano"], "Cuenta guardada una vez con contraseña hasheada")

    _, body, _ = request("acciones/registro.php", data)
    check("Este correo ya está registrado" in body and database("inspect")["cantidad"] == 1, "Bloquea correos duplicados sin crear otra cuenta")

    _, login, _ = request("paginas/login.php")
    credentials = dict(correo=EMAIL, password=PASSWORD, csrf_token=token(login))
    _, body, _ = request("acciones/auth.php", {**credentials, "csrf_token": "invalido"})
    check("formulario venció" in body, "Rechaza acceso con token inválido")
    _, body, _ = request("acciones/auth.php", {**credentials, "password": "Incorrecta-2026"})
    check("Correo o contraseña incorrectos" in body, "Rechaza contraseña incorrecta")
    previous_session = next(cookie.value for cookie in cookies if cookie.name == "PHPSESSID")
    url, body, _ = request("acciones/auth.php", credentials)
    check(url.endswith("index.php") and 'id="panel-perfil"' in body and EMAIL in body and "Cerrar sesión" in body, "Permite entrar y muestra el correo y la salida dentro del perfil")
    check('<details class="menu-perfil">' in body and 'aria-label="Menú de perfil"' in body and "Sesión iniciada" not in body, "El perfil se muestra como un control accesible con el menú cerrado")
    current_session = next(cookie.value for cookie in cookies if cookie.name == "PHPSESSID")
    check(previous_session != current_session, "Renueva la sesión al entrar")

    for page in ("paginas/login.php", "paginas/registro.php"):
        url, body, _ = request(page)
        check(url.endswith("index.php") and 'id="panel-perfil"' in body, "Redirige al inicio si ya hay sesión: " + page)

    _, body, _ = request("acciones/logout.php", {"csrf_token": "invalido"})
    check('id="panel-perfil"' in body, "Un token inválido no cierra la sesión")
    _, body, _ = request("acciones/logout.php")
    check('id="panel-perfil"' in body, "Visitar la URL de salida no cierra la sesión")
    _, body, _ = request("acciones/logout.php", {"csrf_token": token(body)})
    check('id="panel-perfil"' not in body and "Iniciar sesión" in body, "Cierra la sesión y sustituye el perfil por el enlace de acceso")

    for page in ("index.php", "paginas/paquetes.php", "paginas/evidencias.php", "paginas/contacto.php", "paginas/servicios.php"):
        request(page)
    check(True, "Las páginas existentes siguen respondiendo sin errores de PHP")
    print(f"Resultado: {checks} comprobaciones correctas.")
finally:
    cleanup = database("cleanup")
    print("Cuenta temporal eliminada:", cleanup["eliminados"])
