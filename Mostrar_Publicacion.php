<?php
    session_start();
    require_once "Connection.php";

    $isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    $userRole = $isLoggedIn && isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
    $id_usuario_session = $isLoggedIn && isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;

    if ($isLoggedIn) {
        $username_session = $_SESSION['username'];
    }

    // --- PASO 1: OBTENER EL ID DE LA PUBLICACIÓN (DESDE GET O POST) ---
    // Lo necesitamos tanto para mostrar la página (GET) como para guardar el comentario (POST)
    $id_publicacion = 0;
    if (isset($_GET['token'])) {
    $id_publicacion = intval(base64_decode($_GET['token']));
    } elseif (isset($_POST['id_publicacion']) && is_numeric($_POST['id_publicacion'])) {
        $id_publicacion = intval($_POST['id_publicacion']);
    }

    if ($id_publicacion === 0) {
        $_SESSION['error'] = "Publicación no especificada.";
        header("Location: Pagina.php"); 
        exit;
    }


    // --- PASO 2: MANEJAR EL ENVÍO DE UN NUEVO COMENTARIO (SI ES POST) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comentario'])) {
        
        // Verificamos que el usuario esté logueado y el comentario no esté vacío
        if ($isLoggedIn && !empty(trim($_POST['comentario']))) {
            
            $comentario_texto = trim($_POST['comentario']);
            
            // Preparamos el INSERT usando los nombres de tu tabla
            $sql_insert = "INSERT INTO comentarios (Usuario_Comentario, Publicacion_Comentario, Comentario) 
                           VALUES (?, ?, ?)";
            
            if ($stmt_insert = $conexion->prepare($sql_insert)) {
                // "iis" -> integer, integer, string
                $stmt_insert->bind_param("iis", $id_usuario_session, $id_publicacion, $comentario_texto);
                
                if ($stmt_insert->execute()) {
                    // ¡Éxito! Recargamos la página para mostrar el nuevo comentario y limpiar el formulario
                    // Esto evita el reenvío del formulario si el usuario actualiza
                    header("Location: Mostrar_Publicacion.php?id_publicacion=" . $id_publicacion);
                    exit;
                } else {
                    $error_comentario = "Error al guardar el comentario.";
                }
                $stmt_insert->close();
            } else {
                $error_comentario = "Error al preparar la consulta.";
            }

        } elseif (!$isLoggedIn) {
            $error_comentario = "Debes iniciar sesión para comentar.";
        } else {
            $error_comentario = "El comentario no puede estar vacío.";
        }
    }


    // --- PASO 3: OBTENER LA PUBLICACIÓN ÚNICA (COMO ANTES) ---
    $publicacion_unica = null;
    $sql_post_unico = "SELECT p.ID_Publicacion, p.Titulo_Publicacion, p.Descripcion_Publicacion, p.Multimedia_Publicacion,
                              p.Vistas_Publicacion, 
                              u.Nombre_Usuario, c.Nombre_Categoria, p.Fecha_Aprobacion
                       FROM publicacion p
                       JOIN usuarios u ON p.Usuario_Publicacion = u.ID
                       JOIN categorias c ON p.Categorias_Publicacion = c.ID_Categorias
                       WHERE p.Estatus = 1 AND p.ID_Publicacion = ?";

    if ($stmt_post = $conexion->prepare($sql_post_unico)) {
        $stmt_post->bind_param("i", $id_publicacion);
        $stmt_post->execute();
        $resultado_post = $stmt_post->get_result();
        
        if ($resultado_post->num_rows === 1) {
            $publicacion_unica = $resultado_post->fetch_assoc();
        } else {
            $_SESSION['error'] = "La publicación no existe o no está disponible.";
            header("Location: Pagina.php");
            exit;
        }
        $stmt_post->close();
    } else {
        die("Error preparando la consulta de la publicación: " . $conexion->error);
    }

