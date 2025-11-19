<?php

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    require_once "Connection.php";

    $username = $_POST['Name'];
    $password = $_POST['Password'];

    // Consulta que selecciona ID, Contraseña (Pass) y Rol
    $sql = "SELECT ID, Pass, Rol FROM usuarios WHERE Nombre_Usuario = ?";
    $stmt = $conexion->prepare($sql);

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultado = $stmt->get_result();


    if ($resultado->num_rows === 1) {
        $fila = $resultado->fetch_assoc();
        $hash_guardado = $fila['Pass'];

        if (password_verify($password, $hash_guardado)) {

            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            
            $_SESSION['usuario_id'] = $fila['ID'];
            
            $_SESSION['rol'] = $fila['Rol'];
            
            header("Location: Pagina.php");
            exit;
        } else {
            $error_message = "La contraseña es incorrecta.";
        }
    } else {
        $error_message = "El nombre de usuario no existe.";
    }

    $stmt->close();
    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="full-screen-layout">
    <header>
        <h1>Inicio de Sesión <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#6cd085" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-ball-football"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7l4.76 3.45l-1.76 5.55h-6l-1.76 -5.55z" /><path d="M12 7v-4m3 13l2.5 3m-.74 -8.55l3.74 -1.45m-11.44 7.05l-2.56 2.95m.74 -8.55l-3.74 -1.45" /></svg></h1>
    </header>
    <div class= "layout">
        <main>
            <form action="InicioSesion.php" method="POST" class="Formulario">
                <h2>Inicio de Sesión</h2>
                <?php 
                if (!empty($error_message)) {
                    echo '<p class="error-message">' . htmlspecialchars($error_message) . '</p>';
                }
                ?>
                <input type="text" name="Name" id="Name" placeholder="Ingresa el nombre..." required>
                    <div class="password-container">
                        <input type="password" name="Password" id="Password" placeholder="Ingresa la contraseña..." required>
                            <button type="button" id="togglePassword" class="toggle-btn">
                                <span class="icon-show">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-eye-off"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" /></svg>
                                </span>
                                <span class="icon-hide d-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                </span>
                            </button>
                        </div>
                    <button type="submit">Iniciar Sesión</button>
                <p class="form-link">¿No tienes cuenta? <a href="CrearC.php">Crear Cuenta</a></p>
            </form>
        </main>
        </div>
    <footer>
        <p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921</p>
    </footer>
    <script src = "login.js"></script>
</body>
</html>