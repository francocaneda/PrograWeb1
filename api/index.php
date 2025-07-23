<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Authorization, X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header('Access-Control-Allow-Methods: POST, GET, PATCH, DELETE, OPTIONS');
header("Allow: GET, POST, PATCH, DELETE");

date_default_timezone_set('America/Argentina/Buenos_Aires');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {    
   http_response_code(200);
   exit();
}




// Resto de tu código...


spl_autoload_register(
    function ($nombre_clase) {
        include __DIR__.'/'.str_replace('\\', '/', $nombre_clase) . '.php';
    }
);



use \Firebase\JWT\JWT;

require_once 'config_db.php';
require_once 'config_jwt.php';

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

// GET
if ($metodo === 'get' && $comandos[0] === 'getnotificaciones') {
    getNotificaciones();
}

// PATCH
if ($metodo === 'patch' && $comandos[0] === 'patchnotificaciones') {
    patchNotificaciones();
}

if ($metodo === 'post' && $comandos[0] === 'updateusuario') {
    postupdateUsuario();
    exit();
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
        outputError(500, "Falló la conexión: " . mysqli_connect_error());
    }
    mysqli_set_charset($link, 'utf8');
    return $link;
}

function autenticar($email, $clave)
{
    $link = conectarBD();
    $email = mysqli_real_escape_string($link, $email);
    $clave = mysqli_real_escape_string($link, $clave);
    $sql = "SELECT id, nombre_completo FROM usuarios WHERE email='$email' AND clave='$clave'";
    $resultado = mysqli_query($link, $sql);
    if ($resultado === false) {
        outputError(500, "Falló la consulta: " . mysqli_error($link));
    }

    $ret = false;    
    if ($fila = mysqli_fetch_assoc($resultado)) {
        $ret = [
            'id'     => $fila['id'],
            'nombre' => $fila['nombre_completo'],
        ];
    }
    mysqli_free_result($resultado);
    mysqli_close($link);
    return $ret;
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

// ----------------- API ------------------

function getPrivado()
{
    $payload = requiereLogin();
    output(['data' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.']);
}

function getSysinfo()
{
    output(['info' => 'Información del sistema.']);
}


function getPerfil()
{
    $payload = requiereLogin();
    output(['id' => $payload->uid, 'nombre' => $payload->nombre]);
}

function postUploadavatar()
{
    file_put_contents('debug_upload.txt', json_encode($_FILES));

    requiereLogin(); // Asegura que el usuario esté autenticado
    file_put_contents('debug_upload.txt', print_r($_FILES, true));


    if (!isset($_FILES['avatar'])) {
        output(['error' => 'No se envió ninguna imagen'], 400);
    }

    $file = $_FILES['avatar'];

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/x-icon'];
    if (!in_array($file['type'], $allowedTypes)) {
        output(['error' => 'Formato no permitido'], 400);
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        output(['error' => 'El archivo es muy grande'], 400);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('avatar_', true) . '.' . $ext;
    $destino = __DIR__ . "/uploads/$fileName";

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        output(['error' => 'Error al guardar el archivo'], 500);
    }

    $rutaFinal = "uploads/$fileName";

    // Guardar en la base de datos
    $payload = requiereLogin();
    $db = conectarBD();
    $id = mysqli_real_escape_string($db, $payload->uid);
    $rutaBD = mysqli_real_escape_string($db, $rutaFinal);

    $sql = "UPDATE usuarios SET avatar = '$rutaBD' WHERE id = $id";
    mysqli_query($db, $sql);
    mysqli_close($db);

    output(['ruta' => $rutaFinal]);
}

////// Update usuario /////






function postLogin()
{
    $db = conectarBD();
    $data = json_decode(file_get_contents("php://input"), true);

    $email = mysqli_real_escape_string($db, $data['email'] ?? '');
    $claveIngresada = $data['clave'] ?? '';

    if (empty($email) || empty($claveIngresada)) {
        outputError(400);
    }

    // Buscar usuario por email, incluir rol en la consulta
    $sql = "SELECT id, nombre_completo, clave, rol FROM usuarios WHERE email='$email'";
    $result = mysqli_query($db, $sql);

    if ($result === false || mysqli_num_rows($result) === 0) {
        outputError(401); // Usuario no encontrado
    }

    $usuario = mysqli_fetch_assoc($result);

    // Verificar la contraseña con password_verify
    if (!password_verify($claveIngresada, $usuario['clave'])) {
        outputError(401); // Contraseña incorrecta
    }

    // Crear JWT con rol
    $payload = [
        'uid' => $usuario['id'],
        'nombre' => $usuario['nombre_completo'],
        'rol' => $usuario['rol'],
        'exp' => time() + JWT_EXP,
    ];

    $jwt = JWT::encode($payload, JWT_KEY, JWT_ALG);

    mysqli_free_result($result);
    mysqli_close($db);

    output(['jwt' => $jwt]);
}








function patchLogin()
{
    $payload = requiereLogin();
    $payload->exp = time() + JWT_EXP;
    $jwt = JWT::encode($payload, JWT_KEY);
    output(['jwt'=>$jwt]);
}


function postUsuarios()
{
    $db = conectarBD();

    // Obtener datos desde $_POST (vienen de FormData)
    $userName = mysqli_real_escape_string($db, $_POST['userName'] ?? '');
    $email = mysqli_real_escape_string($db, $_POST['email'] ?? '');
    $clave = mysqli_real_escape_string($db, $_POST['password'] ?? '');
    $nombre = mysqli_real_escape_string($db, $_POST['nombre'] ?? '');
    $apellido = mysqli_real_escape_string($db, $_POST['apellido'] ?? '');
    $fechaNacimiento = mysqli_real_escape_string($db, $_POST['fechaNacimiento'] ?? null);
    $bio = mysqli_real_escape_string($db, $_POST['bio'] ?? '');
    $rol = mysqli_real_escape_string($db, $_POST['rol'] ?? 'normaluser');

    // Ruta por defecto del avatar
    $avatar = 'assets/Access.ico';

    // Subir archivo si viene uno
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/x-icon'];

        if (in_array($file['type'], $allowedTypes)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('avatar_', true) . '.' . $ext;
            $destino = __DIR__ . "/uploads/$fileName";

            if (move_uploaded_file($file['tmp_name'], $destino)) {
                $avatar = "uploads/$fileName";
            }
        }
    }

    // Validar campos obligatorios
    if (empty($userName) || empty($email) || empty($clave) || empty($nombre) || empty($apellido)) {
        outputError(400);
    }

    if (empty($fechaNacimiento)) {
    output(['error' => 'La fecha de nacimiento es obligatoria'], 400);
}

    // Validar unicidad de email y nombre de usuario
    $sqlCheckUser = "SELECT id FROM usuarios WHERE user_nameweb='$userName'";
    $resultCheckUser = mysqli_query($db, $sqlCheckUser);
    if (mysqli_num_rows($resultCheckUser) > 0) {
        output(['error' => 'El nombre de usuario ya está registrado'], 409);
        return;
    }

    $sqlCheckEmail = "SELECT id FROM usuarios WHERE email='$email'";
    $resultCheckEmail = mysqli_query($db, $sqlCheckEmail);
    if (mysqli_num_rows($resultCheckEmail) > 0) {
        output(['error' => 'El email ya está registrado'], 409);
        return;
    }

    // Insertar nuevo usuario

    // Encriptar antes la contraseña
    $clave = password_hash($clave, PASSWORD_BCRYPT);


$sql = "INSERT INTO usuarios (user_nameweb, email, clave, nombre_completo, avatar, fecha_nacimiento, bio, rol) 
        VALUES ('$userName', '$email', '$clave', CONCAT('$nombre', ' ', '$apellido'), '$avatar', '$fechaNacimiento', '$bio', '$rol')";


    $result = mysqli_query($db, $sql);

    if ($result === false) {
        outputError(500);
    }

    $id = mysqli_insert_id($db);
    mysqli_close($db);

    output(['id' => $id]);
}

///// Update de Usuario

function postupdateUsuario() {
    $db = conectarBD();

    // Tomar los datos enviados
    $id = $_POST['id'] ?? null;
    $userName = $_POST['userName'] ?? '';
    $email = $_POST['email'] ?? '';
    $fechaNacimiento = $_POST['fechaNacimiento'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $avatar = $_FILES['avatar'] ?? null;

    // Validaciones básicas 
    if (empty($userName) || empty($id) || empty($email) || empty($fechaNacimiento)) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan datos obligatorios']);
        exit();
    }

    // Manejo del avatar (si se subió archivo)
    $avatarPath = null;
    if ($avatar && $avatar['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/';
        $fileName = basename($avatar['name']);
        $targetFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($avatar['tmp_name'], $targetFilePath)) {
            $avatarPath = 'uploads/' . $fileName;  // Ruta relativa para BD y frontend
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al subir el avatar']);
            exit();
        }
    }

    // Escapar variables
    $idEscaped = mysqli_real_escape_string($db, $id);
    $userNameEscaped = mysqli_real_escape_string($db, $userName);
    $emailEscaped = mysqli_real_escape_string($db, $email);
    $fechaNacimientoEscaped = mysqli_real_escape_string($db, $fechaNacimiento);
    $bioEscaped = mysqli_real_escape_string($db, $bio);

    // Preparar consulta SQL con user_nameweb incluido
    $sql = "UPDATE usuarios SET 
        user_nameweb = '$userNameEscaped',
        email = '$emailEscaped',
        fecha_nacimiento = '$fechaNacimientoEscaped',
        bio = '$bioEscaped'";

    if ($avatarPath !== null) {
        $avatarPathEscaped = mysqli_real_escape_string($db, $avatarPath);
        $sql .= ", avatar = '$avatarPathEscaped'";
    }

    $sql .= " WHERE id = '$idEscaped'";

    if (mysqli_query($db, $sql)) {
        mysqli_close($db);
        echo json_encode(['ok' => true]);
        exit();
    } else {
        $error = mysqli_error($db);
        mysqli_close($db);
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar usuario: ' . $error]);
        exit();
    }
}





// Obtener usuarios

function getUsuarios()
{
    $db = conectarBD();
    $sql = "SELECT id, user_nameweb, email, nombre_completo, avatar, fecha_nacimiento, bio, rol, fecha_registro FROM usuarios";
    $result = mysqli_query($db, $sql);

    if ($result === false) {
        outputError(500);
    }



    $usuarios = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $usuarios[] = $row;

    }
    mysqli_free_result($result);
    mysqli_close($db);

    output(['usuarios' => $usuarios]);
}

//Obtener el usuario que esta logueado

function getUsuariologueado()
{
    $payload = requiereLogin();
    $db = conectarBD();

    // Obtener usuario por ID desde el payload
    $idUsuario = mysqli_real_escape_string($db, $payload->uid);
    $sql = "SELECT id, user_nameweb, email, nombre_completo, avatar, fecha_nacimiento, bio, rol, fecha_registro FROM usuarios WHERE id = '$idUsuario'";
    $result = mysqli_query($db, $sql);

    if (!$result || mysqli_num_rows($result) === 0) {
        outputError(404); 
    }

    $usuario = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    mysqli_close($db);

    $partes = explode(' ', trim($usuario['nombre_completo']));
    $apellido = array_pop($partes); // última palabra = apellido
    $nombre = implode(' ', $partes); // el resto = nombre

    $usuarioAuth = [
        'id' => $usuario['id'],
        'user_nameweb' => $usuario['user_nameweb'],
        'email' => $usuario['email'],
        'nombre' => $nombre,
        'apellido' => $apellido,
        'avatar' => $usuario['avatar'],
        'fecha_nacimiento' => $usuario['fecha_nacimiento'],
        'bio' => $usuario['bio'],
        'rol' => $usuario['rol'],
        'fecha_registro' => $usuario['fecha_registro'],
    ];

    output(['usuario' => $usuarioAuth]);
}

// Traer Categorias 

// Traer Categorias con cantidad de posts
function getCategorias()
{
    $db = conectarBD();

    $sql = "
        SELECT 
            c.id_categoria,
            c.nombre_categoria,
            COUNT(DISTINCT p.id_post) AS cantidad_posts,
            COUNT(co.id_comentario) AS cantidad_comentarios
        FROM categorias c
        LEFT JOIN posts p ON c.id_categoria = p.id_categoria
        LEFT JOIN comentarios co ON p.id_post = co.id_post
        GROUP BY c.id_categoria
    ";

    $result = mysqli_query($db, $sql);

    if ($result === false) {
        outputError(500);
    }

    $categorias = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categorias[] = $row;
    }

    mysqli_free_result($result);
    mysqli_close($db);

    output(['categorias' => $categorias]);
}


