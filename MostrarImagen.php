<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    
    header("HTTP/1.1 403 Forbidden");
    exit;
}

require_once "Connection.php";


// Obtener el nombre de usuario de la sesión
$username = $_SESSION['username'];

// ATENCIÓN: Cambia 'foto_perfil' por el nombre real de tu columna LONGBLOB
$sql = "SELECT Foto FROM usuarios WHERE Nombre_Usuario = ?";
$stmt = $conexion->prepare($sql);

if ($stmt === false) {
    die("Error al preparar la consulta: " . $conexion->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $fila = $resultado->fetch_assoc();
    $imagen_blob = $fila['Foto'];

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