<?php
    session_start();
    require_once "Connection.php";

    $isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

    // --- LÍNEA QUE FALTA ---
    $id_usuario_session = $isLoggedIn && isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;

    $userRole = $isLoggedIn && isset($_SESSION['rol']) ? $_SESSION['rol'] : null;

    // El nombre de usuario se obtiene de la sesión para mayor seguridad y eficiencia.
    if ($isLoggedIn) {
        $username_session = $_SESSION['username'];
    }

    if (isset($_SESSION['error'])){    
        $error = $_SESSION['error'];
        unset($_SESSION['error']);
    }
    $id_mundial = 0; // Inicializamos la variable
    $mundiales = [];
    $categorias = [];

   // CASO 1: Entras desde el menú principal (Dato Encriptado)
    if (isset($_GET['data'])) {
        $id_decodificado = base64_decode($_GET['data']);
        if (is_numeric($id_decodificado)) {
            $id_mundial = intval($id_decodificado);
        }
    } 
    // CASO 2: Usas el buscador o filtros (Dato Numérico directo)
    elseif (isset($_GET['id_mundial'])) {
        if (is_numeric($_GET['id_mundial'])) {
            $id_mundial = intval($_GET['id_mundial']);
        }
    }

    // VALIDACIÓN FINAL: Si después de los dos chequeos no tenemos ID válido, adiós.
    if ($id_mundial === 0) {
        header("Location: Pagina.php"); 
        exit;
    }


    

    //$id_mundial = intval($_GET['id_mundial']);

    $sql = "SELECT U.Nombre_Usuario, M.ID_Mundial, M.Nombre_Torneo, M.Anio, M.Sede_Pais, M.Imagen_URL, M.Descripcion_Mundial 
        FROM mundiales M
        INNER JOIN usuarios U ON M.FK_Usuario = U.ID
        WHERE M.ID_Mundial = ?"; // <--- CLÁUSULA WHERE AGREGADA // Ajusté nombres de tablas y columnas según tus imágenes
    $sql2="SELECT ID_Categorias, Nombre_Categoria FROM categorias";

    if ($stmt = $conexion->prepare($sql)) {
        // Ligamos el ID
        $stmt->bind_param("i", $id_mundial);
        // Ejecutamos
        $stmt->execute();
        // Obtenemos el resultado
        $resultado = $stmt->get_result(); 
    } else {
        // Manejo de error si la preparación falla
        die("Error al preparar la consulta del mundial: " . $conexion->error); 
    }

    $resultado2= $conexion->query($sql2);

    // 1. Procesar Mundiales
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $mundiales[] = $fila;

        }

        $stmt->close();
    }

    // 2. Procesar Categorías
    if ($resultado2) {
        while ($fila2 = $resultado2->fetch_assoc()) {
            $categorias[] = $fila2;
        }
        // Es buena práctica liberar la memoria del segundo resultado
        $resultado2->free();
    }