// getPostsConParametros (get categorias con id)

function getPostsConParametros($id_categoria)
{
    error_log("getPostsConParametros llamado con id_categoria=$id_categoria");
    //$payload = requiereLogin(); 
    $db = conectarBD();
    $id_categoria = intval($id_categoria);

    // Consulta básica para obtener posts de la categoría
    $sql = "SELECT 
                p.id_post,
                p.titulo,
                p.contenido,
                p.id_usuario,
                p.id_categoria,
                p.fecha_creacion,
                u.nombre_completo
            FROM posts p
            LEFT JOIN usuarios u ON p.id_usuario = u.id
            WHERE p.id_categoria = $id_categoria
            ORDER BY p.fecha_creacion DESC";

    $result = mysqli_query($db, $sql);

    if ($result === false) {
        outputError(500);
    }

    $posts = [];
    error_log("Cantidad de posts encontrados: " . count($posts));
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }

    mysqli_free_result($result);
    mysqli_close($db);

    output(['posts' => $posts]);
}










// Crear un nuevo post

function postPosts() {
$payload = requiereLogin(); // Verifica JWT y obtiene usuario
$db = conectarBD();

$data = json_decode(file_get_contents("php://input"), true);

$titulo = mysqli_real_escape_string($db, $data['titulo'] ?? '');
$contenido = mysqli_real_escape_string($db, $data['contenido'] ?? '');
$idCategoria = intval($data['id_categoria'] ?? 0);
$idUsuario = intval($payload->uid);

if (empty($titulo) || $idCategoria === 0) {
    output(['error' => 'Faltan datos'], 400);
}

$sql = "INSERT INTO posts (titulo, contenido, id_usuario, id_categoria) 
        VALUES ('$titulo', '$contenido', $idUsuario, $idCategoria)";

if (!mysqli_query($db, $sql)) {
    output(['error' => 'Error al crear el post', 'detalle' => mysqli_error($db)], 500);
}

$id = mysqli_insert_id($db);
mysqli_close($db);

output(['mensaje' => 'Post creado correctamente', 'id_post' => $id]);
}


