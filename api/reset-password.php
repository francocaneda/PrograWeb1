<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Authorization, X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header('Access-Control-Allow-Methods: POST, GET, PATCH, DELETE, OPTIONS');
header("Allow: GET, POST, PATCH, DELETE");

date_default_timezone_set('America/Argentina/Buenos_Aires');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {    
   return 0;    
}  

spl_autoload_register(
    function ($nombre_clase) {
        include __DIR__.'/'.str_replace('\\', '/', $nombre_clase) . '.php';
    }
);

use \Firebase\JWT\JWT;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

require_once 'config_db.php';
require_once 'config_jwt.php';

// ----------------- ROUTER ------------------

$metodo = strtolower($_SERVER['REQUEST_METHOD']);
$comandos = explode('/', strtolower($_GET['comando'] ?? ''));
$funcionNombre = $metodo.ucfirst($comandos[0] ?? '');

$parametros = array_slice($comandos, 1);
if (count($parametros) > 0 && $metodo == 'get') {
    $funcionNombre = $funcionNombre.'ConParametros';
}

if (function_exists($funcionNombre)) {
    call_user_func_array($funcionNombre, $parametros);
} else {
    header(' ', true, 400);
    echo json_encode(['error' => 'Función no encontrada']);
    die;
}

// ----------------- FUNCIONES DE SOPORTE ------------------

function output($val, $headerStatus = 200)
{
    header(' ', true, $headerStatus);
    header('Content-Type: application/json');
    print json_encode($val);
    die;
}

function outputError($codigo = 500)
{
    switch ($codigo) {
        case 400:
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad request", true, 400);
            die;
        case 401:
            header($_SERVER["SERVER_PROTOCOL"] . " 401 Unauthorized", true, 401);
            die;
        case 404:
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", true, 404);
            die;
        default:
            header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error", true, 500);
            die;
            break;
    }
}

function conectarBD()
{
    $link = mysqli_connect(DBHOST, DBUSER, DBPASS, DBBASE);
    if ($link === false) {
        http_response_code(500);
        die('Error de conexión a BD');
    }
    mysqli_set_charset($link, 'utf8');
    return $link;
}

function outputJson($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ----------------- FUNCIONES API ------------------

// Función para manejar la solicitud POST de restablecimiento de contraseña

function postReset()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        outputJson(['error' => 'Método no permitido'], 405);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';

    if (!$email) {
        outputJson(['error' => 'Email requerido'], 400);
    }

    $link = conectarBD();
    $email_safe = mysqli_real_escape_string($link, $email);

    $sql = "SELECT id, nombre_completo FROM usuarios WHERE email='$email_safe'";
    $res = mysqli_query($link, $sql);
    
    if (!$res || mysqli_num_rows($res) === 0) {
        outputJson(['error' => 'Email no encontrado'], 404);
    }

    
    $user = mysqli_fetch_assoc($res);
    $userId = $user['id'];
    $userName = $user['nombre_completo'];  // capturamos el nombre completo
    $token = bin2hex(random_bytes(16));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $sqlInsert = "INSERT INTO password_resets (user_id, token, expires_at) VALUES ($userId, '$token', '$expiry')";
    mysqli_query($link, $sqlInsert);


    $resetLink = "http://localhost:4200/passwordRecup-page?token=$token";


    // PHPMailer configuración
    $mail = new PHPMailer(true);

    // Aca Realizaremos el envio del email a traves de PHPMailer

    try {
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'germaneros96@gmail.com'; 
        $mail->Password = 'ywdn dqis znmi yxzp';  
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('germaneros96@gmail.com', 'ForoRandomUces');
        $mail->addAddress($email);

        // Cuerpo del email
        $mail->isHTML(true);  // <-- Activar HTML
        $mail->Subject = "Recuperación de contraseña - ForoRandomUces";
        $mail->CharSet = 'UTF-8';
       


$mailBody = <<<EOD
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Recuperación de Contraseña</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
<h2>Recuperación de Contraseña</h2>
<p>Hola, <strong> $userName </strong>,</p>
  <p>Haz clic en el siguiente enlace para recuperar tu contraseña:</p>
  <p style="text-align: center; margin: 30px 0;">
    <a href="$resetLink" style="background-color: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">
      AQUI
    </a>
  </p>
  <p>Este enlace es válido por <strong>1 hora</strong>.</p>
  <p>Si no solicitaste esta acción, ignora este mensaje.</p>
  <hr style="margin-top: 40px; border: none; border-top: 1px solid #ccc;">
  <p style="font-style: italic; color: #666;">El equipo de ForoRandomUces</p>
</body>
</html>
EOD;

        $mail->Body = $mailBody;

        $mail->send();

        outputJson(['message' => 'Email enviado correctamente']);
    } catch (Exception $e) {
        outputJson(['error' => "Error al enviar email: {$mail->ErrorInfo}"], 500);
    }

    mysqli_close($link);
}


// MANEJOS DE CAMBIO DE CONTRASEÑA

function patchUpdatePassword() {
    if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
        outputJson(['error' => 'Método no permitido'], 405);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $token = $data['token'] ?? '';
    $newPassword = $data['newPassword'] ?? '';

    if (!$token || !$newPassword) {
        outputJson(['error' => 'Token y nueva contraseña son requeridos'], 400);
    }

    $db = conectarBD();

    // Escapar entradas
    $token = mysqli_real_escape_string($db, $token);
    $newPassword = mysqli_real_escape_string($db, $newPassword);

    // Buscar token válido y no expirado
    $sql = "SELECT user_id FROM password_resets WHERE token='$token' AND expires_at > NOW()";
    $result = mysqli_query($db, $sql);

    if (!$result || mysqli_num_rows($result) === 0) {
        outputJson(['error' => 'Token inválido o expirado'], 400);
    }

    $row = mysqli_fetch_assoc($result);
    $userId = $row['user_id'];

    // Hashear nueva contraseña
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // Actualizar contraseña
    $sqlUpdate = "UPDATE usuarios SET clave='$hashedPassword' WHERE id=$userId";
    $updateResult = mysqli_query($db, $sqlUpdate);

    if (!$updateResult) {
        outputJson(['error' => 'Error al actualizar contraseña'], 500);
    }

    // Eliminar token para que no se use otra vez
    $sqlDelete = "DELETE FROM password_resets WHERE token='$token'";
    mysqli_query($db, $sqlDelete);

    mysqli_close($db);

    outputJson(['message' => 'Contraseña actualizada correctamente']);
}
