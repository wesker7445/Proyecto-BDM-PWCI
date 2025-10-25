<?php

session_start();


require_once "Connection.php";


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. RECOGER DATOS ---
    $nombre = $_POST["Name"];
    $correo = $_POST["Mail"];
    $genero = $_POST["gender"];
    $fecha_nacimiento = $_POST["Birthday"];
    $contrasena = $_POST["Password"];
    $verificacion = $_POST["PasswordVer"];
    $nacimiento = $_POST["birth_country"];
    $nacionalidad = $_POST["nationality"];

    // --- 2. BLOQUE DE VALIDACIÓN ---
    
    // ¡CAMBIO IMPORTANTE! Usaremos un array para guardar TODOS los errores
    $errores = [];

    // Validación 1: Contraseñas coinciden
    if ($contrasena !== $verificacion) {
        $errores[] = "⚠️ Las contraseñas no coinciden.";
    }

    // ¡NUEVO! Validación de fortaleza de contraseña
    // Solo validamos la fortaleza si las contraseñas no están vacías
    if (!empty($contrasena)) {
        if (strlen($contrasena) < 8) {
            $errores[] = "⚠️ La contraseña debe tener al menos 8 caracteres.";
        }
        if (!preg_match('/[a-z]/', $contrasena)) {
            $errores[] = "⚠️ La contraseña debe incluir al menos una letra minúscula.";
        }
        if (!preg_match('/[A-Z]/', $contrasena)) {
            $errores[] = "⚠️ La contraseña debe incluir al menos una letra mayúscula.";
        }
        if (!preg_match('/[0-9]/', $contrasena)) { // También puedes usar '/\d/'
            $errores[] = "⚠️ La contraseña debe incluir al menos un número.";
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $contrasena)) { // Busca un caracter que NO sea letra ni número
            $errores[] = "⚠️ La contraseña debe incluir al menos un carácter especial (ej: !@#$%).";
        }
    } else {
        $errores[] = "⚠️ Debes ingresar una contraseña.";
    }


    // Validación 3: Email (Nivel 1 y 2)
    // Se cambió de 'elseif' a 'if' para que se valide siempre
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "⚠️ Correo electrónico inválido. Asegúrate de que el formato sea correcto (ej: usuario@dominio.com)";
    }
    // Nivel 2: (Recomendado) Validación de Dominio
    else {
        // Obtenemos solo la parte del dominio
        $partes = explode('@', $correo);
        $dominio = array_pop($partes); // Obtiene lo que está después de la @

        // Comprobamos si ese dominio tiene registros DNS de correo (MX)
        if (!checkdnsrr($dominio, "MX")) {
            $errores[] = "⚠️ El dominio '$dominio' no parece existir o no puede recibir correos. Revisa que esté bien escrito.";
        }
    }

    // Validación 4: Archivo
    // Primero, verifica si el archivo se subió correctamente
    if (!isset($_FILES['Archivo']) || $_FILES['Archivo']['error'] !== UPLOAD_ERR_OK)
    {
        // Maneja diferentes errores de subida
        $upload_error = $_FILES['Archivo']['error'] ?? UPLOAD_ERR_NO_FILE;
        switch ($upload_error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errores[] = "⚠️ El archivo es demasiado grande.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $errores[] = "⚠️ No se seleccionó ningún archivo.";
                break;
            default:
                $errores[] = "⚠️ Ocurrió un error desconocido al subir el archivo.";
        }
    }
    // Si se subió, AHORA comprueba si es una imagen
    else {
        $tempFile = $_FILES['Archivo']['tmp_name'];
        
        // Usamos @ para suprimir warnings si el archivo no es una imagen
        $check = @getimagesize($tempFile);
        
        if ($check === false) {
            // Si getimagesize falla, no es una imagen
            $errores[] = "⚠️ El archivo subido no es una imagen válida.";
        } else {
            // Es una imagen, ahora verificamos los tipos permitidos (JPG, PNG, BMP)
            $allowed_types = [
                IMAGETYPE_JPEG, // Para .jpg y .jpeg
                IMAGETYPE_PNG,  // Para .png
                IMAGETYPE_BMP   // Para .bmp
            ];
            
            // $check[2] contiene el tipo de imagen (ej. IMAGETYPE_JPEG)
            if (!in_array($check[2], $allowed_types)) {
                $errores[] = "⚠️ Tipo de imagen no permitido. Solo se aceptan JPG, PNG o BMP.";
            }
        }
    }
    
    if (empty($fecha_nacimiento)) {
        $errores[] = "⚠️ Debes ingresar tu fecha de nacimiento.";
    } else {
        try {
            // Creamos un objeto DateTime para la fecha de nacimiento
            $fecha_nac_obj = new DateTime($fecha_nacimiento);
            
            // Creamos un objeto DateTime para el día de hoy (sin la hora)
            $hoy = new DateTime('today');

            // 1. Comprobar que la fecha de nacimiento no sea en el futuro
            if ($fecha_nac_obj > $hoy) {
                $errores[] = "⚠️ No puedes nacer en el futuro. Revisa tu fecha de nacimiento.";
            } else {
                // 2. Calcular la diferencia entre hoy y la fecha de nacimiento
                $diferencia = $hoy->diff($fecha_nac_obj);
                
                // Obtenemos la edad en años ('y' es la propiedad para 'years')
                $edad = $diferencia->y;

                // 3. Comprobar si es menor de 18 años
                if ($edad < 12) {
                    $errores[] = "⚠️ Debes ser mayor de 12 años para registrarte.";
                }
                
                // Opcional: Una comprobación de "cordura" (ej. no más de 120 años)
                if ($edad > 120) {
                     $errores[] = "⚠️ La fecha de nacimiento parece incorrecta (¿Más de 120 años?).";
                }
            }

        } catch (Exception $e) {
            // Esto captura si la fecha tiene un formato inválido (ej. 31/02/2000)
            $errores[] = "⚠️ El formato de la fecha de nacimiento no es válido.";
        }
    }


    // --- 3. BLOQUE DE EJECUCIÓN ---
    
    // ¡CAMBIO IMPORTANTE! Verificamos si nuestro array de errores está VACÍO
    if (empty($errores)) {

        // Si no hay errores, procedemos con la inserción
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $archivo = null;
        if (isset($_FILES['Archivo']) && $_FILES['Archivo']['error'] === UPLOAD_ERR_OK) {
            $archivo = file_get_contents($_FILES['Archivo']['tmp_name']);
        }

        $sql = "INSERT INTO usuarios 
                (Nombre_Usuario, Foto, Genero, Pais_Nacimiento, Nacionalidad, Fecha_Nacimientro, Email, Pass)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            // Si la preparación falla, creamos un error
            $error = "Error al preparar la consulta: " . $conexion->error;
        } else {
            $placeholder = NULL;
            $stmt->bind_param(
                "sbsiisss",
                $nombre, 
                $placeholder, 
                $genero, 
                $nacimiento, 
                $nacionalidad, 
		        $fecha_nacimiento, 
                $correo, 
                $hash 
            );

            if ($archivo !== null) {
                $stmt->send_long_data(1, $archivo);
            }

            if ($stmt->execute()) {
                header("Location: inicioSesion.php?registro=exitoso");
                exit();
            } else {
                // Si la ejecución falla (ej. correo duplicado), creamos un error
                $error = "❌ Error al registrar: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        // ¡NUEVO! Si hay errores de validación, los unimos todos
        // Usamos <br> para que SweetAlert muestre saltos de línea
        $error = implode("<br>", $errores);
    }
}