// Obetenr posts con parametros

function getPostDetalleConParametros($id_post)
{
    $db = conectarBD();
    $id_post = intval($id_post);

    $sql = "
        SELECT 
            p.id_post,
            p.titulo,
            p.contenido,
            p.fecha_creacion,
            u.nombre_completo,
            u.avatar,
            c.nombre_categoria,
            (SELECT COUNT(*) FROM visitas_post vp WHERE vp.id_post = p.id_post) AS cantidad_vistas
        FROM posts p
        INNER JOIN usuarios u ON p.id_usuario = u.id
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE p.id_post = $id_post
    ";

    $result = mysqli_query($db, $sql);

    if (!$result || mysqli_num_rows($result) === 0) {
        outputError(404); // No encontrado
    }

    $post = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    mysqli_close($db);

    output(['post' => $post]);
}


// Post Categorias

function postCategorias()
{
    $payload = requiereLogin();
    $db = conectarBD();

    // Verificar rol admin
    if (!isset($payload->rol) || $payload->rol !== 'admin') {
        outputError(401); // No autorizado
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $nombre = mysqli_real_escape_string($db, $data['nombre_categoria'] ?? '');

    if (empty($nombre)) {
        output(['error' => 'Nombre de categoría es obligatorio'], 400);
    }

    $sql = "INSERT INTO categorias (nombre_categoria) VALUES ('$nombre')";

    if (!mysqli_query($db, $sql)) {
        output(['error' => 'Error al crear la categoría', 'detalle' => mysqli_error($db)], 500);
    }

    $id = mysqli_insert_id($db);
    mysqli_close($db);

    output(['mensaje' => 'Categoría creada correctamente', 'id_categoria' => $id]);
}


function deleteCategorias($id_categoria) {
    $payload = requiereLogin();

    // Solo admin puede borrar
    if (!isset($payload->rol) || $payload->rol !== 'admin') {
        outputError(401);
    }

    $db = conectarBD();
    $id_categoria = intval($id_categoria);

    // Opcional: chequea que no haya posts relacionados antes de borrar

    $sql = "DELETE FROM categorias WHERE id_categoria = $id_categoria";

    if (!mysqli_query($db, $sql)) {
        output(['error' => 'Error al eliminar la categoría', 'detalle' => mysqli_error($db)], 500);
    }

    mysqli_close($db);
    output(['mensaje' => 'Categoría eliminada correctamente']);
}













// Registrar visitas a un post

function postRegistrarVisita()
{
    $payload = requiereLogin();
    $db = conectarBD();
    $id_usuario = intval($payload->uid);

    $data = json_decode(file_get_contents("php://input"), true);
    $id_post = intval($data['id_post'] ?? 0);

    if ($id_post === 0) {
        output(['error' => 'Falta el id_post'], 400);
    }

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




                                    // COMENTARIOS //


// Obtener comentarios por ID de post

function buildTree(array $elements, $parentId = null) {
    $branch = [];

    foreach ($elements as $element) {
        if ($element['id_comentario_padre'] == $parentId) {
            $children = buildTree($elements, $element['id_comentario']);
            if ($children) {
                $element['respuestas'] = $children;
            } else {
                $element['respuestas'] = [];
            }
            $branch[] = $element;
        }
    }

    return $branch;
}

function getComentariosConParametros($id_post) {
    $db = conectarBD();
    $id_post = intval($id_post);

    $sql = "
        SELECT 
            c.id_comentario,
            c.contenido,
            c.fecha_comentario,
            c.id_usuario,
            c.id_comentario_padre,
            u.nombre_completo,
            u.avatar
        FROM comentarios c
        INNER JOIN usuarios u ON c.id_usuario = u.id
        WHERE c.id_post = $id_post
        ORDER BY c.fecha_comentario ASC
    ";

    $result = mysqli_query($db, $sql);
    if (!$result) {
        outputError(500);
    }

    $comentarios = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $comentarios[] = $row;
    }

    mysqli_free_result($result);
    mysqli_close($db);

    $comentariosAnidados = buildTree($comentarios, null);

    output(['comentarios' => $comentariosAnidados]);
}
// Crear un nuevo comentario

function postComentarios() {
    $db = conectarBD();
    $payload = requiereLogin();

    $data = json_decode(file_get_contents("php://input"), true);
    $contenido = mysqli_real_escape_string($db, $data['contenido'] ?? '');
    $id_post = intval($data['id_post'] ?? 0);
    $id_usuario = intval($payload->uid);
    $id_comentario_padre = isset($data['id_comentario_padre']) ? intval($data['id_comentario_padre']) : null;

    if (empty($contenido) || $id_post === 0) {
        output(['error' => 'Faltan datos'], 400);
    }

    $sql = "INSERT INTO comentarios 
            (id_post, id_usuario, contenido, id_comentario_padre) 
            VALUES ($id_post, $id_usuario, '$contenido', " . 
            ($id_comentario_padre !== null ? $id_comentario_padre : 'NULL') . ")";

    $result = mysqli_query($db, $sql);

    if (!$result) {
        output(['error' => 'Error al insertar comentario', 'detalle' => mysqli_error($db)], 500);
    }

    // Obtener autor del post para notificación
    $sqlAutorPost = "SELECT id_usuario, titulo FROM posts WHERE id_post = $id_post";
    $resAutorPost = mysqli_query($db, $sqlAutorPost);
    $id_usuario_destino = 0;
    $mensaje = "";

    if ($resAutorPost && mysqli_num_rows($resAutorPost) > 0) {
        $rowPost = mysqli_fetch_assoc($resAutorPost);
        $id_usuario_destino_post = intval($rowPost['id_usuario']);
        $titulo_post = $rowPost['titulo'];

        if ($id_comentario_padre !== null) {
            // Si es respuesta, notificar al autor del comentario padre
            $sqlAutorComentario = "SELECT id_usuario FROM comentarios WHERE id_comentario = $id_comentario_padre";
            $resAutorComentario = mysqli_query($db, $sqlAutorComentario);

            if ($resAutorComentario && mysqli_num_rows($resAutorComentario) > 0) {
                $rowComentario = mysqli_fetch_assoc($resAutorComentario);
                $id_usuario_destino = intval($rowComentario['id_usuario']);
                if ($id_usuario_destino !== $id_usuario) {
                    $mensaje = " respondió a tu comentario en el post: \"$titulo_post\"";
                }
            }
        } else {
            // Si es comentario nuevo, notificar al autor del post si no es quien comenta
            if ($id_usuario_destino_post !== $id_usuario) {
                $id_usuario_destino = $id_usuario_destino_post;
                $mensaje = " comentó tu publicación: \"$titulo_post\"";
            }
        }

        // Insertar notificación si mensaje y destinatario existen
        if ($id_usuario_destino !== 0 && !empty($mensaje)) {
            $sqlNoti = "INSERT INTO notificaciones (id_usuario_destino, id_usuario_origen, mensaje) VALUES ($id_usuario_destino, $id_usuario, '".mysqli_real_escape_string($db, $mensaje)."')";
            mysqli_query($db, $sqlNoti);
        }
    }

    mysqli_close($db);
    output(['mensaje' => 'Comentario guardado correctamente']);
}

// eliminar comentarios

function deleteComentarios() {
    $payload = requiereLogin();
    $db = conectarBD();

    $idComentario = intval($_GET['id_comentario'] ?? 0);
    if ($idComentario === 0) {
        output(['error' => 'Falta id_comentario'], 400);
    }

    $idUsuario = intval($payload->uid);
    $rolUsuario = $payload->rol;

    $sqlVerif = "SELECT id_usuario FROM comentarios WHERE id_comentario = $idComentario";
    $resVerif = mysqli_query($db, $sqlVerif);
    if (!$resVerif || mysqli_num_rows($resVerif) === 0) {
        output(['error' => 'Comentario no encontrado'], 404);
    }
    $row = mysqli_fetch_assoc($resVerif);

    if ($row['id_usuario'] != $idUsuario && $rolUsuario !== 'admin') {
        outputError(401); // No autorizado
    }

    // Opcional: eliminar respuestas hijas también (si quieres)
    $sqlDelete = "DELETE FROM comentarios WHERE id_comentario = $idComentario";
    $result = mysqli_query($db, $sqlDelete);

    if (!$result) {
        output(['error' => 'No se pudo eliminar el comentario', 'detalle' => mysqli_error($db)], 500);
    }

    mysqli_close($db);
    output(['mensaje' => 'Comentario eliminado correctamente']);
}

/////////////////////////// Seccion del index  //////////////////////////////

function getUsuariosAntiguos()
{
    requiereLogin();

    $db = conectarBD();
    $sql = "SELECT id, nombre_completo, fecha_registro, avatar 
            FROM usuarios 
            WHERE rol != 'admin'
            ORDER BY fecha_registro ASC 
            LIMIT 3";

    $result = mysqli_query($db, $sql);

    if ($result === false) {
        outputError(500);
    }

    $usuarios = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $usuarios[] = $row;
    }

    mysqli_free_result($result);
    mysqli_close($db);

    output(['usuarios_antiguos' => $usuarios]);
}



// Total de usuarios
function getTotalUsuarios()
{
    $db = conectarBD();
    $sql = "SELECT COUNT(*) AS total FROM usuarios";
    $result = mysqli_query($db, $sql);
    if ($result === false) {
        outputError(500);
    }
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    mysqli_close($db);
    output(['total_usuarios' => intval($row['total'])]);
}

// Total de posts
function getTotalPosts()
{
    $db = conectarBD();
    $sql = "SELECT COUNT(*) AS total FROM posts";
    $result = mysqli_query($db, $sql);
    if ($result === false) {
        outputError(500);
    }
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    mysqli_close($db);
    output(['total_posts' => intval($row['total'])]);
}

// Total de comentarios
function getTotalComentarios()
{
    $db = conectarBD();
    $sql = "SELECT COUNT(*) AS total FROM comentarios";
    $result = mysqli_query($db, $sql);
    if ($result === false) {
        outputError(500);
    }
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    mysqli_close($db);
    output(['total_comentarios' => intval($row['total'])]);
}

/////////////////// SERVICIO DE LIKES ///////////////////////

// Función para dar like a un post

function postLike_post() {
    $payload = requiereLogin();
    $db = conectarBD();

    $data = json_decode(file_get_contents("php://input"), true);
    $id_post = intval($data['id_post'] ?? 0);
    $id_usuario = intval($payload->uid);

    if ($id_post === 0) {
        output(['error' => 'Falta id_post'], 400);
    }

    // Verificar si ya existe el like
    $sqlCheck = "SELECT id_like FROM likes_post WHERE id_post = $id_post AND id_usuario = $id_usuario";
    $result = mysqli_query($db, $sqlCheck);
    if ($result && mysqli_num_rows($result) > 0) {
        output(['mensaje' => 'Ya le diste like a este post']);
    }

    // Insertar like
    $sql = "INSERT INTO likes_post (id_post, id_usuario, fecha_like) VALUES ($id_post, $id_usuario, NOW())";

    if (!mysqli_query($db, $sql)) {
        output(['error' => 'Error al dar like', 'detalle' => mysqli_error($db)], 500);
    }

    // Obtener el autor del post para notificación
    $sqlAutor = "SELECT id_usuario, titulo FROM posts WHERE id_post = $id_post";
    $resAutor = mysqli_query($db, $sqlAutor);
    if ($resAutor && mysqli_num_rows($resAutor) > 0) {
        $row = mysqli_fetch_assoc($resAutor);
        $id_usuario_destino = intval($row['id_usuario']);
        $titulo_post = $row['titulo'];

        // Evitar notificar si el usuario se da like a sí mismo
        if ($id_usuario_destino !== $id_usuario) {
            $mensaje = " le dio Me gusta a tu publicación: \"$titulo_post\"";

            $sqlNoti = "INSERT INTO notificaciones (id_usuario_destino, id_usuario_origen, mensaje) VALUES ($id_usuario_destino, $id_usuario, '".mysqli_real_escape_string($db, $mensaje)."')";
            mysqli_query($db, $sqlNoti);
        }
    }

    mysqli_close($db);
    output(['mensaje' => 'Like agregado']);
}


// Función para quitar like de un post
function deleteLikePost() {
    $payload = requiereLogin();
    $db = conectarBD();

    $id_post = intval($_GET['id_post'] ?? 0);
    $id_usuario = intval($payload->uid);

    if ($id_post === 0) {
        output(['error' => 'Falta id_post'], 400);
    }

    $sql = "DELETE FROM likes_post WHERE id_post = $id_post AND id_usuario = $id_usuario";

    if (!mysqli_query($db, $sql)) {
        output(['error' => 'Error al quitar like', 'detalle' => mysqli_error($db)], 500);
    }

    mysqli_close($db);
    output(['mensaje' => 'Like removido']);
}

// Función para obtener cantidad de likes y si el usuario dio like en un post
function getLikesPostConParametros($id_post) {
    $payload = null;
    $userId = 0;

    try {
        $payload = requiereLogin();
        $userId = intval($payload->uid);
    } catch(Exception $e) {
        // Usuario no autenticado, solo devuelve cantidad de likes
    }

    $db = conectarBD();
    $id_post = intval($id_post);

    $sqlCount = "SELECT COUNT(*) AS total_likes FROM likes_post WHERE id_post = $id_post";
    $resCount = mysqli_query($db, $sqlCount);
    $rowCount = mysqli_fetch_assoc($resCount);
    $totalLikes = intval($rowCount['total_likes']);

    $userLiked = false;
    if ($userId) {
        $sqlUser = "SELECT id_like FROM likes_post WHERE id_post = $id_post AND id_usuario = $userId";
        $resUser = mysqli_query($db, $sqlUser);
        $userLiked = ($resUser && mysqli_num_rows($resUser) > 0);
    }

    mysqli_close($db);
    output([
        'total_likes' => $totalLikes,
        'user_liked' => $userLiked
    ]);
}


// Eliminar Posts


function postDeletePost() {
    $payload = requiereLogin(); // Asegura que el usuario está autenticado
    $db = conectarBD();

    // Asegurarse de leer bien el JSON del body
    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON, true);

    if (!$data || !isset($data['id_post'])) {
        output(['error' => 'Falta id_post o formato inválido'], 400);
        return;
    }

    $id_post = intval($data['id_post']);

    // Verificar que el post exista
    $sql = "SELECT id_usuario FROM posts WHERE id_post = $id_post";
    $result = mysqli_query($db, $sql);

    if (!$result || mysqli_num_rows($result) === 0) {
        output(['error' => 'Post no encontrado'], 404);
        return;
    }

    $row = mysqli_fetch_assoc($result);
    $autor_post = intval($row['id_usuario']);

    // Validar permisos
    if ($payload->rol !== 'admin' && intval($payload->uid) !== $autor_post) {
        output(['error' => 'No autorizado'], 401);
        return;
    }

    // Eliminar el post
    $sqlDelete = "DELETE FROM posts WHERE id_post = $id_post";
    if (!mysqli_query($db, $sqlDelete)) {
        output(['error' => 'Error al eliminar el post', 'detalle' => mysqli_error($db)], 500);
        return;
    }

    mysqli_close($db);
    output(['mensaje' => 'Post eliminado correctamente']);
}



