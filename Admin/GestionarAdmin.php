<?php

    session_start();
    require_once "../Connection.php";

    $isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    $userRole = $isLoggedIn && isset($_SESSION['rol']) ? $_SESSION['rol'] : null;

    if ($isLoggedIn) {
        $username_session = $_SESSION['username'];
    }

    if (isset($_SESSION['error'])){    
        $error = $_SESSION['error'];
        unset($_SESSION['error']);
    }
    
    // 1. DETERMINAR EL MODO PRINCIPAL (CATEGORIA O PAIS)
    $tipo_entidad = isset($_GET['tipo']) && in_array($_GET['tipo'], ['categoria', 'pais']) ? $_GET['tipo'] : null;

    if (!$tipo_entidad) {
        // Redirigir si no se especifica el tipo, o mostrar un error
        $_SESSION['error'] = "Error: Tipo de gestión no especificado.";
        header("Location: GestionarM.php");
        exit();
    }
    
    // 2. CONFIGURACIÓN DINÁMICA DE LA ENTIDAD
    if ($tipo_entidad === 'categoria') {
        $titulo = 'Categorías';
        $tabla = 'categorias';
        $id_columna = 'ID_Categorias';
        $nombre_columna = 'Nombre_Categoria';
        $nombre_singular = 'Categoría';
    } else { // tipo_entidad === 'pais'
        $titulo = 'Países';
        $tabla = 'pais';
        $id_columna = 'ID_Pais';
        $nombre_columna = 'Pais';
        $nombre_singular = 'País';
    }
    
    // Usaremos Categoria.php como el archivo de edición unificado
    $enlace_editor = 'Categoria.php'; 

    // 3. CONSULTA SQL DINÁMICA
    $elementos = [];
    $sql = "SELECT $id_columna, $nombre_columna FROM $tabla ORDER BY $nombre_columna ASC";
    $resultado = $conexion->query($sql);

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $elementos[] = $fila; 
        }
    }

    $conexion->close(); 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar <?php echo $titulo; ?></title>
    <link rel="stylesheet" href="/Pagina/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>

</head>
<body>
    <header>
        <h1>Gestionar <?php echo $titulo; ?> 
        <?php if ($tipo_entidad === 'categoria'): ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#6cd085" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-tag"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7.5 7.5a4.5 4.5 0 1 0 4.5 4.5" /><path d="M13.43 17.97l-3.32 -10.97a1.06 1.06 0 0 1 .47 -1.25l7.98 -4.84a1.06 1.06 0 0 1 1.25 .47l4.84 7.98a1.06 1.06 0 0 1 -.47 1.25l-7.98 4.84a1.06 1.06 0 0 1 -1.25 -.47z" /></svg>
        <?php else: ?>
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
                    <a href="InicioSesion.php">Iniciar Sesión</a>
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
                    </ul>
                </li> 
                <?php endif; ?>
                    
                <li>
                    <a href="MisPosts.php"><i class="fas fa-cog"></i> Perfil</a>
                </li>
            </ul>
        </nav>

    <main>
        <div class="tabla-gestion-container">
            <h2>Gestión de <?php echo $titulo; ?></h2>
            
            <a href="<?php echo $enlace_editor; ?>?tipo=<?php echo $tipo_entidad; ?>" class="btn-crear">
                ➕ Crear Nueva <?php echo $nombre_singular; ?>
            </a>
        
            <?php 
            if (empty($elementos)): 
                ?>
                <p>No hay <?php echo strtolower($titulo); ?> registrados en este momento.</p>
            
            <?php 
            else:
            ?>
                <table class="tabla-gestion">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre de la <?php echo $nombre_singular; ?></th>
                            <?php if ($tipo_entidad !== 'pais'): ?>
                            <th>Acciones (Editar/Eliminar)</th>
                            <?php else: ?>
                            <th>Acciones (Editar)</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($elementos as $elemento): 
                            $id = htmlspecialchars($elemento[$id_columna]);
                            $nombre = htmlspecialchars($elemento[$nombre_columna]);
                            // Enlace al editor unificado (Categoria.php), pasando el tipo y el ID para el modo editar
                            $link_editar = $enlace_editor . "?tipo=" . $tipo_entidad . "&id_" . $tipo_entidad . "=" . $id;
                        ?>
                        <tr>
                            <td><?php echo $id; ?></td>
                            <td><?php echo $nombre; ?></td>
                            <td>
                                <a href="<?php echo $link_editar; ?>" class="btn-accion editar">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                
                                <?php if ($tipo_entidad !== 'pais'): ?>
                                <a href="#" class="btn-accion eliminar" onclick="return confirm('¿Estás seguro de que quieres eliminar <?php echo $nombre; ?>?');">
                                    <i class="fas fa-trash"></i> Eliminar
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            <?php 
            endif;
            ?>
       </div> 
    </main>
</div>

<footer>
<p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921 <p>
</footer>

</body>
</html>