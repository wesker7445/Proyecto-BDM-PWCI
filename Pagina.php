<?php
    session_start();
    require_once "Connection.php";

    $isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

    $userRole = $isLoggedIn && isset($_SESSION['rol']) ? $_SESSION['rol'] : null;

    // El nombre de usuario se obtiene de la sesión para mayor seguridad y eficiencia.
    if ($isLoggedIn) {
        $username_session = $_SESSION['username'];
    }

    if (isset($_SESSION['error'])){    
        $error = $_SESSION['error'];
        unset($_SESSION['error']);
    }

    
    $mundiales = [];

    $sql = "SELECT U.Nombre_Usuario, M.ID_Mundial, M.Nombre_Torneo, M.Anio, M.Sede_Pais, M.Imagen_URL, M.Descripcion_Mundial, M.Imagen_URL 
        FROM mundiales M
        INNER JOIN usuarios U ON M.FK_Usuario = U.ID"; // Ajusté nombres de tablas y columnas según tus imágenes
    $resultado = $conexion->query($sql);

// 1. Procesar Mundiales
if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $mundiales[] = $fila;
    }
    // Es buena práctica liberar la memoria del primer resultado
    $resultado->free(); 
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
    <h1>Feed <i class="fa-solid fa-futbol"></i></h1>
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
            <?php if ($isLoggedIn): ?>
            <li>
                <a href="logout.php"> <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
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
        <div class="publicacion">
                <?php 
                if (empty($mundiales)): 
                    // --- NO HAY MUNDIALES ---
                ?>
                    <p>No hay mundiales para mostrar en este momento.</p>
                
                <?php 
                else:
                    // --- SÍ HAY MUNDIALES, INICIA EL BUCLE ---
                    foreach ($mundiales as $mundial): 

                        // 💡 CONSEJO: Asignamos variables para legibilidad (como en tu otro proyecto)
                        // ¡¡Usamos $mundial (singular) porque es la variable del bucle!!
                        
                        $ID_Mundial      = htmlspecialchars($mundial['ID_Mundial']);
                        $Nombre_Torneo   = htmlspecialchars($mundial['Nombre_Torneo']);
                        $Sede_Pais       = htmlspecialchars($mundial['Sede_Pais']);
                        $Nombre_Usuario  = htmlspecialchars($mundial['Nombre_Usuario']);
                        
                        // --- ESTA ES LA LÍNEA MÁGICA ---
                        // Confiamos en la BD, igual que el Proyecto 1 (que usa $multimedia)
                        $ruta_limpia = ltrim($mundial['Imagen_URL'], '/');

                        // 2. Añade UNA SOLA barra al inicio.
                        //    Ahora estamos 100% seguros de que la ruta es "/Pagina/..."
                        $Imagen_URL = htmlspecialchars("/" . $ruta_limpia);
                        
                        // Procesamos la descripción (como ya hacías)
                        $descripcion_corta = htmlspecialchars(substr($mundial['Descripcion_Mundial'], 0, 279));
                        if (strlen($mundial['Descripcion_Mundial']) > 279) {
                            $descripcion_corta .= "..."; 
                        }
                ?>
                    <a href="Publicaciones.php?data=<?php echo base64_encode($ID_Mundial); ?>">
                            <article class="post-preview"> 
                                
                                <h3><?php echo $Nombre_Torneo; ?> (<?php echo $Sede_Pais; ?>)</h3>
                                
                                <div class="post-info-preview"> 
                                    <span>Sede: <strong> <?php echo $Sede_Pais; ?> </strong> </span>
                                    <span> | </span>
                                    <span>Creador: <strong> <?php echo $Nombre_Usuario; ?> </strong> </span>
                                </div>
                                
                                <div class="preview-content">
                                    
                                    <?php if (!empty($Imagen_URL)): // Usamos nuestra variable limpia ?>
                                        
                                        <div class="post-image-container">
                                            
                                            <img src="<?= $Imagen_URL ?>" alt="Mundial">
                                        </div>
                                    <?php endif; ?>

                                    <p><?php echo $descripcion_corta; ?></p> 
                                </div>
                                
                            </article>
                        </a>
                <?php 
                    endforeach; // <-- Termina el bucle
                endif; // <-- Termina el if/else
                ?>
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

<footer>
    <p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921 <p>
</footer>
</body>
</html>