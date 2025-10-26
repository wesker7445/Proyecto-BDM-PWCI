<?php
session_start();
require_once "Connection.php";

// --- 1. DETERMINAR EL MODO ---
$esModoEditar = false;
$datosUsuario = [];
$id_usuario_actual = null;
$error = null; // Variable para SweetAlert

// Verifica si el usuario ha iniciado sesión
if (isset($_SESSION['usuario_id'])) {
    $esModoEditar = true;
    $id_usuario_actual = $_SESSION['usuario_id'];

    // Si está en modo editar, busca los datos actuales del usuario
    // USAMOS ID_Usuario (¡corregido!)ID_Usuario
    $stmt_datos = $conexion->prepare("SELECT Nombre_Usuario, Foto, Genero, Pais_Nacimiento, Nacionalidad, Fecha_Nacimientro, Email FROM usuarios WHERE ID = ?");
    $stmt_datos->bind_param("i", $id_usuario_actual);
    $stmt_datos->execute();
    $resultado_datos = $stmt_datos->get_result();
    
    if ($resultado_datos->num_rows === 1) {
        $datosUsuario = $resultado_datos->fetch_assoc();
    } else {
        // El usuario de la sesión no existe, forzar cierre de sesión
        session_destroy();
        header("Location: inicioSesion.php?error=UsuarioNoValido");
        exit();
    }
    $stmt_datos->close();
}


