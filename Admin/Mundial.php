<?php 

    // Iniciar la sesión al principio de todo
    session_start();
    require_once "../Connection.php";

    // Verificar si el usuario está logueado, si no, redirigirlo a la página de login
    $isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    $userRole = $isLoggedIn && isset($_SESSION['rol']) ? $_SESSION['rol'] : null;

    // El nombre de usuario se obtiene de la sesión
    if ($isLoggedIn) {
        $username_session = $_SESSION['username'];
    }

    // *** PASO 1: DETERMINAR EL MODO (CREAR O EDITAR) ***
    $esModoEditar = false;
    $datosMundial = [];
    $id_mundial_actual = null;

    if (isset($_GET['id_mundial']) && is_numeric($_GET['id_mundial'])) {
        $esModoEditar = true;
        $id_mundial_actual = (int)$_GET['id_mundial'];

        // --- Si es MODO EDITAR, obtenemos los datos actuales ---
        $stmt_datos = $conexion->prepare("SELECT Nombre_Torneo, Anio, Sede_Pais, Imagen_URL, Descripcion_mundial FROM mundiales WHERE ID_Mundial = ?");
        $stmt_datos->bind_param("i", $id_mundial_actual);
        $stmt_datos->execute();
        $resultado_datos = $stmt_datos->get_result();
        
        if ($resultado_datos->num_rows === 1) {
            $datosMundial = $resultado_datos->fetch_assoc();
        } else {
            // Si el ID no existe, es un error
            $_SESSION['error'] = "Error: Mundial no encontrado.";
            header("Location: GestionarM.php"); // Redirigir a la página de gestión
            exit();
        }
        $stmt_datos->close();
    }


    // Mostrar errores de sesión (si los hay)
    if (isset($_SESSION['error'])){    
        $error = $_SESSION['error'];
        unset($_SESSION['error']);
    }

    // *** PASO 2: PROCESAR EL FORMULARIO (POST) ***
    if ($_SERVER["REQUEST_METHOD"] == "POST"){

        $mundial = $_POST["Nombre_Mundial"];
        $descripcion = $_POST["Descripcion_Mundial"];
        $fecha_mundial = $_POST["Anio_Mundial"];
        $sede = $_POST["sede"];

        $errores = [];
        $anio_a_guardar = $fecha_mundial; 

        // *** LÓGICA DE IMAGEN MEJORADA ***
        // Si estamos en modo editar, la URL inicial es la que ya existe.
        $imagen_url = $datosMundial['Imagen_URL'] ?? null; 

        // Se subió un archivo nuevo (o se intentó)
        if (isset($_FILES['Archivo']) && $_FILES['Archivo']['error'] === UPLOAD_ERR_OK) {
            
            $file_tmp = $_FILES['Archivo']['tmp_name'];
            $file_name = basename($_FILES['Archivo']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $upload_dir = __DIR__ . "/../uploads/mundiales/"; 
            
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $errores[] = "❌ Error: No se pudo crear el directorio de subida: " . $upload_dir;
                    goto end_upload_process; 
                }
            }
            
            $unique_name = uniqid('mundial_', true) . "." . $file_ext;
            $upload_path = $upload_dir . $unique_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Si se mueve con éxito, actualizamos la URL (sea para crear o editar)
                $imagen_url = "/Pagina/uploads/mundiales/" . $unique_name;
                
                // (Opcional pero recomendado: si es modo editar, borrar la imagen antigua)
                // if ($esModoEditar && !empty($datosMundial['Imagen_URL'])) {
                //    $ruta_antigua = __DIR__ . "/.." . $datosMundial['Imagen_URL'];
                //    if (file_exists($ruta_antigua)) {
                //        @unlink($ruta_antigua);
                //    }
                // }
                
            } else {
                $errores[] = "⚠️ Error al mover el archivo subido.";
            }

        } elseif (isset($_FILES['Archivo']) && $_FILES['Archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Hubo un error, pero NO fue "no se subió archivo"
            $errores[] = "⚠️ Error en la subida del archivo: " . $_FILES['Archivo']['error'];
        }
        // Si NO se subió archivo (UPLOAD_ERR_NO_FILE):
        // - Si es MODO CREAR, $imagen_url seguirá siendo 'null' (correcto).
        // - Si es MODO EDITAR, $imagen_url mantendrá el valor de $datosMundial['Imagen_URL'] (correcto).

        end_upload_process: // Etiqueta para el goto

        // *** PASO 3: BLOQUE DE EJECUCIÓN (INSERT o UPDATE) ***
        if (empty($errores)) {

            if ($esModoEditar) {
                // --- MODO EDITAR: LÓGICA DE UPDATE ---
                $sql_update = "UPDATE mundiales SET Nombre_Torneo = ?, Anio = ?, Sede_Pais = ?, Imagen_URL = ?, Descripcion_mundial = ? WHERE ID_Mundial = ?";
                $stmt = $conexion->prepare($sql_update);
                
                // s: Nombre, s: Sede, s: Imagen_URL, s: Descripcion
                // i: Anio, i: ID_Mundial
                if ($stmt->bind_param("sisssi", $mundial, $anio_a_guardar, $sede, $imagen_url, $descripcion, $id_mundial_actual)) {
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Mundial actualizado exitosamente.";
                        // Redirigir de vuelta a esta misma página de edición
                        header("Location: " . $_SERVER['PHP_SELF'] . "?id_mundial=" . $id_mundial_actual . "&actualizado=exitoso");
                        exit();
                    } else {
                        $errores[] = "Error al actualizar en la base de datos: " . $stmt->error;
                    }
                } else {
                    $errores[] = "Error al preparar los parámetros de actualización: " . $stmt->error;
                }

            } else {
                // --- MODO CREAR: LÓGICA de INSERT (La que ya tenías) ---
                $sql_insert = "INSERT INTO mundiales (Nombre_Torneo, Anio, Sede_Pais, Imagen_URL, FK_Usuario, Descripcion_mundial) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conexion->prepare($sql_insert);

                $fk_usuario = $_SESSION['usuario_id'] ?? 0; // Asume que tienes el ID en la sesión.

                if ($stmt->bind_param("sissis", $mundial, $anio_a_guardar, $sede, $imagen_url, $fk_usuario, $descripcion)) {
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Mundial creado exitosamente.";
                        header("Location: " . $_SERVER['PHP_SELF'] . "?creado=exitoso"); // Redirigir
                        exit();
                    } else {
                        $errores[] = "Error al insertar en la base de datos: " . $stmt->error;
                    }
                } else {
                    $errores[] = "Error al preparar los parámetros de inserción: " . $stmt->error;
                }
            }
            $stmt->close(); 
        }

        if (!empty($errores)) {
            $error = implode("<br>", $errores);
        }
    }

    // --- CONSULTA DE PAÍSES (Para el HTML) ---
    $sql_paises = "SELECT ID_Pais, Pais FROM pais ORDER BY Pais ASC";
    $resultado_paises = $conexion->query($sql_paises);
    $paises = []; 

    if ($resultado_paises && $resultado_paises->num_rows > 0) {
        while ($fila = $resultado_paises->fetch_assoc()) {
            $paises[] = $fila; 
        }
    } else {
        $error_paises = "Error al cargar la lista de países.";
    }

    $conexion->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $esModoEditar ? 'Editar Mundial' : 'Crear Mundial'; ?></title>
    <link rel="stylesheet" href="/Pagina/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
