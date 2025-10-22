<?php
// Iniciar la sesión
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Finalmente, destruir la sesión
session_destroy();

// Redirigir a la página de inicio de sesión
header("location: InicioSesion.php");
exit;
?>