// Buscador


// getPost para el buscador

function getGetPosts() {
    $db = conectarBD();

    $sql = "
        SELECT 
            p.id_post, p.titulo, p.contenido, p.fecha_creacion,
            u.nombre_completo AS autor,
            c.nombre_categoria
        FROM posts p
        JOIN usuarios u ON p.id_usuario = u.id
        JOIN categorias c ON p.id_categoria = c.id_categoria
        ORDER BY p.fecha_creacion DESC
    ";

    $result = mysqli_query($db, $sql);

    if (!$result) {
        outputError(500);
    }

    $posts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }

    mysqli_free_result($result);
    mysqli_close($db);

    output(['posts' => $posts]);
}


function getBuscarPostsConParametros($query) {
    if (!$query) {
        outputError(400); // Faltan parámetros
    }

    $db = conectarBD();
    $query = "%$query%";

    $sql = "
        SELECT 
            p.id_post, p.titulo, p.contenido, p.fecha_creacion,
            u.nombre_completo AS autor,
            c.nombre_categoria
        FROM posts p
        JOIN usuarios u ON p.id_usuario = u.id
        JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE p.titulo LIKE ? OR p.contenido LIKE ?
        ORDER BY p.fecha_creacion DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $query, $query);
    $stmt->execute();

    $result = $stmt->get_result();

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }

    $stmt->close();
    $db->close();

    output(['posts' => $posts]);
}