// ... (Código anterior)

    // 1. Verificar filtros (Categoría y Búsqueda)
    $id_categoria_seleccionada = isset($_GET['id_categoria']) ? intval($_GET['id_categoria']) : null;
    $busqueda = isset($_GET['Busqueda']) ? $_GET['Busqueda'] : null;

    $publicaciones_aprobadas = [];

    // 2. Construimos la consulta base
    $sql_posts = "SELECT p.ID_Publicacion, p.Titulo_Publicacion, p.Descripcion_Publicacion, p.Multimedia_Publicacion,
                        u.Nombre_Usuario, c.Nombre_Categoria, p.Fecha_Aprobacion
                FROM publicacion p
                JOIN usuarios u ON p.Usuario_Publicacion = u.ID
                JOIN categorias c ON p.Categorias_Publicacion = c.ID_Categorias
                WHERE p.Estatus = 1 AND p.Mundial_Publicacion = ?";

    // 3. Lógica dinámica para agregar condiciones
    $types = "i";             // Tipos de datos para bind_param (inicia con 'i' por id_mundial)
    $params = [$id_mundial];   // Array de parámetros (inicia con el valor de id_mundial)

    // Si hay categoría seleccionada
    if ($id_categoria_seleccionada) {
        $sql_posts .= " AND p.Categorias_Publicacion = ?";
        $types .= "i";
        $params[] = $id_categoria_seleccionada;
    }

    // Si hay texto en la búsqueda
    if ($busqueda) {
        // Buscamos coincidencias en el Título O en la Descripción
        $sql_posts .= " AND (p.Titulo_Publicacion LIKE ? OR p.Descripcion_Publicacion LIKE ?)";
        $types .= "ss"; // Dos strings
        $param_busqueda = "%" . $busqueda . "%"; // Los % hacen que busque texto parcial
        $params[] = $param_busqueda;
        $params[] = $param_busqueda;
    }

    $sql_posts .= " ORDER BY p.Fecha_Aprobacion DESC";

    // 4. Ejecución de la consulta preparada dinámica
    if ($stmt_posts = $conexion->prepare($sql_posts)) {
        
        // Usamos bind_param dinámicamente con los arrays que creamos arriba
        $stmt_posts->bind_param($types, ...$params);

        $stmt_posts->execute();
        $resultado_posts = $stmt_posts->get_result();
        
        while ($fila_post = $resultado_posts->fetch_assoc()) {
            $publicaciones_aprobadas[] = $fila_post;
        }
        $stmt_posts->close();
    }
    
    $posts_likeados_por_usuario = [];

    // 2. Solo ejecutamos esta consulta si el usuario ESTÁ logueado
    if ($isLoggedIn && $id_usuario_session !== null) {
        
        // 3. Consultamos la tabla 'publicacion_like'
        $sql_likes_check = "SELECT Publicacion_Like FROM publicacion_like WHERE Usuario_Like = ?";
        
        if ($stmt_likes = $conexion->prepare($sql_likes_check)) {
            $stmt_likes->bind_param("i", $id_usuario_session);
            $stmt_likes->execute();
            $res_likes = $stmt_likes->get_result();
            
            // 4. Guardamos todos los IDs en un array simple (Ej: [1, 5, 12])
            // Usamos array_column para tomar solo la columna 'Publicacion_Like'
            $posts_likeados_por_usuario = array_column($res_likes->fetch_all(MYSQLI_ASSOC), 'Publicacion_Like');
            
            $stmt_likes->close();
        }
    }

    $conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Principal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    
    <style>
        /* Tus estilos están bien */
        .mundial-section {
            border-bottom: 3px solid #333;
            margin-bottom: 25px;
            padding-bottom: 15px;
        }
        .post-preview h3 {
            margin-top: 0;
        }
        .post-preview p {
            margin: 10px 0 0 20px; /* Estilo para la lista de jugadores */
        }
    </style>