// --- 2. PROCESAR EL FORMULARIO (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- ¡CORREGIDO! Recoger TODAS las variables ---
    $nombre = $_POST["Name"];
    $correo = $_POST["Mail"];
    $genero = $_POST["gender"];
    $fecha_nacimiento = $_POST["Birthday"];
    $contrasena = $_POST["Password"];
    $verificacion = $_POST["PasswordVer"];
    $nacimiento = $_POST["birth_country"];
    $nacionalidad = $_POST["nationality"];

    $errores = [];

    // --- 3. VALIDACIONES (Lógica modificada para Editar) ---

    // VALIDACIÓN DE CONTRASEÑA
    if (!empty($contrasena)) {
        // Si el usuario escribió algo en el campo contraseña (en crear O editar)
        if (strlen($contrasena) < 8) $errores[] = "⚠️ Contraseña: Mínimo 8 caracteres.";
        if (!preg_match('/[A-Z]/', $contrasena)) $errores[] = "⚠️ Contraseña: Requiere una mayúscula.";
        if (!preg_match('/[a-z]/', $contrasena)) $errores[] = "⚠️ Contraseña: Requiere una minúscula.";
        if (!preg_match('/[0-9]/', $contrasena)) $errores[] = "⚠️ Contraseña: Requiere un número.";
        if (!preg_match('/[^a-zA-Z0-9]/', $contrasena)) $errores[] = "⚠️ Contraseña: Requiere un símbolo.";
        if ($contrasena !== $verificacion) $errores[] = "⚠️ Las contraseñas no coinciden.";

    } elseif (!$esModoEditar) {
        // Si la contraseña está vacía Y estamos en MODO CREAR, es un error
        $errores[] = "⚠️ Debes ingresar una contraseña.";
    }
    // Si la contraseña está vacía Y estamos en MODO EDITAR, no es un error. Simplemente no se actualiza.

    // VALIDACIÓN DE EMAIL
    // (Tu lógica de email con checkdnsrr es excelente, la dejamos igual)
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "⚠️ Correo electrónico inválido.";
    } else {
        $partes = explode('@', $correo);
        $dominio = array_pop($partes);
        if (!checkdnsrr($dominio, "MX")) {
            $errores[] = "⚠️ El dominio '$dominio' no parece existir.";
        }
    }

    // VALIDACIÓN DE ARCHIVO (FOTO)
    if (isset($_FILES['Archivo']) && $_FILES['Archivo']['error'] === UPLOAD_ERR_OK) {
        // Si se subió un archivo (en crear O editar), lo validamos
        $tempFile = $_FILES['Archivo']['tmp_name'];
        $check = @getimagesize($tempFile);
        if ($check === false) {
            $errores[] = "⚠️ El archivo subido no es una imagen.";
        } else {
            $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP];
            if (!in_array($check[2], $allowed_types)) {
                $errores[] = "⚠️ Solo se aceptan imágenes JPG, PNG o BMP.";
            }
        }
    } elseif (!$esModoEditar) {
        // Si NO se subió archivo Y estamos en MODO CREAR, es un error
        $errores[] = "⚠️ Debes subir una foto de perfil.";
    }
    // Si NO se subió archivo Y estamos en MODO EDITAR, no es un error. Simplemente no se actualiza.

    // VALIDACIÓN DE EDAD
    // (Tu lógica de edad es excelente, la dejamos igual)
    if (empty($fecha_nacimiento)) {
        $errores[] = "⚠️ Debes ingresar tu fecha de nacimiento.";
    } else {
        try {
            $fecha_nac_obj = new DateTime($fecha_nacimiento);
            $hoy = new DateTime('today');
            if ($fecha_nac_obj > $hoy) {
                $errores[] = "⚠️ No puedes nacer en el futuro.";
            } else {
                $edad = $hoy->diff($fecha_nac_obj)->y;
                if ($edad < 12) $errores[] = "⚠️ Debes ser mayor de 12 años.";
                if ($edad > 120) $errores[] = "⚠️ Fecha de nacimiento incorrecta.";
            }
        } catch (Exception $e) {
            $errores[] = "⚠️ Formato de fecha de nacimiento no válido.";
        }
    }

    // --- 4. BLOQUE DE EJECUCIÓN (INSERT o UPDATE) ---
    
    if (empty($errores)) {
        
        if ($esModoEditar) {
            // --- MODO EDITAR: LÓGICA DE UPDATE ---
            
            // Construimos la consulta UPDATE dinámicamente
            $sql_update = "UPDATE usuarios SET Nombre_Usuario = ?, Email = ?, Genero = ?, Pais_Nacimiento = ?, Nacionalidad = ?, Fecha_Nacimientro = ?";
            $tipos = "sssiss"; // Tipos de datos para bind_param
            $parametros = [$nombre, $correo, $genero, $nacimiento, $nacionalidad, $fecha_nacimiento];

            // ¿El usuario quiere cambiar la contraseña?
            if (!empty($contrasena)) {
                $hash = password_hash($contrasena, PASSWORD_DEFAULT);
                $sql_update .= ", Pass = ?";
                $tipos .= "s";
                $parametros[] = $hash;
            }
            
            // ¿El usuario quiere cambiar la foto?
            $archivo = null;
            if (isset($_FILES['Archivo']) && $_FILES['Archivo']['error'] === UPLOAD_ERR_OK) {
                $archivo = file_get_contents($_FILES['Archivo']['tmp_name']);
                $sql_update .= ", Foto = ?";
                $tipos .= "b"; // Tipo 'b' para BLOB
                $parametros[] = $archivo;
            }
            
            // Añadimos el WHERE
            $sql_update .= " WHERE ID = ?";
            $tipos .= "i";
            $parametros[] = $id_usuario_actual;
            
            $stmt = $conexion->prepare($sql_update);
            $stmt->bind_param($tipos, ...$parametros); // El '...' expande el array
            
            // Si hay un archivo, enviarlo con send_long_data
            if ($archivo !== null) {
                // El índice del BLOB es el (total de '?') - 2 (porque el ID es el último)
                $blob_index = substr_count($sql_update, "?") - (strpos($sql_update, "Pass = ?") ? 2 : 1);
                if (strpos($sql_update, "Pass = ?") === false) $blob_index = 6;
                else $blob_index = 7;
                
                $blob_index = array_search($archivo, $parametros); // Forma más simple
                $stmt->send_long_data($blob_index, $archivo);
            }

            if ($stmt->execute()) {
                // ¡Éxito! Redirige a esta misma página con un mensaje
                header("Location: CrearC.php?actualizado=exitoso");
                exit();
            } else {
                $error = "❌ Error al actualizar: " . $stmt->error;
            }

        } else {
            // --- MODO CREAR: LÓGICA DE INSERT (La que ya tenías) ---
            
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $archivo = file_get_contents($_FILES['Archivo']['tmp_name']);
            
            $sql_insert = "INSERT INTO usuarios (Nombre_Usuario, Foto, Genero, Pais_Nacimiento, Nacionalidad, Fecha_Nacimientro, Email, Pass) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql_insert);
            
            $placeholder = NULL;
            $stmt->bind_param("sbsiisss", $nombre, $placeholder, $genero, $nacimiento, $nacionalidad, $fecha_nacimiento, $correo, $hash);
            $stmt->send_long_data(1, $archivo);
            
            if ($stmt->execute()) {
                header("Location: inicioSesion.php?registro=exitoso");
                exit();
            } else {
                $error = "❌ Error al registrar: " . $stmt->error;
            }
        }
        $stmt->close();
        
    } else {
        // Si hay errores de validación
        $error = implode("<br>", $errores);
    }
} // Fin del if ($_SERVER["REQUEST_METHOD"] == "POST")


