<?php

session_start();


$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "proyecto";

$conexion = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {


    $nombre = $_POST["Name"];
    $apellido = $_POST["LastName"];
    $correo = $_POST["Mail"];
    $numero = $_POST["Number"];
    $genero = $_POST["gender"];
    $fecha_nacimiento = $_POST["Birthday"];
    $contrasena = $_POST["Password"];
    $verificacion = $_POST["PasswordVer"];
    $nacimiento = $_POST["birth_country"]; 
    $nacionalidad = $_POST["nationality"]; 

    if ($contrasena !== $verificacion) {
        echo "<script>alert('Las contraseñas no coinciden');</script>";
        exit;
    }


    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Correo inválido');</script>";
        exit;
    }

   
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);


    $archivo = null;
    if (isset($_FILES['Archivo']) && $_FILES['Archivo']['error'] === UPLOAD_ERR_OK) {
        $archivo = file_get_contents($_FILES['Archivo']['tmp_name']);
    }


    $sql = "INSERT INTO usuarios 
        (nombre_usuario, apellido_usuario, correo, numero_celular, genero, fecha_nacimiento, contrasena, nacimiento, nacionalidad, archivo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        die("Error al preparar la consulta: " . $conexion->error);
    }


    // s = string
    // i = integer
    // b = blob
    $placeholder = NULL; // Creamos una variable NULL
    $stmt->bind_param(
        "ssssssssib", // Séptimo parámetro cambiado de 'i' a 's'
        $nombre,
        $apellido,
        $correo,
        $numero,
        $genero,
        $fecha_nacimiento,
        $hash,
        $nacimiento,
        $nacionalidad,
        $placeholder
    );
if ($archivo !== null) {
    // El índice 9 corresponde al décimo '?'
    $stmt->send_long_data(9, $archivo);
}

    if ($stmt->execute()) {
        header("Location: inicioSesion.php?registro=exitoso");
        exit();
    } else {
        // Detiene el script y muestra el error exacto de MySQL
        die("Error al ejecutar la consulta: " . $stmt->error); 
    }

    $stmt->close();
}


$sql = "SELECT id_pais, nombre_pais FROM paises ORDER BY nombre_pais ASC";
$resultado_paises = $conexion->query($sql);

if ($resultado_paises) { // Es buena práctica verificar si el resultado no es false
    $total_paises = $resultado_paises->num_rows;
} else {
    $total_paises = 0;
    // Opcional: registrar el error de por qué falló la consulta de países
    error_log("Error en consulta de países: " . $conexion->error);
}
$conexion->close();
?>


<!DOCTYPE html>
    <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Crear Cuenta</title>
            <link rel="stylesheet" href="style.css">
        </head>
    <body>
    <header>
        <h1>Crear Cuenta/Editar Perfil <svg  xmlns="http://www.w3.org/2000/svg"  width="30"  height="30"  viewBox="0 0 24 24"  fill="none"  stroke="#6cd085"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-ball-football"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7l4.76 3.45l-1.76 5.55h-6l-1.76 -5.55z" /><path d="M12 7v-4m3 13l2.5 3m-.74 -8.55l3.74 -1.45m-11.44 7.05l-2.56 2.95m.74 -8.55l-3.74 -1.45" /></svg></h1>
    </header>

    <nav>
        <ul> 
            <li> 
                <a href ="#">Categorias</a>
                <ul>
                    <li><a href="#">Faltas</a></li>
                    <li><a href="#">Jugadas polémicas</a></li>
                    <li><a href="#">Goles</a></li>
                    <li><a href="#">Partidos</a></li>
                    <li><a href="#">Otro</a></li>
                </ul>
            </li>
            <li><a href ="InicioSesion.php">Inicio de Sesion</a></li>
            <li><a href ="Pagina.php">Menu Principal</a></li>
        </ul>
    </nav>
    <main>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data" class="Formulario" id="registroForm">
            <h2>Crear Cuenta/Editar Perfil</h2>
            <p> Datos Personales</p>
            <input type="text" name="Name" placeholder="Ingresa el nombre..." oninput="soloLetras(this)" required>
            <input type="text" name="LastName" placeholder="Ingresa el apellido..." oninput="soloLetras(this)" required>
            <input type="email" name="Mail" placeholder="Ingresa tu correo...">
            <input type="number" name="Number" placeholder="Ingresa tu número celular..." required>
                        
            <label for="gender">Selecciona tu género:</label>
            <select name="gender" id="gender" required>
            <option value="" selected disabled>-- Selecciona --</option>
            <option value="Hombre">Hombre</option>
            <option value="Mujer">Mujer</option>
            </select>

            
            <input type="date" name="Birthday" required>

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

            <div class="password-container">
                <input type="password" name="PasswordVer" id="PasswordVer" placeholder="Verifica la contraseña..." required> 
                <button type="button" id="togglePasswordVer" class="toggle-btn">
                    <span class="icon-show">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-eye-off"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" /></svg>
                    </span>
                    <span class="icon-hide d-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                    </span>
                </button>
            </div>

            <input type="file" name="Archivo" required>
            
            <label for="birth_country">Selecciona tu país de nacimiento:</label>
            <select name="birth_country" id="birth_country" required>
                <option value="" selected disabled>-- Selecciona --</option>
                <?php
                if ($total_paises > 0) {
                    while ($fila = $resultado_paises->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($fila['id_pais']) . '">' . htmlspecialchars($fila['nombre_pais']) . '</option>';
                    }
                }
                ?>
            </select>

            <label for="nationality">Selecciona tu nacionalidad:</label>
            <select name="nationality" id="nationality" required>
                <option value="" selected disabled>-- Selecciona --</option>
                <?php
                
                $resultado_paises->data_seek(0);
                if ($total_paises > 0) {
                    while ($fila = $resultado_paises->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($fila['id_pais']) . '">' . htmlspecialchars($fila['nombre_pais']) . '</option>';
                    }
                }
                ?>
            </select>

            <button type="submit">Registrarte</button>
        </form>
    </main>
        <script src = "funcion.js"></script>
        <footer>
            <p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921 <p>
        </footer>
    </body>
    </html>