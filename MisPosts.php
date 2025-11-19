<?php 

// Iniciar la sesión al principio de todo
    session_start();
    require_once "Connection.php"; // Esto debería crear el objeto $conexion

    $isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    $userRole = $isLoggedIn && isset($_SESSION['rol']) ? $_SESSION['rol'] : null;

    // El nombre de usuario se obtiene de la sesión
    $username_session = $isLoggedIn ? $_SESSION['username'] : 'Invitado';

    if (isset($_SESSION['error'])){    
        $error = $_SESSION['error'];
        unset($_SESSION['error']);
    }

    // --- INICIO DE LA LÓGICA DEL PERFIL ---

    // Inicializar variables
    $user_id = null;
    $total_posts = 0;
    $posts = []; // Un array para guardar las publicaciones
    
    $pais_nacimiento = "No especificado";
    $nacionalidad = "No especificada";

    if ($isLoggedIn) {
        if (isset($_SESSION['usuario_id'])) {
            $user_id = $_SESSION['usuario_id'];
        } else {
            $sql_get_id = "SELECT ID FROM usuarios WHERE Nombre_Usuario = ?";
            if ($stmt_id = $conexion->prepare($sql_get_id)) {
                $stmt_id->bind_param("s", $username_session);
                $stmt_id->execute();
                $result_id = $stmt_id->get_result();
                if ($result_id->num_rows > 0) {
                    $row_id = $result_id->fetch_assoc();
                    $user_id = $row_id['ID_Usuario'];
                    $_SESSION['usuario_id'] = $user_id; // Guárdalo en la sesión para la próxima vez
                }
                $stmt_id->close();
            }
        }

        // Si encontramos al usuario, buscamos sus posts
        if ($user_id) {

            $sql_perfil = "SELECT 
                            p_nac.Pais AS PaisNacimiento, 
                            p_nat.Pais AS Nacionalidad
                           FROM usuarios
                           LEFT JOIN pais AS p_nac ON usuarios.Pais_Nacimiento = p_nac.ID_Pais
                           LEFT JOIN pais AS p_nat ON usuarios.Nacionalidad = p_nat.ID_Pais
                           WHERE usuarios.ID = ?";
            
            if ($stmt_perfil = $conexion->prepare($sql_perfil)) {
                $stmt_perfil->bind_param("i", $user_id);
                $stmt_perfil->execute();
                $result_perfil = $stmt_perfil->get_result();
                
                if ($result_perfil->num_rows > 0) {
                    $row_perfil = $result_perfil->fetch_assoc();

                    if (!empty($row_perfil['PaisNacimiento'])) {
                        $pais_nacimiento = $row_perfil['PaisNacimiento'];
                    }

                    if (!empty($row_perfil['Nacionalidad'])) {
                        $nacionalidad = $row_perfil['Nacionalidad'];
                    }
                }
                $stmt_perfil->close();
            }
            
            // 2. Contar el total de posts del usuario
            // (Basado en tu tabla 'publicacion', columna 'Usuario_Publicacion' de la imagen f1e55d.jpg)
            $sql_count = "SELECT COUNT(*) as total FROM publicacion WHERE Usuario_Publicacion = ?";
            if($stmt_count = $conexion->prepare($sql_count)) {
                $stmt_count->bind_param("i", $user_id);
                $stmt_count->execute();
                $result_count = $stmt_count->get_result();
                if ($result_count) {
                    $total_posts = $result_count->fetch_assoc()['total'];
                }
                $stmt_count->close();
            }

            // 3. Obtener todas las publicaciones de ese usuario
            // (Usando las tablas 'publicacion' y 'categorias' de tus imágenes)
            $sql_posts = "SELECT 
                            p.ID_Publicacion,      
                            p.Mundial_Publicacion,
                            p.Titulo_Publicacion, 
                            p.Descripcion_Publicacion, 
                            p.Fecha_Publicacion, 
                            c.Nombre_Categoria
                          FROM publicacion p
                          JOIN categorias c ON p.Categorias_Publicacion = c.ID_Categorias
                          WHERE p.Usuario_Publicacion = ?
                          ORDER BY p.Fecha_Publicacion DESC";
            
            if($stmt_posts = $conexion->prepare($sql_posts)) {
                $stmt_posts->bind_param("i", $user_id);
                $stmt_posts->execute();
                $result_posts = $stmt_posts->get_result();
                
                // Guardar todos los posts en el array
                while ($row_post = $result_posts->fetch_assoc()) {
                    $posts[] = $row_post;
                }
                $stmt_posts->close();
            }
        }
    }

    // ¡NO CIERRES LA CONEXIÓN AQUÍ! Se cierra al final del archivo.
    // $conexion->close(); <-- ESTE ERA EL ERROR PRINCIPAL

    // --- FIN DE LA LÓGICA DEL PERFIL ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
