<?php
    session_start();
    require_once "../Connection.php"; // Incluimos la conexión

    // --- PASO 1: LÓGICA DE PROCESAMIENTO (SOLO SI ES POST) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Verificamos que el usuario sea un administrador (rol 1)
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
            $_SESSION['error'] = "No tienes permiso para realizar esta acción.";
            header("Location: AprobarP.php");
            exit;
        }

        // Verificamos que la conexión exista
        if (!isset($conexion)) {
            $_SESSION['error'] = "Error de conexión a la base de datos.";
            header("Location: AprobarP.php");
            exit;
        }

        // Verificamos que tengamos los datos necesarios
        if (isset($_POST['id_publicacion']) && isset($_POST['accion'])) {
            
            $id_publicacion = $_POST['id_publicacion'];
            $accion = $_POST['accion'];
            
            if ($accion == 'aprobar') {
                // Acción de APROBAR: Cambiamos Estatus a 1
                $nuevo_estatus = 1;
                $sql = "UPDATE publicacion 
                SET Estatus = ?,
                    Fecha_Publicacion = Now(),
                    Fecha_Aprobacion = Now()
                WHERE ID_Publicacion = ?";
                
                if ($stmt = $conexion->prepare($sql)) {
                    $stmt->bind_param("ii", $nuevo_estatus, $id_publicacion);
                    if (!$stmt->execute()) {
                        $_SESSION['error'] = "Error al aprobar la publicación: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error'] = "Error al preparar la consulta: " . $conexion->error;
                }
            }
            // Como solo quieres el botón de aprobar, quitamos la lógica de "rechazar" de aquí.

        } else {
            $_SESSION['error'] = "Datos incompletos.";
        }

        $conexion->close();

        // --- Redirigir DE VUELTA ---
        // Esto SÓLO se ejecuta después de procesar el POST.
        header("Location: AprobarP.php");
        exit; // ¡Importante! Detiene el script aquí.
    }

    // --- PASO 2: LÓGICA DE VISUALIZACIÓN (SI ES GET) ---
    // Si el script llega aquí, es porque es un GET (carga normal de página).

    // Verificamos que la conexión exista (para cargar datos)
    if (!isset($conexion)) {
        die("Error fatal: No se pudo establecer la conexión a la base de datos.");
    }

    // Lógica para obtener el estado de login (para el header y nav)
    $isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    $username_session = $isLoggedIn && isset($_SESSION['username']) ? $_SESSION['username'] : '';
    $userRole = $isLoggedIn && isset($_SESSION['rol']) ? $_SESSION['rol'] : 0;

    // (Seguridad extra) Si NO es admin, ni siquiera debería ver esta página
    if ($userRole != 1) {
        header("Location: /Pagina/Pagina.php"); // O a donde quieras
        exit;
    }
        
    $publicaciones = [];
    $error_bd = isset($_SESSION['error']) ? htmlspecialchars($_SESSION['error']) : ""; 
    unset($_SESSION['error']); 

    // --- NUEVA LÓGICA DE BÚSQUEDA ---
    $busqueda = isset($_GET['Busqueda']) ? $_GET['Busqueda'] : null;
    $params = [];
    $types = "";

    // Consulta base para publicaciones pendientes (Estatus = 0)
    $sql_fetch = "SELECT p.ID_Publicacion, p.Titulo_Publicacion, p.Descripcion_Publicacion, p.Multimedia_Publicacion, 
                        u.Nombre_Usuario, t.Nombre_Torneo, c.Nombre_Categoria
                FROM publicacion p
                JOIN usuarios u ON p.Usuario_Publicacion = u.ID
                JOIN mundiales t ON p.Mundial_Publicacion = t.ID_Mundial
                JOIN categorias c ON p.Categorias_Publicacion = c.ID_Categorias
                WHERE p.Estatus = 0"; // <-- La condición de base

    // Lógica dinámica para agregar la condición de búsqueda
    if ($busqueda) {
        // Buscamos coincidencias en el Título O en la Descripción
        $sql_fetch .= " AND (p.Titulo_Publicacion LIKE ? OR p.Descripcion_Publicacion LIKE ?)";
        $types .= "ss"; // Dos strings
        $param_busqueda = "%" . $busqueda . "%"; // Los % permiten buscar texto parcial/literal
        $params[] = $param_busqueda;
        $params[] = $param_busqueda;
    }

    $sql_fetch .= " ORDER BY p.Fecha_Publicacion ASC";
    // --- FIN DE LA NUEVA LÓGICA ---


    // 1. Ejecución con sentencia preparada si hay búsqueda (o simple query si no la hay)
    if ($busqueda) {
        if ($stmt = $conexion->prepare($sql_fetch)) {
            // Usamos bind_param dinámicamente
            $stmt->bind_param($types, ...$params); 
            $stmt->execute();
            $resultado = $stmt->get_result();

            while ($fila = $resultado->fetch_assoc()) {
                $publicaciones[] = $fila;
            }
            $stmt->close();
        } else {
            $error_bd = "Error al preparar la consulta con búsqueda: " . $conexion->error;
        }

    } else {
        // Ejecución simple si NO hay búsqueda
        if ($resultado = $conexion->query($sql_fetch)) {
            while ($fila = $resultado->fetch_assoc()) {
                $publicaciones[] = $fila;
            }
            $resultado->free();
        } else {
            $error_bd = "Error al consultar las publicaciones: " . $conexion->error;
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprobar publicacion</title>
    <link rel="stylesheet" href="/Pagina/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
</head>
<body>
<header>
    <h1>Aprobar Publicacion <i class="fa-solid fa-futbol"></i></h1>
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
                    <li><a href="/Pagina/Admin/EliminarC.php"> <i class="fa-solid fa-trash"></i>Eliminar Comentario</a></li>
                    <li><a href="/Pagina/Admin/GestionarM.php"> <i class="fa-solid fa-list-check"></i> Gestionar Mundial</a></li>
                    <li><a href="/Pagina/Admin/GestionarAdmin.php?tipo=categoria"><i class="fa-solid fa-tag"></i> Gestionar Categorías</a></li>
                    <li><a href="/Pagina/Admin/GestionarAdmin.php?tipo=pais"><i class="fa-solid fa-flag"></i> Gestionar Países</a></li>
                    <li><a href="/Pagina/Admin/Mundial.php"> <i class="fa-solid fa-globe"></i> Crear Mundial</a></li>
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
        <div class="main-search-container">
            <form action="" method="GET"> <div class="search-box">
                    <input type="text" name="Busqueda" placeholder="Buscar en publicaciones pendientes..."
                        value="<?php echo isset($_GET['Busqueda']) ? htmlspecialchars($_GET['Busqueda']) : ''; ?>"> <button type="submit"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-zoom"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
                    </button>
                </div>
            </form>
        </div>

        <div class="admin-content">
            <h1>Administración de Publicaciones Pendientes</h1>

            <?php if (!empty($publicaciones)): ?>
                <?php foreach ($publicaciones as $pub): ?>
                    <div class="publicacion-card">
                        <h2><?php echo htmlspecialchars($pub['Titulo_Publicacion']); ?></h2>
                        <div class="info"> 
                            <span><strong>Autor:</strong> <?php echo htmlspecialchars($pub['Nombre_Usuario']); ?></span>
                            <span><strong>Torneo:</strong> <?php echo htmlspecialchars($pub['Nombre_Torneo']); ?></span>
                            <span><strong>Categoría:</strong> <?php echo htmlspecialchars($pub['Nombre_Categoria']); ?></span>
                        </div>

                        <p><?php echo nl2br(htmlspecialchars($pub['Descripcion_Publicacion'])); ?></p>

                        <?php if (!empty($pub['Multimedia_Publicacion'])): ?>
                            <?php
                                $multimedia_url = htmlspecialchars($pub['Multimedia_Publicacion']);
                                // Obtener la extensión del archivo
                                $extension = strtolower(pathinfo(parse_url($multimedia_url, PHP_URL_PATH), PATHINFO_EXTENSION));
                                
                                // Extensiones comunes de imagen y video (¡ajusta esto a lo que uses!)
                                $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                $video_extensions = ['mp4', 'webm', 'ogg'];
                            ?>

                        <div class="multimedia-container">
                        <?php if (in_array($extension, $image_extensions)): ?>
                            <img src="<?php echo $multimedia_url; ?>" alt="Imagen de la publicación">
                            
                        <?php elseif (in_array($extension, $video_extensions)): ?>
                            <video controls>
                                <source src="<?php echo $multimedia_url; ?>" type="video/<?php echo $extension; ?>">
                                    Tu navegador no soporta la etiqueta de video.
                            </video>
                            
                        <?php else: ?>
                            <p>Enlace multimedia no reconocido: <a href="<?php echo $multimedia_url; ?>" target="_blank">Ver Archivo</a></p>
                        <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    <div class="acciones">
                        <form action="AprobarP.php" method="POST" class="btn-form">
                                <input type="hidden" name="id_publicacion" value="<?php echo $pub['ID_Publicacion']; ?>">
                                <input type="hidden" name="accion" value="aprobar">
                                <button class="btn-aprobar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-thumb-up"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 11v8a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1v-7a1 1 0 0 1 1 -1h3a4 4 0 0 0 4 -4v-1a2 2 0 0 1 4 0v5h3a2 2 0 0 1 2 2l-1 5a2 3 0 0 1 -2 2h-7a3 3 0 0 1 -3 -3" /></svg>
                                    APROBAR
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <p>¡No hay publicaciones pendientes de aprobación y/o no hay publicaciones con ese nombre!</p>
            <?php endif; ?>

            <?php if ($error_bd): ?>
                <p class="error-bd">⚠️ Error de Base de Datos: <?php echo $error_bd; ?></p>
            <?php endif; ?>
        </div>
    </main>
</div>
<footer>
<p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921 <p>
</footer>

</body>
</html>