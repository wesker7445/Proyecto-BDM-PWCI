<?php
// Iniciar sesión para acceder a las variables de sesión
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Si no está logueado, se prohíbe el acceso a la imagen.
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "proyecto";

$conexion = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener el nombre de usuario de la sesión
$username = $_SESSION['username'];

// ATENCIÓN: Cambia 'foto_perfil' por el nombre real de tu columna LONGBLOB
$sql = "SELECT archivo FROM usuarios WHERE nombre_usuario = ?";
$stmt = $conexion->prepare($sql);

if ($stmt === false) {
    die("Error al preparar la consulta: " . $conexion->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $fila = $resultado->fetch_assoc();
    $imagen_blob = $fila['archivo'];

    if (!empty($imagen_blob)) {
        // Enviar la cabecera de tipo de contenido. 
        // Esto asume que guardas imágenes JPEG. Si usas PNG, cambia a "image/png"
        header("Content-Type: image/jpg"); 
        echo $imagen_blob;
    } else {
        // Opcional: Si el usuario no tiene imagen, muestra una por defecto.
        // Asegúrate de que esta imagen exista en tu proyecto.
        // readfile('imagenes/default-avatar.png');
    }
}

$stmt->close();
$conexion->close();
?>