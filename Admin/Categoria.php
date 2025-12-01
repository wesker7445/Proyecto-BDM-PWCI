<?php

    // Iniciar la sesión al principio de todo
    session_start();
    require_once "../Connection.php";

    // 1. VERIFICAR USUARIO Y ROL
    $isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    $userRole = $isLoggedIn && isset($_SESSION['rol']) ? $_SESSION['rol'] : null;

    if ($isLoggedIn) {
        $username_session = $_SESSION['username'];
    }

    // 2. DETERMINAR EL MODO PRINCIPAL (CATEGORIA O PAIS)
    $tipo_entidad = isset($_GET['tipo']) && in_array($_GET['tipo'], ['categoria', 'pais']) ? $_GET['tipo'] : null;

    if (!$tipo_entidad) {
        // Redirigir si no se especifica el tipo, o mostrar un error
        $_SESSION['error'] = "Error: Tipo de entidad no especificado.";
        header("Location: GestionarM.php"); // Redirige a una página de gestión general
        exit();
    }

    // 3. CONFIGURACIÓN DINÁMICA DE LA ENTIDAD
    if ($tipo_entidad === 'categoria') {
        $tabla = 'categorias';
        $id_columna = 'ID_Categorias';
        $nombre_columna = 'Nombre_Categoria';
        $nombre_singular = 'Categoría';
        $nombre_plural = 'Categorías';
        $redireccion_error = 'GestionarC.php'; // Cambiar si tienes una página de gestión
    } else { // tipo_entidad === 'pais'
        $tabla = 'pais';
        $id_columna = 'ID_Pais';
        $nombre_columna = 'Pais';
        $nombre_singular = 'País';
        $nombre_plural = 'Países';
        $redireccion_error = 'GestionarP.php'; // Cambiar si tienes una página de gestión
    }

    // 4. PASO 1: DETERMINAR EL MODO (CREAR O EDITAR)
    $esModoEditar = false;
    $datosEntidad = [];
    $id_actual = null;

    $id_param = "id_" . $tipo_entidad; // Crea la variable GET esperada (ej: id_categoria)

    if (isset($_GET[$id_param]) && is_numeric($_GET[$id_param])) {
        $esModoEditar = true;
        $id_actual = (int)$_GET[$id_param];

        // --- MODO EDITAR: Obtenemos los datos actuales ---
        $stmt_datos = $conexion->prepare("SELECT $id_columna, $nombre_columna FROM $tabla WHERE $id_columna = ?");
        $stmt_datos->bind_param("i", $id_actual);
        $stmt_datos->execute();
        $resultado_datos = $stmt_datos->get_result();

        if ($resultado_datos->num_rows === 1) {
            $datosEntidad = $resultado_datos->fetch_assoc();
        } else {
            $_SESSION['error'] = "Error: " . $nombre_singular . " no encontrado.";
            header("Location: " . $redireccion_error);
            exit();
        }
        $stmt_datos->close();
    }


    // Mostrar errores de sesión
    if (isset($_SESSION['error'])){
        $error = $_SESSION['error'];
        unset($_SESSION['error']);
    }

    // 5. PASO 2: PROCESAR EL FORMULARIO (POST)
    if ($_SERVER["REQUEST_METHOD"] == "POST"){

        $nombre_entidad = trim($_POST["Nombre_Entidad"]);
        $errores = [];

        if (empty($nombre_entidad)) {
            $errores[] = "⚠️ El nombre de la " . strtolower($nombre_singular) . " no puede estar vacío.";
        }

        // 6. PASO 3: BLOQUE DE EJECUCIÓN (INSERT o UPDATE)
        if (empty($errores)) {

            if ($esModoEditar) {
                // --- MODO EDITAR: LÓGICA DE UPDATE ---
                $sql_update = "UPDATE $tabla SET $nombre_columna = ? WHERE $id_columna = ?";
                $stmt = $conexion->prepare($sql_update);

                // s: Nombre, i: ID
                if ($stmt->bind_param("si", $nombre_entidad, $id_actual)) {
                    if ($stmt->execute()) {
                        $_SESSION['success'] = $nombre_singular . " actualizado(a) exitosamente.";
                        // Redirigir de vuelta a esta misma página de edición
                        header("Location: " . $_SERVER['PHP_SELF'] . "?tipo=$tipo_entidad&" . $id_param . "=" . $id_actual . "&actualizado=exitoso");
                        exit();
                    } else {
                        $errores[] = "Error al actualizar en la base de datos: " . $stmt->error;
                    }
                } else {
                    $errores[] = "Error al preparar los parámetros de actualización: " . $stmt->error;
                }

            } else {
                // --- MODO CREAR: LÓGICA de INSERT ---
                $sql_insert = "INSERT INTO $tabla ($nombre_columna) VALUES (?)";
                $stmt = $conexion->prepare($sql_insert);

                if ($stmt->bind_param("s", $nombre_entidad)) {
                    if ($stmt->execute()) {
                        $_SESSION['success'] = $nombre_singular . " creado(a) exitosamente.";
                        header("Location: " . $_SERVER['PHP_SELF'] . "?tipo=$tipo_entidad&creado=exitoso"); // Redirigir
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

    $conexion->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $esModoEditar ? 'Editar ' . $nombre_singular : 'Crear ' . $nombre_singular; ?></title>
    <link rel="stylesheet" href="/Pagina/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
</head>
<body>
    <header>
        <h1> <?php echo $esModoEditar ? 'Editar ' . $nombre_singular : 'Crear ' . $nombre_singular; ?>
        <?php if ($tipo_entidad === 'categoria'): ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#6cd085" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-tag"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7.5 7.5a4.5 4.5 0 1 0 4.5 4.5" /><path d="M13.43 17.97l-3.32 -10.97a1.06 1.06 0 0 1 .47 -1.25l7.98 -4.84a1.06 1.06 0 0 1 1.25 .47l4.84 7.98a1.06 1.06 0 0 1 -.47 1.25l-7.98 4.84a1.06 1.06 0 0 1 -1.25 -.47z" /></svg>
        <?php else: // tipo_entidad === 'pais' ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#6cd085" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-flag-3"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 14.761l-2 -1.139v-7.122l2 -1.138m14 8.761l2 -1.139v-7.122l-2 -1.138" /><path d="M5 3v18" /><path d="M5 14.761c3.111 .602 6.516 .602 9.627 0c3.111 -.602 4.192 -1.139 4.192 -1.139v-7.247c0 .178 -1.081 .715 -4.192 1.317c-3.111 .602 -6.516 .602 -9.627 0v7.069z" /></svg>
        <?php endif; ?>
        </h1>
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
            $action_url = htmlspecialchars($_SERVER['PHP_SELF']) . "?tipo=$tipo_entidad";
            if ($esModoEditar) {
                $action_url .= "&" . $id_param . "=" . $id_actual;
            }
        ?>
        <form action="<?php echo $action_url; ?>" method="POST" class="Formulario">

            <h2><?php echo $esModoEditar ? 'Actualizar ' . $nombre_singular : 'Crear ' . $nombre_singular; ?></h2>
            <input type="text" name="Nombre_Entidad" id="Nombre_Entidad"
                   placeholder="Ingresa el nombre de la <?php echo strtolower($nombre_singular); ?>..."
                   value="<?php echo htmlspecialchars($datosEntidad[$nombre_columna] ?? ''); ?>" required>

            <button type="submit"><?php echo $esModoEditar ? 'Actualizar ' . $nombre_singular : 'Crear ' . $nombre_singular; ?></button>

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
                title: '¡<?php echo $nombre_singular; ?> Creado!',
                text: 'La nueva <?php echo strtolower($nombre_singular); ?> se ha guardado exitosamente.',
                confirmButtonColor: '#3085d6'
            });
            </script>
            <?php endif; ?>

            <?php if (isset($_GET['actualizado']) && $_GET['actualizado'] === 'exitoso') : ?>
            <script>
            Swal.fire({
                icon: 'success',
                title: '¡<?php echo $nombre_singular; ?> Actualizado!',
                text: 'Los datos de la <?php echo strtolower($nombre_singular); ?> se han guardado exitosamente.',
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