</head>
<body>
<header>
    <h1>Mundial <i class="fa-solid fa-futbol"></i></h1>
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
            <li> 
                <a href="#"><i class="fa-solid fa-ellipsis-vertical"></i> Categorías</a>
                             <?php 
            if (empty($categorias)): 
            ?>
                <p>No hay categorias para mostrar en este momento.</p>

            <?php 
            else:
            ?>
            <ul>
                <?php foreach ($categorias as $categoria): ?>
                    <li>
                        <a href="Publicaciones.php?id_mundial=<?php echo $id_mundial; ?>&id_categoria=<?php echo $categoria['ID_Categorias']; ?>">
                            <i class="fa-solid fa-bars"></i>
                            <?php echo htmlspecialchars($categoria['Nombre_Categoria']); ?>
                        </a>
                    </li> 
                <?php endforeach; ?>
            </ul>
           <?php
            endif;
            ?>
            <li><a href="Pagina.php"><i class="fas fa-home"></i><span>Ir al Inicio</span></a></li>
            <li><a href="javascript:history.back()" onclick="return true;"><i class="fas fa-undo"></i><span>Volver Atrás</span></a></li>
            </li>
           <?php if ($isLoggedIn && !empty($mundiales)): ?>
            <li>
                <a href="logout.php"> <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
            </li>
            <li>
                <a href="Publicacion.php?data=<?= base64_encode($mundiales[0]['ID_Mundial']); ?>">
                    <i class="fa-solid fa-plus"></i> Crear Publicación
                </a>
            </li>
            <?php else: ?>
            <li>
                <a href="InicioSesion.php">Iniciar Sesión</a>
            </li>
            <?php endif; ?>

            <!-- Bloque con desplegable de admin -->

            <?php if ($userRole == 1): ?>
            <li class="admin-dropdown"> <a href="#" class="dropdown-toggle" id="adminToggle"> <i class="fa-solid fa-user-tie"></i> Modo Admin </a>
                <ul>
                    <li><a href="/Pagina/Admin/AprobarP.php"> <i class="fa-solid fa-thumbs-up"></i> Aprobar Publicacion</a></li>
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
            <li class="push-bottom"> 
                <a href="MisPosts.php"><i class="fas fa-cog"></i> Perfil</a>
            </li>
        </ul>
    </nav>
        <main>
            <div class="main-search-container">
                <form action="" method="GET">
                    <div class="search-box">
                        <input type="hidden" name="id_mundial" value="<?php echo $id_mundial; ?>">
                        
                        <input type="text" name="Busqueda" placeholder="Buscar en este mundial..." 
                            value="<?php echo isset($_GET['Busqueda']) ? htmlspecialchars($_GET['Busqueda']) : ''; ?>">
                        
                        <button type="submit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-zoom"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
                        </button>
                    </div>
                </form>
            </div>
            <div class="publicacion">
                <div class="publicaciones-feed">
                    <?php 
                    // Si el array está vacío, significa que el ID no existe en la BD
                    if (empty($mundiales)): 
                        // --- NO HAY MUNDIAL ---
                    ?>
                        <p>El mundial solicitado no existe o no se pudo cargar.</p>
                    
                    <?php 
                    else:
                        // --- SÍ HAY UN MUNDIAL, USAMOS EL PRIMER ELEMENTO ---
                        $mundial         = $mundiales[0]; // Solo habrá uno
                        $ID_Mundial      = htmlspecialchars($mundial['ID_Mundial']);
                        $Nombre_Torneo   = htmlspecialchars($mundial['Nombre_Torneo']);
                        $Anio            = htmlspecialchars($mundial['Anio']); // Puedes añadir más campos
                        $Sede_Pais       = htmlspecialchars($mundial['Sede_Pais']);
                        $Nombre_Usuario  = htmlspecialchars($mundial['Nombre_Usuario']);
                        
                        $ruta_limpia     = ltrim($mundial['Imagen_URL'], '/');
                        $Imagen_URL      = htmlspecialchars("/" . $ruta_limpia);
                        
                        // Aquí usamos la descripción completa (o formateada para la vista completa)
                        $Descripcion_Mundial = htmlspecialchars($mundial['Descripcion_Mundial']); 
                        // --- INICIO INTEGRACIÓN API FÚTBOL (DIRECTA API-SPORTS) ---
                        
                        $api_datos = null; 
                        $mostrar_datos_api = false;

                        if (!empty($Anio)) {
                            
                            $curl = curl_init();
                            curl_setopt_array($curl, [
                                // La URL sigue siendo la misma v3
                                CURLOPT_URL => "https://v3.football.api-sports.io/fixtures?league=1&season=" . $Anio,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => "",
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 30,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => "GET",
                                CURLOPT_HTTPHEADER => [
                                    // EL CAMBIO IMPORTANTE ESTÁ AQUÍ:
                                    // Ya no usamos 'x-rapidapi-key', ahora usamos 'x-apisports-key'
                                    "x-apisports-key: 2c26dcf815d2610b2cf969cb40175d27" 
                                ],
                            ]);

                            $response_api = curl_exec($curl);
                            $err_api = curl_error($curl);
                            curl_close($curl);

                            if (!$err_api) {
                                $data_decoded = json_decode($response_api, true);
                                
                                // Verificar si hay errores en la respuesta de la API (ej. límite excedido)
                                if (isset($data_decoded['errors']) && !empty($data_decoded['errors'])) {
                                    // Opcional: Descomenta esto si quieres ver errores en pantalla mientras pruebas
                                    // var_dump($data_decoded['errors']);
                                }
                                
                                if (isset($data_decoded['response']) && !empty($data_decoded['response'])) {
                                    $api_datos = $data_decoded['response'];
                                    $mostrar_datos_api = true;
                                }
                            }
                        }
                        // --- FIN INTEGRACIÓN API ---
                    ?>
                        <article class="post-full"> 
                            <h2><?php echo $Nombre_Torneo; ?> (<?php echo $Anio; ?>)</h2>
                            
                            <div class="post-info-full"> 
                                <span>Sede: <strong> <?php echo $Sede_Pais; ?> </strong> </span>
                                <span> | </span>
                                <span>Creador: <strong> <?php echo $Nombre_Usuario; ?> </strong> </span>
                            </div>
                            
                            <div class="full-content">
                                
                                <?php if (!empty($Imagen_URL)): ?>
                                    
                                    <div class="post-image-full-container">
                                        <img src="<?= $Imagen_URL ?>" alt="Mundial: <?php echo $Nombre_Torneo; ?>">                             
                                    </div>
                                <?php endif; ?>

                                <p><?php echo nl2br($Descripcion_Mundial); ?></p> 

                                <?php if ($mostrar_datos_api): ?>
                                    <div class="api-results-container">
                                        
                                        <div class="api-header">
                                            <i class="fa-solid fa-futbol" style="color: #6cd085; font-size: 1.2rem;"></i>
                                            <h3>Resultados del Torneo</h3>
                                            <span class="api-badge">EN VIVO API</span>
                                        </div>
                                        
                                        <div class="api-list">
                                            <?php foreach ($api_datos as $partido): ?>
                                                <div class="match-card">
                                                    
                                                    <div class="match-teams">
                                                        <span style="flex: 1; text-align: right;"><?php echo $partido['teams']['home']['name']; ?></span>
                                                        
                                                        <div class="match-score">
                                                            <?php echo $partido['goals']['home'] ?? 0; ?> - <?php echo $partido['goals']['away'] ?? 0; ?>
                                                        </div>
                                                        
                                                        <span style="flex: 1; text-align: left;"><?php echo $partido['teams']['away']['name']; ?></span>
                                                    </div>

                                                    <div class="match-details">
                                                        <span><i class="fa-regular fa-calendar"></i> <?php echo date("d/m/Y", strtotime($partido['fixture']['date'])); ?></span>
                                                        <span><i class="fa-solid fa-location-dot"></i> <?php echo $partido['fixture']['venue']['name']; ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                        </article>
                    <?php 
                    endif; // <-- Termina el if/else
                    ?>
                </div>
                <br>
                <div class="publicaciones-feed">
                    <h2>Publicaciones de este Mundial</h2>
                    <hr>

                    <?php if (empty($publicaciones_aprobadas)): ?>
                        
                        <h4>Aún no hay publicaciones aprobadas para este mundial y/o no hay publicaciones de esa categoria.</h4>
                    
                    <?php else: ?>
                        
                        <?php foreach ($publicaciones_aprobadas as $pub): ?>
                            <div class="publicacion-card">
                                <h2><?php echo htmlspecialchars($pub['Titulo_Publicacion']); ?></h2>
                                <div class="info"> 
                                    <span><strong>Autor:</strong> <?php echo htmlspecialchars($pub['Nombre_Usuario']); ?></span>
                                    <span><strong>Categoría:</strong> <?php echo htmlspecialchars($pub['Nombre_Categoria']); ?></span>
                                    <span><strong>Publicado:</strong> <?php echo htmlspecialchars(date("d/m/Y", strtotime($pub['Fecha_Aprobacion']))); ?></span>
                                </div>

                                <p><?php echo nl2br(htmlspecialchars($pub['Descripcion_Publicacion'])); ?></p>

                                <?php if (!empty($pub['Multimedia_Publicacion'])): ?>
                                    <?php
                                        $multimedia_url = htmlspecialchars($pub['Multimedia_Publicacion']);
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

                                <div class="acciones">

                                    <?php
                                        // 1. Guardamos el ID del post actual
                                        $id_de_este_post = $pub['ID_Publicacion'];
                                        
                                        // 2. Verificamos si este ID está en nuestro array de likes
                                        $ya_likeado = $isLoggedIn && in_array($id_de_este_post, $posts_likeados_por_usuario);
                                    ?>

                                    <button class="btn-like" 
                                            data-id="<?php echo $id_de_este_post; ?>"
                                            
                                            <?php 
                                            // 3. Si ya tiene like, añadimos el estilo rojo y lo deshabilitamos
                                            if ($ya_likeado) echo 'style="color: #e11d48;" disabled'; 
                                            ?>
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3.27 1.25-4 2.7-1.11-1.69-2.92-3.04-5-3.24A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                                        
                                        <?php 
                                        // 4. Cambiamos el texto del botón
                                        if ($ya_likeado): 
                                        ?>
                                            Te gusta
                                        <?php else: ?>
                                            Like
                                        <?php endif; ?>
                                    </button>

                                    <a href="Mostrar_Publicacion.php?token=<?php echo base64_encode($pub['ID_Publicacion']); ?>" class="btn-comentar">
                                        Comentar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>
            </div>

            
        </main>