</head>
<body>
    <header>
        <h1>Perfil <i class="fa-solid fa-futbol"></i></h1>
        <?php if ($isLoggedIn): ?>
        <div class="user">
            <img src="MostrarImagen.php" alt="Foto de perfil" height="50" width="50">
            <h3><?php echo htmlspecialchars($username_session); ?></h3>
        </div>
        <?php endif; ?>
        </div>
    </header>
    <div class= "layout">
        <nav class="sidebar">
            <ul> 
                <li>
                    <a href="logout.php"> <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                </li>

                <li><a href="javascript:history.back()" onclick="return true;"><i class="fas fa-undo"></i><span>Volver Atrás</span></a></li>
            <?php if ($userRole == 1): ?>
            <li class="admin-dropdown push-bottom"> 
                <a href="#" class="dropdown-toggle" id="adminToggle"> <i class="fa-solid fa-user-tie"></i> Modo Admin </a>
                <ul>
                    <li><a href="/Pagina/Admin/AprobarP.php"> <i class="fa-solid fa-thumbs-up"></i> Aprobar Publicacion</a></li>
                    <li><a href="/Pagina/Admin/EliminarC.php"> <i class="fa-solid fa-trash"></i>Eliminar Comentario</a></li>
                    <li><a href="/Pagina/Admin/GestionarM.php"> <i class="fa-solid fa-list-check"></i> Gestionar Mundial</a></li>
                    <li><a href="/Pagina/Admin/Mundial.php"> <i class="fa-solid fa-globe"></i> Crear Mundial</a></li>
                </ul>
            </li> 
            <?php endif; ?>
            
            </ul>
        </nav>

        <main class="main-stacked">
            <div class="perfil-completo-misposts">
                <div class="foto-perfil-misposts">
                    <img src="MostrarImagen.php" alt="Foto de perfil" height="50" width="50">
                </div>
                <div class="info-perfil-misposts">
                    <h1><?php echo htmlspecialchars($username_session); ?></h1>
                    
                    <p class="stats-misposts">Total de posts: <?php echo $total_posts; ?></p>
                    <p class="descripcion-misposts">
                        <strong>País de Nacimiento:</strong> <?php echo htmlspecialchars($pais_nacimiento); ?><br>
                        <strong>Nacionalidad:</strong> <?php echo htmlspecialchars($nacionalidad); ?>
                    </p>
                    
                    <button class="btn-editar-perfil" onclick="window.location.href='CrearC.php'"> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Editar Perfil
                    </button>
                </div>
            </div>

            <div class="posts-container">
                <h2>Mis Publicaciones</h2>
                
                <?php if (empty($posts)): ?>
                    <p>Todavía no has creado ninguna publicación.</p>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        
                        <a href="Mostrar_Publicacion.php?id_mundial=<?php echo htmlspecialchars($post['Mundial_Publicacion']); ?>&id_publicacion=<?php echo htmlspecialchars($post['ID_Publicacion']); ?>">
                            <article class="post">
                                <h3><?php echo htmlspecialchars($post['Titulo_Publicacion']); ?></h3>
                                <div class="post-info"> 
                                    <span>Publicado el: <?php echo htmlspecialchars(date("d/m/Y", strtotime($post['Fecha_Publicacion']))); ?></span> • 
                                    <span>Categoría: <?php echo htmlspecialchars($post['Nombre_Categoria']); ?></span>
                                </div>
                                <p><?php echo htmlspecialchars(substr($post['Descripcion_Publicacion'], 0, 150)) . '...'; ?></p>
                            </article>
                        </a>

                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
        </main>
    </div>
    <footer>
        <p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921</p>
    </footer>

    <?php
        // ¡LA CONEXIÓN SE CIERRA AQUÍ, AL FINAL DE TODO!
        $conexion->close(); 
    ?>
</body>
</html>