// --- 5. CONSULTA DE PAÍSES (Para el HTML) ---
$sql_paises = "SELECT ID_Pais, Pais FROM pais ORDER BY Pais ASC";
$resultado_paises = $conexion->query($sql_paises);
$paises = []; // Guardamos los países en un array para usarlos dos veces

if ($resultado_paises && $resultado_paises->num_rows > 0) {
    while ($fila = $resultado_paises->fetch_assoc()) {
        $paises[] = $fila; // Almacena cada fila
    }
} else {
    // Si falla la consulta de países, es un error grave para el formulario
    $error = "Error al cargar la lista de países.";
}

$conexion->close();
?>

<!DOCTYPE html>
    <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo $esModoEditar ? 'Editar Perfil' : 'Crear Cuenta'; ?></title>
            <link rel="stylesheet" href="style.css">
        </head>
    <body class="<?php echo $esModoEditar ? 'modo-editar' : 'modo-crear'; ?>">
    <header>
        <h1> 
            <?php echo $esModoEditar ? 'Editar Perfil' : 'Crear Cuenta'; ?> 
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#6cd085" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-ball-football"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7l4.76 3.45l-1.76 5.55h-6l-1.76 -5.55z" /><path d="M12 7v-4m3 13l2.5 3m-.74 -8.55l3.74 -1.45m-11.44 7.05l-2.56 2.95m.74 -8.55l-3.74 -1.45" /></svg>
        </h1>

        <div class="user">
            <?php if ($esModoEditar && isset($datosUsuario['Foto'])): ?>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($datosUsuario['Foto']); ?>" alt="Foto de perfil" width="40" height="40">
                <h3><?php echo htmlspecialchars($datosUsuario['Nombre_Usuario']); ?></h3>
            
            <?php else: ?>
                <a href="InicioSesion.php">
                    <h3>Iniciar Sesión</h3>
                </a>
            <?php endif; ?>
        </div>
    </header>
    <div class= "layout">
        <nav class="sidebar">
            <ul> 
                <li>
                    <a href ="InicioSesion.php">Inicio de Sesion</a>
                </li>
                <li>
                    <a href ="Pagina.php">Menu Principal</a>
                </li>
            </ul>
        </nav>
    
        <main>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data" class="Formulario" id="registroForm">
                
                <h2><?php echo $esModoEditar ? 'Actualizar mis Datos' : 'Crear Cuenta'; ?></h2>

                <?php if ($esModoEditar && !empty($datosUsuario['Foto'])): ?>
                    <div class="perfil-actual">
                        <p>Foto de Perfil Actual:</p>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($datosUsuario['Foto']); ?>" alt="Foto de perfil" width="100">
                    </div>
                <?php endif; ?>

                <p>Datos Personales</p>
                
                <input type="text" name="Name" placeholder="Ingresa el nombre de usuario..." oninput="soloLetras(this)" 
                    value="<?php echo htmlspecialchars($datosUsuario['Nombre_Usuario'] ?? ''); ?>" required>
                
                <input type="email" name="Mail" placeholder="Ingresa tu correo..."
                    value="<?php echo htmlspecialchars($datosUsuario['Email'] ?? ''); ?>" required>

                <label for="gender">Selecciona tu género:</label>
                <select name="gender" id="gender" required>
                    <option value="" disabled <?php echo empty($datosUsuario['Genero']) ? 'selected' : ''; ?>>-- Selecciona --</option>
                    <option value="Hombre" <?php echo ($datosUsuario['Genero'] ?? '') === 'Hombre' ? 'selected' : ''; ?>>Hombre</option>
                    <option value="Mujer" <?php echo ($datosUsuario['Genero'] ?? '') === 'Mujer' ? 'selected' : ''; ?>>Mujer</option>
                </select>

                <input type="date" name="Birthday" 
                    value="<?php echo htmlspecialchars($datosUsuario['Fecha_Nacimientro'] ?? ''); ?>" required>

        <div class="password-container">
            <input type="password" name="Password" id="Password" 
                placeholder="<?php echo $esModoEditar ? 'Nueva contraseña (dejar en blanco para no cambiar)' : 'Ingresa la contraseña...'; ?>"
                <?php echo $esModoEditar ? '' : 'required'; ?>>
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
            <input type="password" name="PasswordVer" id="PasswordVer" 
                placeholder="<?php echo $esModoEditar ? 'Verifica la nueva contraseña' : 'Verifica la contraseña...'; ?>"
                <?php echo $esModoEditar ? '' : 'required'; ?>> 
            <button type="button" id="togglePasswordVer" class="toggle-btn">
                <span class="icon-show">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-eye-off"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" /></svg>
                </span>
                <span class="icon-hide d-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                </span>
            </button>
        </div>

                <label for="Archivo">
                    <?php echo $esModoEditar ? 'Cambiar foto de perfil (opcional):' : 'Sube tu foto de perfil:'; ?>
                </label>
                <input type="file" name="Archivo" id="Archivo" 
                    <?php echo $esModoEditar ? '' : 'required'; ?>>
                
                <label for="birth_country">Selecciona tu país de nacimiento:</label>
                <select name="birth_country" id="birth_country" required>
                    <option value="" disabled <?php echo empty($datosUsuario['Pais_Nacimiento']) ? 'selected' : ''; ?>>-- Selecciona --</option>
                    <?php
                    foreach ($paises as $fila) {
                        $id_pais = htmlspecialchars($fila['ID_Pais']);
                        $nombre_pais = htmlspecialchars($fila['Pais']);
                        $seleccionado = ($datosUsuario['Pais_Nacimiento'] ?? '') == $id_pais ? 'selected' : '';
                        echo "<option value=\"$id_pais\" $seleccionado>$nombre_pais</option>";
                    }
                    ?>
                </select>

                <label for="nationality">Selecciona tu nacionalidad:</label>
                <select name="nationality" id="nationality" required>
                    <option value="" disabled <?php echo empty($datosUsuario['Nacionalidad']) ? 'selected' : ''; ?>>-- Selecciona --</option>
                    <?php
                    foreach ($paises as $fila) { // Reutilizamos el array de países
                        $id_pais = htmlspecialchars($fila['ID_Pais']);
                        $nombre_pais = htmlspecialchars($fila['Pais']);
                        $seleccionado = ($datosUsuario['Nacionalidad'] ?? '') == $id_pais ? 'selected' : '';
                        echo "<option value=\"$id_pais\" $seleccionado>$nombre_pais</option>";
                    }
                    ?>
                </select>

                <button type="submit">
                    <?php echo $esModoEditar ? 'Guardar Cambios' : 'Registrarte'; ?>
                </button>
            </form>
            
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

            <?php if (isset($error)) : ?>
            <script>
            Swal.fire({
                icon: 'error',
                title: 'Oops... Tienes errores:',
                html: <?= json_encode($error) ?>,
                confirmButtonColor: '#d33'
            });
            </script>
            <?php endif; ?>

            <?php if (isset($_GET['actualizado']) && $_GET['actualizado'] === 'exitoso') : ?>
            <script>
            Swal.fire({
                icon: 'success',
                title: '¡Perfil actualizado!',
                text: 'Tus datos han sido guardados.',
                confirmButtonColor: '#3085d6'
            });
            </script>
            <?php endif; ?>
            
            <?php if (isset($_GET['registro']) && $_GET['registro'] === 'exitoso') : ?>
            <script>
            Swal.fire({
                icon: 'success',
                title: '¡Registro exitoso!',
                text: 'Tu cuenta ha sido creada. Ahora inicia sesión.',
                confirmButtonColor: '#3085d6'
            });
            </script>
            <?php endif; ?>
        
            <script src="funcion.js"></script>

        </main>
    </div>
    <footer>
        <p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921</p>
    </footer>
</body>
</html>