</head>
<body>
    <header>
        <h1> <?php echo $esModoEditar ? 'Editar Mundial' : 'Crear Mundial'; ?> <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#6cd085" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-ball-football"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7l4.76 3.45l-1.76 5.55h-6l-1.76 -5.55z" /><path d="M12 7v-4m3 13l2.5 3m-.74 -8.55l3.74 -1.45m-11.44 7.05l-2.56 2.95m.74 -8.55l-3.74 -1.45" /></svg></h1>
        <?php if ($isLoggedIn): ?>
        <div class="user">
            <img src="../MostrarImagen.php" alt="Foto de perfil" height="50" width="50">
            <h3><?php echo htmlspecialchars($username_session); ?></h3>
        </div>
        <?php endif; ?>
    </header>
    <div class= "layout">
        <nav class="sidebar">
            <ul>             
                <li><a href="../Pagina.php"><i class="fas fa-home"></i><span>Ir al Inicio</span></a></li>
                <li><a href="javascript:history.back()" onclick="return true;"><i class="fas fa-undo"></i><span>Volver Atrás</span></a></li>

                <?php if ($isLoggedIn): ?>
                <li>
                    <a href="../logout.php"> <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                </li>
                <?php else: ?>
                <li>
                    <a href="../InicioSesion.php">Iniciar Sesión</a>
                </li>
                <?php endif; ?>

                <!-- Bloque con desplegable de admin -->
                <?php if ($userRole == 1): ?>
                <li class="admin-dropdown push-bottom"> 
                    <a href="#" class="dropdown-toggle" id="adminToggle"> <i class="fa-solid fa-user-tie"></i> Modo Admin </a>
                    <ul>
                        <li><a href="/Pagina/Admin/AprobarP.php"> <i class="fa-solid fa-thumbs-up"></i> Aprobar Publicacion</a></li>
                        <li><a href="/Pagina/Admin/EliminarC.php"> <i class="fa-solid fa-trash"></i>Eliminar Comentario</a></li>
                        <li><a href="/Pagina/Admin/GestionarM.php"> <i class="fa-solid fa-list-check"></i> Gestionar Mundial</a></li>
                        <li><a href="/Pagina/Admin/GestionarAdmin.php?tipo=categoria"><i class="fa-solid fa-tag"></i> Gestionar Categorías</a></li>
                        <li><a href="/Pagina/Admin/GestionarAdmin.php?tipo=pais"><i class="fa-solid fa-flag"></i> Gestionar Países</a></li>
                        <li><a href="/Pagina/Admin/Categoria.php?tipo=categoria"><i class="fa-solid fa-tag"></i>Crear Categoría</a></li>
                        <li><a href="/Pagina/Admin/Categoria.php?tipo=pais"><i class="fa-solid fa-flag"></i>Crear País</a></li>
                    </ul>
                </li> 
                <?php endif; ?>
                    
                <li>
                    <a href="MisPosts.php"><i class="fas fa-cog"></i> Perfil</a>
                </li>
            </ul>
        </nav>

    <main>
        <?php
            $action_url = htmlspecialchars($_SERVER['PHP_SELF']);
            if ($esModoEditar) {
                $action_url .= "?id_mundial=" . $id_mundial_actual;
            }
        ?>
        <form action="<?php echo $action_url; ?>" method="POST" enctype="multipart/form-data" class="Formulario">
            
            <h2><?php echo $esModoEditar ? 'Actualizar Mundial' : 'Crear Mundial'; ?></h2>
            
            <input type="text" name="Nombre_Mundial" id="Nombre_Mundial" placeholder="Ingresa el nombre..." 
                   value="<?php echo htmlspecialchars($datosMundial['Nombre_Torneo'] ?? ''); ?>" required>
            
            <textarea name="Descripcion_Mundial" id="Descripcion_Mundial" placeholder="Ingresa la descripcion..."><?php echo htmlspecialchars($datosMundial['Descripcion_mundial'] ?? ''); ?></textarea>
            
            <input type="number" name="Anio_Mundial" id="Anio_Mundial" min="1900" max="2100" placeholder="Año (ej: 2026)"
                   value="<?php echo htmlspecialchars($datosMundial['Anio'] ?? ''); ?>" required>
            
            <label for="sede">Selecciona la sede:</label>
            <select name="sede" id="sede" required>
                <option value=""> -- Selecciona la sede del mundial --</option>
                <?php
                    // Bucle corregido para seleccionar la sede guardada
                    if (isset($error_paises)) {
                        echo "<option disabled> $error_paises </option>";
                    } else {
                        foreach ($paises as $fila) { 
                            $nombre_pais = htmlspecialchars($fila['Pais']);
                            // Comparamos el país de la fila con el país guardado en $datosMundial
                            $seleccionado = (isset($datosMundial['Sede_Pais']) && $datosMundial['Sede_Pais'] == $nombre_pais) ? 'selected' : '';
                            echo "<option value=\"$nombre_pais\" $seleccionado>$nombre_pais</option>";
                        }
                    }
                ?>
            </select> 
            <label for="Archivo">
                <?php echo $esModoEditar ? 'Cambiar imagen (opcional):' : 'Sube la imagen del mundial:'; ?>
            </label>
            
            <?php if ($esModoEditar && !empty($datosMundial['Imagen_URL'])): ?>
                <div class="imagen-actual">
                    <p>Imagen Actual:</p>
                    <img src="<?php echo htmlspecialchars($datosMundial['Imagen_URL']); ?>" alt="Imagen actual" width="100" style="border-radius: 8px;">
                </div>
            <?php endif; ?>

            <input type="file" name="Archivo" id="Archivo">
            
            <button type="submit"><?php echo $esModoEditar ? 'Actualizar Mundial' : 'Crear Mundial'; ?></button>

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

            <?php if (isset($_GET['creado']) && $_GET['creado'] === 'exitoso') : ?>
            <script>
            Swal.fire({
                icon: 'success',
                title: '¡Mundial Creado!',
                text: 'El nuevo mundial se ha guardado exitosamente.',
                confirmButtonColor: '#3085d6'
            });
            </script>
            <?php endif; ?>

            <?php if (isset($_GET['actualizado']) && $_GET['actualizado'] === 'exitoso') : ?>
            <script>
            Swal.fire({
                icon: 'success',
                title: '¡Mundial Actualizado!',
                text: 'Los datos del mundial se han guardado exitosamente.',
                confirmButtonColor: '#3085d6'
            });
            </script>
            <?php endif; ?>

        </form>
    </main>
</div>

<footer>
<p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921 <p>
</footer>

</body>
</html>