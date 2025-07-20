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
        // Ya existe el like, devolver mensaje o error
        output(['mensaje' => 'Ya le diste like a este post']);
    }

    // Insertar like
    $sql = "INSERT INTO likes_post (id_post, id_usuario, fecha_like) VALUES ($id_post, $id_usuario, NOW())";

    if (!mysqli_query($db, $sql)) {
        output(['error' => 'Error al dar like', 'detalle' => mysqli_error($db)], 500);
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