// --- INICIO: NUEVA LÓGICA HÍBRIDA DE CONTEO DE VISTAS ---
    
    // Esta variable 'interruptor' decidirá si actualizamos el contador general
    $debe_incrementar_contador = false; 
    $cookie_name = 'vistas_publicaciones'; // Nombre de nuestra cookie

    if ($isLoggedIn && $id_usuario_session !== null) {
        
        // --- CASO 1: USUARIO REGISTRADO ---
        // Intentamos registrar su vista en la tabla 'publicacion_vista'
        
        $sql_vista = "INSERT IGNORE INTO publicacion_vista (FK_Usuario, FK_Publicacion) 
                      VALUES (?, ?)";
        
        if ($stmt_vista = $conexion->prepare($sql_vista)) {
            $stmt_vista->bind_param("ii", $id_usuario_session, $id_publicacion);
            $stmt_vista->execute();
            
            // ¡Clave! Verificamos si la inserción fue exitosa (affected_rows > 0)
            // Si es 0, significa que ya existía (ya la había visto)
            if ($stmt_vista->affected_rows > 0) {
                $debe_incrementar_contador = true; // Es una vista nueva para este usuario
            }
            $stmt_vista->close();
        }
        
    } else {
        
        // --- CASO 2: USUARIO ANÓNIMO (POR COOKIE) ---
        
        // 1. Leemos la cookie y la decodificamos de JSON
        $vistas_cookie = [];
        if (isset($_COOKIE[$cookie_name])) {
            // true = decodifica como array asociativo
            $vistas_cookie = json_decode($_COOKIE[$cookie_name], true);
            if (!is_array($vistas_cookie)) {
                $vistas_cookie = []; // Si la cookie está corrupta, la reseteamos
            }
        }

        // 2. Verificamos si el ID de este post NO está en el array de la cookie
        if (!in_array($id_publicacion, $vistas_cookie)) {
            
            // 2a. No está: Es una vista nueva
            $debe_incrementar_contador = true;
            
            // 2b. Agregamos este ID al array
            $vistas_cookie[] = $id_publicacion;
            
            // 2c. Guardamos la cookie actualizada por 1 año
            // Guardamos el array como un string JSON: [1, 5, 12]
            setcookie($cookie_name, json_encode($vistas_cookie), time() + (86400 * 365), "/"); 
        }
        // Si el ID ya estaba en la cookie, no hacemos nada.
    }

    // --- ACCIÓN FINAL: ACTUALIZAR EL CONTADOR SI ES NECESARIO ---
    if ($debe_incrementar_contador && $id_publicacion > 0) {
        
        // Actualizamos la columna 'Vistas_Publicacion' de tu tabla 'publicacion'
        $sql_update_vistas = "UPDATE publicacion 
                              SET Vistas_Publicacion = Vistas_Publicacion + 1 
                              WHERE ID_Publicacion = ?";
        
        if ($stmt_update = $conexion->prepare($sql_update_vistas)) {
            $stmt_update->bind_param("i", $id_publicacion);
            $stmt_update->execute();
            $stmt_update->close();
        }
    }

    $total_likes = 0; // Valor por defecto
    
    // Usamos los nombres de columna de tu imagen (image_f3ba79.jpg)
    $sql_likes = "SELECT COUNT(*) AS total_likes 
                  FROM publicacion_like 
                  WHERE Publicacion_Like = ?";
                  
    if ($stmt_likes = $conexion->prepare($sql_likes)) {
        $stmt_likes->bind_param("i", $id_publicacion);
        $stmt_likes->execute();
        $resultado_likes = $stmt_likes->get_result();
        
        if ($fila_likes = $resultado_likes->fetch_assoc()) {
            $total_likes = $fila_likes['total_likes'];
        }
        $stmt_likes->close();
    }

    // --- PASO 4: OBTENER LOS COMENTARIOS EXISTENTES ---
    $comentarios = [];
    $sql_comentarios = "SELECT c.Comentario, c.Fecha_Comentario, u.Nombre_Usuario, u.ID AS id_comentarista
                        FROM comentarios c
                        JOIN usuarios u ON c.Usuario_Comentario = u.ID
                        WHERE c.Publicacion_Comentario = ?
                        ORDER BY c.Fecha_Comentario ASC"; // Mostrar los más antiguos primero // Mostrar los más antiguos primero

    if ($stmt_com = $conexion->prepare($sql_comentarios)) {
        $stmt_com->bind_param("i", $id_publicacion);
        $stmt_com->execute();
        $resultado_com = $stmt_com->get_result();
        
        while ($fila_com = $resultado_com->fetch_assoc()) {
            $comentarios[] = $fila_com;
        }
        $stmt_com->close();
    }

    // Ahora sí cerramos la conexión
    $conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($publicacion_unica['Titulo_Publicacion']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    
    <style>
        /* Estilos para la sección de comentarios */
        .comentarios-section {
            background-color: #3d3d3dff; /* Mantengo tu color */
            border-radius: 8px;
            padding: 20px;
            margin-top: 25px;
        }
        .comentarios-section h3 {
            margin-top: 0;
            border-bottom: 2px solid #555; /* Oscurecido para tu tema */
            padding-bottom: 10px;
            color: #fff; /* Color de texto para fondo oscuro */
        }
        .comentario-form textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            min-height: 80px;
            margin-bottom: 10px;
        }
        .comentario-form button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .comentario-form button:hover {
            background-color: #0056b3;
        }
        
        /* --- NUEVOS ESTILOS PARA LA LISTA (Estilo Reddit) --- */
        .lista-comentarios {
            margin-top: 20px;
        }
        .lista-comentarios > p{ /* El 'Sé el primero' */
            text-align: center;
        }
        
        /* Contenedor principal del comentario (Flex layout) */
        .comentario-reddit {
            display: flex;
            gap: 15px; /* Espacio entre la foto y el texto */
            border-bottom: 1px solid #555; /* Borde oscuro */
            padding: 15px 0;
        }
        .comentario-reddit:last-child {
            border-bottom: none;
        }

        /* Columna de la foto de perfil */
        .comentario-avatar {
            flex-shrink: 0; /* Evita que la columna de la foto se encoja */
        }
        .comentario-avatar img {
            width: 40px; /* Tamaño fijo para la foto */
            height: 40px;
            border-radius: 50%; /* ¡La hace redonda! */
            object-fit: cover; /* Evita que la imagen se estire */
            background-color: #555; /* Color de fondo mientras carga */
            border: 1px solid #777; /* Borde sutil */
        }
        
        /* Columna del contenido (nombre, fecha, texto) */
        .comentario-contenido {
            flex: 1; /* Ocupa el resto del espacio */
            min-width: 0; /* Soluciona problemas de 'overflow' en flex */
        }

        .comentario-info {
            font-size: 0.9em;
            color: #bbb; /* Color de info más tenue */
            margin-bottom: 5px;
        }
        .comentario-info strong {
            color: #fff; /* Nombre de usuario resaltado */
        }
        .comentario-info .fecha {
            color: #999;
        }

        /* El texto del comentario en sí */
        .comentario-contenido p {
            margin: 0;
            color: #ddd; /* Texto principal del comentario */
            /* Anula la 'p' de 'Sé el primero' */
            text-align: left; 
            /* Importante: permite que el texto largo se ajuste */
            word-wrap: break-word; 
        }

    </style>