$sql = "SELECT ID_Pais, Pais FROM pais ORDER BY Pais ASC";
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
            <input type="text" name="Name" placeholder="Ingresa el nombre de usuario..." oninput="soloLetras(this)" required>
            <input type="email" name="Mail" placeholder="Ingresa tu correo...">
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
                        echo '<option value="' . htmlspecialchars($fila['ID_Pais']) . '">' . htmlspecialchars($fila['Pais']) . '</option>';
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
                        echo '<option value="' . htmlspecialchars($fila['ID_Pais']) . '">' . htmlspecialchars($fila['Pais']) . '</option>';
                    }
                }
                ?>
            </select>

            <button type="submit">Registrarte</button>
        </form>

        
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        
        <?php if (isset($error)) : ?>
        <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops... Tienes los siguientes errores:', // Título un poco más descriptivo
            html: <?= json_encode($error) ?>, // ¡CAMBIO! De 'text' a 'html'
            confirmButtonColor: '#d33'
        });
        </script>
        <?php endif; ?>

        <?php if (isset($_GET['registro']) && $_GET['registro'] === 'exitoso') : ?>
        <script>
        Swal.fire({
            icon: 'success',
            title: '¡Registro exitoso!',
            text: 'Tu cuenta ha sido creada correctamente.',
            confirmButtonColor: '#3085d6'
        });
        </script>
        <?php endif; ?>
       
        <script src="funcion.js"></script>
    </main>
        <footer>
            <p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921 <p>
        </footer>
    </body>
    </html>