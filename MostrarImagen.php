<?php
session_start();
require_once "Connection.php";

$sql = "";
$param_type = "";
$param_value = null;

// --- LÓGICA MEJORADA ---

// 1. Prioridad: Si se pasa un ID por URL (para los comentarios), usarlo.
if (isset($_GET['id_usuario']) && is_numeric($_GET['id_usuario'])) {
    
    // Usamos el ID de la URL
    $sql = "SELECT Foto FROM usuarios WHERE ID = ?";
    $param_type = "i";
    $param_value = intval($_GET['id_usuario']);

// 2. Fallback: Si no hay ID en URL, usar la lógica original (para el header)
} elseif (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['username'])) {
    
    // Usamos el Nombre_Usuario de la sesión
    $sql = "SELECT Foto FROM usuarios WHERE Nombre_Usuario = ?";
    $param_type = "s";
    $param_value = $_SESSION['username'];

// 3. Si no hay ni GET id ni sesión, no se puede mostrar nada.
} else {
    // No hay usuario que buscar.
    // Opcional: podrías mostrar una imagen por defecto aquí.
    header("HTTP/1.1 403 Forbidden");
    exit;
}

// --- EJECUCIÓN DE CONSULTA ---

$stmt = $conexion->prepare($sql);

if ($stmt === false) {
    die("Error al preparar la consulta: " . $conexion->error);
}

$stmt->bind_param($param_type, $param_value);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $fila = $resultado->fetch_assoc();
    $imagen_blob = $fila['Foto'];

    if (!empty($imagen_blob)) {
        // Enviar la cabecera de tipo de contenido (asumiendo JPEG)
        header("Content-Type: image/jpg"); 
        echo $imagen_blob;
    } else {
        // Opcional: Mostrar imagen por defecto si el BLOB está vacío
        // readfile('imagenes/default-avatar.png');
    }
} else {
     // Opcional: Mostrar imagen por defecto si el usuario no se encuentra
     // readfile('imagenes/default-avatar.png');
}

$stmt->close();
$conexion->close();
?>