<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Authorization, X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header('Access-Control-Allow-Methods: POST, OPTIONS');
header("Allow: POST, OPTIONS");

date_default_timezone_set('America/Argentina/Buenos_Aires');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


spl_autoload_register(
    function ($nombre_clase) {
        include __DIR__.'/'.str_replace('\\', '/', $nombre_clase) . '.php';
    }
);

require_once 'config_db.php';
require_once 'config_jwt.php';

use \Firebase\JWT\JWT;

// ----------------- ROUTER ------------------

$metodo = strtolower($_SERVER['REQUEST_METHOD']);
$comandos = explode('/', strtolower($_GET['comando']));
$funcionNombre = $metodo.ucfirst($comandos[0]);

$parametros = array_slice($comandos, 1);
if (count($parametros) >0 && $metodo == 'get') {
    $funcionNombre = $funcionNombre.'ConParametros';
}

if (function_exists($funcionNombre)) {
    call_user_func_array($funcionNombre, $parametros);
} else {
    header(' ', true, 400);
}

// Funciones auxiliares para enviar respuesta JSON
function output($val, $headerStatus = 200)
{
    header('Content-Type: application/json');
    http_response_code($headerStatus);
    echo json_encode($val);
    exit();
}

function outputError($codigo = 500)
{
    switch ($codigo) {
        case 400:
            http_response_code(400);
            break;
        case 401:
            http_response_code(401);
            break;
        case 404:
            http_response_code(404);
            break;
        default:
            http_response_code(500);
            break;
    }
    exit();
}

function conectarBD()
{
    $link = mysqli_connect(DBHOST, DBUSER, DBPASS, DBBASE);
    if ($link === false) {
        outputError(500);
    }
    mysqli_set_charset($link, 'utf8');
    return $link;
}

function requiereLogin()
{
    try {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            throw new Exception("Token requerido", 1);
        }
        list($jwt) = sscanf($headers['Authorization'], 'Bearer %s');
        $decoded = JWT::decode($jwt, JWT_KEY, [JWT_ALG]);
    } catch(Exception $e) {
        outputError(401);
    }
    return $decoded;
}

// Función para registrar la visita


function postRegistrarVisita() {
    // Requiere que el usuario esté autenticado y obtiene el payload del JWT
    $payload = requiereLogin();
    $db = conectarBD();
    $id_usuario = intval($payload->uid);

    // Obtiene el id_post del body JSON enviado
    $data = json_decode(file_get_contents("php://input"), true);
    $id_post = intval($data['id_post'] ?? 0);

    if ($id_post === 0) {
        output(['error' => 'Falta el id_post'], 400);
        exit;
    }

    // Inserta o actualiza la visita usando ON DUPLICATE KEY UPDATE
    $sql = "INSERT INTO visitas_post (id_post, id_usuario, fecha_visita) 
            VALUES ($id_post, $id_usuario, CURRENT_TIMESTAMP) 
            ON DUPLICATE KEY UPDATE fecha_visita = CURRENT_TIMESTAMP";

    if (!mysqli_query($db, $sql)) {
        output(['error' => 'Error al registrar visita', 'detalle' => mysqli_error($db)], 500);
    } else {
        output(['mensaje' => 'Visita registrada correctamente']);
    }

    mysqli_close($db);
}