function getBuscarPosts() {
    if (!isset($_GET['query'])) {
        outputError(400, 'Falta el parámetro de búsqueda');
    }

    $query = $_GET['query'];
    getBuscarPostsConParametros($query);
}

///////////////////////// NOTIFICACIONES /////////////////////////

function getNotificaciones()
{
    $payload = requiereLogin();
    $db = conectarBD();
    $id_usuario = intval($payload->uid);

$sql = "SELECT n.id_notificacion, n.mensaje, n.leido, n.fecha_envio, u.user_nameweb AS usuario_origen
        FROM notificaciones n
        JOIN usuarios u ON n.id_usuario_origen = u.id
        WHERE n.id_usuario_destino = $id_usuario
        ORDER BY n.fecha_envio DESC";

    $result = mysqli_query($db, $sql);

    if (!$result) {
        output(['error' => 'Error al obtener notificaciones', 'detalle' => mysqli_error($db)], 500);
    }

    $notificaciones = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notificaciones[] = $row;
    }

    mysqli_free_result($result);
    mysqli_close($db);

    output(['notificaciones' => $notificaciones]);
}

// Notificacion al comentar o dar like

function postNotificaciones()
{
    $payload = requiereLogin();
    $db = conectarBD();

    $data = json_decode(file_get_contents("php://input"), true);
    $id_usuario_destino = intval($data['id_usuario_destino'] ?? 0);
    $id_usuario_origen = intval($payload->uid); // Quien hace la acción
    $tipo = mysqli_real_escape_string($db, $data['tipo'] ?? '');
    $id_post = intval($data['id_post'] ?? 0);
    $id_comentario = intval($data['id_comentario'] ?? 0);

    if ($id_usuario_destino === 0 || empty($tipo)) {
        output(['error' => 'Faltan datos para crear notificación'], 400);
    }

    // Obtener nombre usuario origen
    $sqlUser = "SELECT user_nameweb FROM usuarios WHERE id = $id_usuario_origen LIMIT 1";
    $resUser = mysqli_query($db, $sqlUser);
    $userNameOrigen = '';
    if ($resUser && mysqli_num_rows($resUser) > 0) {
        $rowUser = mysqli_fetch_assoc($resUser);
        $userNameOrigen = $rowUser['user_nameweb'];
    } else {
        // Si no se encuentra el usuario origen, retornar error para no crear notificación inválida
        output(['error' => 'Usuario origen no encontrado'], 400);
    }

    // Construir mensaje según tipo de notificación
    $mensaje = '';
    if ($tipo === 'like_post') {
        $sqlPost = "SELECT titulo FROM posts WHERE id_post = $id_post LIMIT 1";
        $resPost = mysqli_query($db, $sqlPost);
        $tituloPost = 'tu publicación';
        if ($resPost && mysqli_num_rows($resPost) > 0) {
            $rowPost = mysqli_fetch_assoc($resPost);
            $tituloPost = $rowPost['titulo'];
        }
        $mensaje = "$userNameOrigen le dio Me gusta a tu publicación: \"$tituloPost\"";
    } elseif ($tipo === 'comentario_post') {
        $sqlPost = "SELECT titulo FROM posts WHERE id_post = $id_post LIMIT 1";
        $resPost = mysqli_query($db, $sqlPost);
        $tituloPost = 'tu publicación';
        if ($resPost && mysqli_num_rows($resPost) > 0) {
            $rowPost = mysqli_fetch_assoc($resPost);
            $tituloPost = $rowPost['titulo'];
        }
        $mensaje = "$userNameOrigen comentó tu publicación: \"$tituloPost\"";
    } elseif ($tipo === 'respuesta_comentario') {
        $sqlPost = "SELECT titulo FROM posts WHERE id_post = $id_post LIMIT 1";
        $resPost = mysqli_query($db, $sqlPost);
        $tituloPost = 'tu publicación';
        if ($resPost && mysqli_num_rows($resPost) > 0) {
            $rowPost = mysqli_fetch_assoc($resPost);
            $tituloPost = $rowPost['titulo'];
        }
        $mensaje = "$userNameOrigen respondió a tu comentario en el post: \"$tituloPost\"";
    } else {
        $mensaje = "Tienes una nueva notificación de $userNameOrigen";
    }

    $mensajeEsc = mysqli_real_escape_string($db, $mensaje);

    // Inserción con id_usuario_origen para guardar quién generó la notificación
    $sql = "INSERT INTO notificaciones (id_usuario_destino, id_usuario_origen, mensaje) 
            VALUES ($id_usuario_destino, $id_usuario_origen, '$mensajeEsc')";

    if (!mysqli_query($db, $sql)) {
        output(['error' => 'Error al crear notificación', 'detalle' => mysqli_error($db)], 500);
    }

    $id = mysqli_insert_id($db);
    mysqli_close($db);

    output(['mensaje' => 'Notificación creada', 'id_notificacion' => $id]);
}




