<?php

    session_start();
    require_once "../Connection.php";

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

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $mundiales[] = $fila; // Almacena cada mundial en el array
        }
    }

    $conexion->close(); 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina Principal</title>
    <link rel="stylesheet" href="/Pagina/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>


</head>
<body>
    <header>
        <h1>Gestionar Mundial <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#6cd085" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-ball-football"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7l4.76 3.45l-1.76 5.55h-6l-1.76 -5.55z" /><path d="M12 7v-4m3 13l2.5 3m-.74 -8.55l3.74 -1.45m-11.44 7.05l-2.56 2.95m.74 -8.55l-3.74 -1.45" /></svg></h1>
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
                    <a href="InicioSesion.php">Iniciar Sesión</a>
                </li>
                <?php endif; ?>

                <!-- Bloque con desplegable de admin -->
                <?php if ($userRole == 1): ?>
                <li class="admin-dropdown push-bottom"> 
                    <a href="#" class="dropdown-toggle" id="adminToggle"> <i class="fa-solid fa-user-tie"></i> Modo Admin </a>
                    <ul>
                        <li><a href="/Pagina/Admin/GestionarAdmin.php?tipo=categoria"><i class="fa-solid fa-tag"></i> Gestionar Categorías</a></li>
                        <li><a href="/Pagina/Admin/GestionarAdmin.php?tipo=pais"><i class="fa-solid fa-flag"></i> Gestionar Países</a></li>
                        <li><a href="/Pagina/Admin/AprobarP.php"> <i class="fa-solid fa-thumbs-up"></i> Aprobar Publicacion</a></li>
                        <li><a href="/Pagina/Admin/EliminarC.php"> <i class="fa-solid fa-trash"></i>Eliminar Comentario</a></li>
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
            <div class="tarjetas-mundial">
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
                    <a href="Mundial.php?id_mundial=<?php echo $ID_Mundial; ?>">
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

<footer>
<p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921 <p>
</footer>

</body>
</html>