</head>
<body>
<header>
    <h1>Publicacion Mundial <i class="fa-solid fa-futbol"></i></h1>
    <?php if ($isLoggedIn): ?>
    <div class="user">
        <img src="MostrarImagen.php" alt="Foto de perfil" height="50" width="50">
        <h3><?php echo htmlspecialchars($username_session); ?></h3>
    </div>
    <?php endif; ?>
</header>
<div class= "layout">
    <nav class="sidebar">
        <ul>
            <li><a href="Pagina.php"><i class="fas fa-home"></i><span>Ir al Inicio</span></a></li>
            <li><a href="javascript:history.back()" onclick="return true;"><i class="fas fa-undo"></i><span>Volver Atrás</span></a></li>
            
            <?php if ($isLoggedIn): ?>
            <li>
                <a href="logout.php"> <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
            </li>
            <?php else: ?>
            <li>
                <a href="InicioSesion.php">Iniciar Sesión</a>
            </li>
            <?php endif; ?>

            <?php if ($userRole == 1): ?>
            <li class="admin-dropdown"> <a href="#" class="dropdown-toggle" id="adminToggle"> <i class="fa-solid fa-user-tie"></i> Modo Admin </a>
                <ul>
                    <li><a href="/Pagina/Admin/AprobarP.php"> <i class="fa-solid fa-thumbs-up"></i> Aprobar Publicacion</a></li>
                    <li><a href="/Pagina/Admin/EliminarC.php"> <i class="fa-solid fa-trash"></i>Eliminar Comentario</a></li>
                    <li><a href="/Pagina/Admin/GestionarM.php"> <i class="fa-solid fa-list-check"></i> Gestionar Mundial</a></li>
                    <li><a href="/Pagina/Admin/Mundial.php"> <i class="fa-solid fa-globe"></i> Crear Mundial</a></li>
                </ul>
            </li> 
            <?php endif; ?>
            <li class="push-bottom"> 
                <a href="MisPosts.php"><i class="fas fa-cog"></i> Perfil</a>
            </li>
        </ul>
    </nav>

    <main> 
        <div class="publicacion">
            <div class="publicaciones-feed">
                
                <div class="publicacion-card">
                    <h2><?php echo htmlspecialchars($publicacion_unica['Titulo_Publicacion']); ?></h2>
                    <div class="info"> 
                        <span><strong>Autor:</strong> <?php echo htmlspecialchars($publicacion_unica['Nombre_Usuario']); ?></span>
                        <span><strong>Categoría:</strong> <?php echo htmlspecialchars($publicacion_unica['Nombre_Categoria']); ?></span>
                        <span><strong>Publicado:</strong> <?php echo htmlspecialchars(date("d/m/Y", strtotime($publicacion_unica['Fecha_Aprobacion']))); ?></span>

                        <br> <span>
                            <i class="fa-solid fa-eye"></i> <strong>Vistas:</strong> 
                            <?php echo htmlspecialchars($publicacion_unica['Vistas_Publicacion']); ?>
                        </span>
                        
                        <span>
                            <i class="fa-solid fa-thumbs-up"></i> <strong>Likes:</strong> 
                            <?php echo $total_likes; ?>
                        </span>
                    </div>

                    <p><?php echo nl2br(htmlspecialchars($publicacion_unica['Descripcion_Publicacion'])); ?></p>

                    <?php if (!empty($publicacion_unica['Multimedia_Publicacion'])): ?>
                        <?php
                            $multimedia_url = htmlspecialchars($publicacion_unica['Multimedia_Publicacion']);
                            $extension = strtolower(pathinfo(parse_url($multimedia_url, PHP_URL_PATH), PATHINFO_EXTENSION));
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
                </div>

                <div class="comentarios-section">
                    <h3>Comentarios</h3>

                    <?php if ($isLoggedIn): ?>
                        <form class="comentario-form" method="POST" action="Mostrar_Publicacion.php?token=<?php echo base64_encode($id_publicacion); ?>">
                            <textarea name="comentario" placeholder="Escribe tu comentario..." required></textarea>
                            
                            <input type="hidden" name="id_publicacion" value="<?php echo $id_publicacion; ?>">
                            
                            <button type="submit">Comentar</button> 
                        </form>
                    <?php else: ?>
                        <p>Debes <a href="InicioSesion.php">iniciar sesión</a> para poder comentar.</p>
                        <p>Cree una cuenta para comentar. <a href="InicioSesion.php">Iniciar Sesión o Registrarse</a></p>
                    <?php endif; ?>

                    <?php if (isset($error_comentario)): ?>
                        <p style="color: red;"><?php echo htmlspecialchars($error_comentario); ?></p>
                    <?php endif; ?>

                    <hr style="border-color: #555; margin-top: 20px;">

                    <div class="lista-comentarios">
                        <?php if (empty($comentarios)): ?>
                            <p style="color: #bbb;">Aún no hay comentarios. ¡Sé el primero en comentar!</p>
                        <?php else: ?>
                            <?php foreach ($comentarios as $com): ?>
                                <div class="comentario-reddit">
                                    
                                    <div class="comentario-avatar">
                                        <img src="MostrarImagen.php?id_usuario=<?php echo $com['id_comentarista']; ?>" alt="Foto de <?php echo htmlspecialchars($com['Nombre_Usuario']); ?>">
                                    </div>

                                    <div class="comentario-contenido">
                                        <div class="comentario-info">
                                            <strong><?php echo htmlspecialchars($com['Nombre_Usuario']); ?></strong>
                                            <span class="fecha"> • <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($com['Fecha_Comentario']))); ?></span>
                                        </div>
                                        <p><?php echo nl2br(htmlspecialchars($com['Comentario'])); ?></p>
                                    </div>
                                    
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>

            </div>
        </div>
    </main>
</div> 

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($error)) : ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Error',
    text: <?= json_encode($error) ?>,
    confirmButtonColor: '#d33'
});
</script>
<?php endif; ?>

<footer>
    <p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921 <p>
</footer>
</body>
</html>