// Notificacion leida

function patchNotificaciones()
{
    $payload = requiereLogin();
    $db = conectarBD();

    $data = json_decode(file_get_contents("php://input"), true);
    $id_notificacion = intval($data['id_notificacion'] ?? 0);

    if ($id_notificacion === 0) {
        output(['error' => 'Falta id_notificacion'], 400);
    }

    // Verificar que la notificación pertenezca al usuario
    $sqlVerif = "SELECT id_usuario_destino FROM notificaciones WHERE id_notificacion = $id_notificacion";
    $resVerif = mysqli_query($db, $sqlVerif);
    if (!$resVerif || mysqli_num_rows($resVerif) === 0) {
        output(['error' => 'Notificación no encontrada'], 404);
    }

    $row = mysqli_fetch_assoc($resVerif);
    if (intval($row['id_usuario_destino']) !== intval($payload->uid)) {
        outputError(401);
    }

    $sql = "UPDATE notificaciones SET leido = 1 WHERE id_notificacion = $id_notificacion";

    if (!mysqli_query($db, $sql)) {
        output(['error' => 'Error al actualizar notificación', 'detalle' => mysqli_error($db)], 500);
    }

    mysqli_close($db);

    output(['mensaje' => 'Notificación marcada como leída']);
}









function postEliminarUsuario()
{
    $payload = requiereLogin();
    $db = conectarBD();

    // Solo admin puede eliminar usuarios
    if ($payload->rol !== 'admin') {
        output(['error' => 'Acceso denegado'], 403);
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $id_usuario = intval($data['id_usuario'] ?? 0);

    if ($id_usuario === 0) {
        output(['error' => 'Falta id_usuario'], 400);
    }

    // Evitar que admin se elimine a sí mismo (opcional)
    if ($id_usuario === intval($payload->uid)) {
        output(['error' => 'No podés eliminar tu propio usuario'], 400);
    }

    // Verificar si el usuario existe
    $sqlCheck = "SELECT id FROM usuarios WHERE id = $id_usuario LIMIT 1";
    $resCheck = mysqli_query($db, $sqlCheck);
    if (!$resCheck || mysqli_num_rows($resCheck) === 0) {
        output(['error' => 'Usuario no encontrado'], 404);
    }

    // Eliminar usuario
    $sqlDelete = "DELETE FROM usuarios WHERE id = $id_usuario";
    if (!mysqli_query($db, $sqlDelete)) {
        output(['error' => 'Error al eliminar usuario', 'detalle' => mysqli_error($db)], 500);
    }

    mysqli_close($db);

    output(['mensaje' => 'Usuario eliminado correctamente']);
}


function getGetUsuarios()
{
    $db = conectarBD();
    $sql = "SELECT id, user_nameweb, email, nombre_completo, avatar, fecha_nacimiento, bio, rol, fecha_registro FROM usuarios";
    $result = mysqli_query($db, $sql);

    if ($result === false) {
        outputError(500);
    }



    $usuarios = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $usuarios[] = $row;

    }
    mysqli_free_result($result);
    mysqli_close($db);

    output(['usuarios' => $usuarios]);
}