<?php
// Iniciar la sesión para acceder a las variables del usuario
session_start();
// Incluir tu archivo de conexión
require_once "Connection.php"; 

// Preparar una respuesta JSON que leerá JavaScript
header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// --- 1. VERIFICACIÓN DE SESIÓN (SEGURIDAD) ---

// ASUNCIÓN CRÍTICA:
// Para que esto funcione, tu script de login (donde haces $_SESSION['loggedin'] = true)
// DEBE TAMBIÉN guardar el ID numérico del usuario en la sesión.
// 
// Ejemplo de lo que deberías tener en tu script de login:
// $_SESSION['loggedin'] = true;
// $_SESSION['username'] = $usuario['Nombre_Usuario'];
// $_SESSION['user_id'] = $usuario['ID']; // <-- ¡ESTA LÍNEA ES ESENCIAL!

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $response['message'] = 'No has iniciado sesión.';
    echo json_encode($response);
    exit;
}

// Verificamos que el ID del usuario exista en la sesión
if (!isset($_SESSION['usuario_id'])) {
    $response['message'] = 'Error de sesión. No se encontró el ID de usuario. Asegúrate de guardarlo en el login.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['usuario_id'];

// --- 2. OBTENCIÓN DE DATOS (POST_ID) ---

if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    $response['message'] = 'Datos de publicación no válidos.';
    echo json_encode($response);
    exit;
}

$post_id = intval($_POST['post_id']);

// --- 3. LÓGICA DE INSERT EN LA BD ---

// Nombres de columnas de tu tabla 'publicacion_like'
$sql = "INSERT INTO publicacion_like (Usuario_Like, Publicacion_Like) VALUES (?, ?)";

if ($stmt = $conexion->prepare($sql)) {
    // Ligamos los parámetros: "i" = entero
    // 1er param: ID del usuario de la sesión
    // 2do param: ID de la publicación del botón
    $stmt->bind_param("ii", $user_id, $post_id);
    
    if ($stmt->execute()) {
        // ¡Éxito! El INSERT se completó.
        $response['success'] = true;
        $response['message'] = '¡Te gusta esta publicación!';
    } else {
        // El INSERT falló. La causa más probable es que ya exista (Clave duplicada)
        if ($conexion->errno == 1062) {
            // Error 1062 es "Duplicate entry" (clave primaria duplicada)
            // Esto significa que el usuario ya le había dado like.
            $response['success'] = false;
            $response['message'] = 'Ya te gusta esta publicación.';
        } else {
            // Otro error de base de datos
            $response['message'] = 'Error al registrar el like: ' . $conexion->error;
        }
    }
    $stmt->close();
} else {
    // El INSERT falló en la preparación (ej. error de sintaxis SQL)
    $response['message'] = 'Error al preparar la consulta: ' . $conexion->error;
}

$conexion->close();

// Devolvemos la respuesta (sea éxito o error) a JavaScript
echo json_encode($response);
?>