</div> 
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($error)) : ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Acceso denegado',
    text: <?= json_encode($error) ?>,
    confirmButtonColor: '#d33'
});
</script>
<?php endif; ?>

<script>
    // 1. Pasamos la variable de PHP a JavaScript para saber si el usuario está logueado
    const isUserLoggedIn = <?php echo json_encode($isLoggedIn); ?>;

    // 2. Seleccionamos TODOS los botones que tengan la clase .btn-like
    const likeButtons = document.querySelectorAll('.btn-like');

    // 3. Añadimos un "escuchador" de eventos a CADA botón
    likeButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            
            // --- REQUISITO 2: Verificar si el usuario está logueado ---
            if (!isUserLoggedIn) {
                // Si NO está logueado, mostramos la alerta que pediste
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción Requerida',
                    // Este es el texto exacto que solicitaste
                    text: 'Crea una cuenta para darle me gusta a la publicacion',
                    confirmButtonText: 'Entendido',
                    footer: '<a href="InicioSesion.php">Inicia Sesión o Regístrate</a>'
                });
            } else {
                // --- REQUISITO 1: Si está logueado, procesamos el INSERT ---
                
                // Obtenemos el ID de la publicación desde el atributo 'data-id' del botón
                const postId = this.dataset.id; 
                const likeButton = this; // Guardamos una referencia al botón

                // Usamos fetch (AJAX) para enviar el ID al servidor
                fetch('procesar_like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    // Enviamos el ID del post en el cuerpo de la solicitud
                    body: 'post_id=' + postId 
                })
                .then(response => response.json()) // Esperamos una respuesta en formato JSON
                .then(data => {
                    // 'data' es la respuesta de 'procesar_like.php'
                    if (data.success) {
                        // El like fue exitoso
                        Swal.fire({
                            icon: 'success',
                            title: data.message,
                            timer: 1500, // La alerta se cierra sola
                            showConfirmButton: false
                        });
                        
                        // Opcional: Cambiamos visualmente el botón
                        likeButton.style.color = '#e11d48'; // Color rojo
                        likeButton.innerHTML = likeButton.innerHTML.replace('Like', 'Te gusta');
                        likeButton.disabled = true; // Deshabilitamos el botón para evitar doble clic

                    } else {
                        // El like falló (ej. ya le había dado like)
                        Swal.fire({
                            icon: 'error',
                            title: data.message
                        });
                    }
                })
                .catch(error => {
                    // Error de conexión
                    console.error('Error en fetch:', error);
                    Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                });
            }
        });
    });
</script>


<footer>
    <p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921 <p>
</footer>
</body>
</html>