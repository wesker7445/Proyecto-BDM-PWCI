<?php 
    session_start();
    require_once "../Connection.php"; // Incluimos la conexión

    // 1. VERIFICACIÓN DE PERMISOS (ADMIN)
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
        $_SESSION['error'] = "No tienes permiso para realizar esta acción.";
        // Si ya está en AprobarP.php, no redirigir a sí mismo, mejor al inicio
        header("Location: /Pagina/Pagina.php"); 
        exit;
    }

    // Verificamos que la conexión exista
    if (!isset($conexion)) {
        $_SESSION['error'] = "Error de conexión a la base de datos.";
        header("Location: /Pagina/Pagina.php");
        exit;
    }

    // --- NUEVO: MANEJAR ACCIÓN DE ELIMINAR (SI SE ENVIÓ UN POST) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_comentario']) && isset($_POST['accion']) && $_POST['accion'] == 'eliminar') {
        
        $id_comentario_a_eliminar = intval($_POST['id_comentario']);
        
        $sql_delete = "DELETE FROM comentarios WHERE ID_Comentario = ?";
        
        if ($stmt_delete = $conexion->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $id_comentario_a_eliminar);
            if ($stmt_delete->execute()) {
                $_SESSION['success'] = "Comentario eliminado correctamente.";
            } else {
                $_SESSION['error'] = "Error al eliminar el comentario.";
            }
            $stmt_delete->close();
        } else {
            $_SESSION['error'] = "Error al preparar la eliminación.";
        }
        
        // Redirigir a la misma página para evitar reenvío de formulario
        header("Location: EliminarC.php");
        exit;
    }
    // --- FIN DEL MANEJADOR POST ---


    // Información de sesión para la cabecera
    $isLoggedIn = true; // Sabemos que está logueado por el check de arriba
    $username_session = $_SESSION['username'];
    $userRole = $_SESSION['rol'];


    // --- NUEVO: OBTENER TODOS LOS COMENTARIOS Y MANEJAR BÚSQUEDA ---
    $comentarios_para_moderar = [];
    $busqueda = isset($_GET['Busqueda']) ? $_GET['Busqueda'] : null;
    $params = [];
    $types = "";

    // Consulta base (JOINs para obtener la información necesaria)
    $sql_get_comentarios = "SELECT 
                              c.ID_Comentario, 
                              c.Comentario, 
                              c.Fecha_Comentario, 
                              u.Nombre_Usuario, 
                              p.Titulo_Publicacion, 
                              cat.Nombre_Categoria
                          FROM comentarios c
                          JOIN usuarios u ON c.Usuario_Comentario = u.ID
                          JOIN publicacion p ON c.Publicacion_Comentario = p.ID_Publicacion
                          JOIN categorias cat ON p.Categorias_Publicacion = cat.ID_Categorias
                          WHERE 1=1"; // Cláusula "siempre verdadera" para facilitar la adición de condiciones

    // Lógica dinámica para agregar la condición de búsqueda
    if ($busqueda) {
        // Buscamos coincidencias en el comentario, el usuario o el título de la publicación
        $sql_get_comentarios .= " AND (c.Comentario LIKE ? OR u.Nombre_Usuario LIKE ? OR p.Titulo_Publicacion LIKE ?)";
        $types .= "sss"; // Tres strings
        $param_busqueda = "%" . $busqueda . "%"; // Los % permiten buscar texto parcial/literal
        $params[] = $param_busqueda;
        $params[] = $param_busqueda;
        $params[] = $param_busqueda;
    }

    $sql_get_comentarios .= " ORDER BY c.Fecha_Comentario DESC"; // Los más nuevos primero

    // 2. Ejecución de la consulta preparada dinámica
    if ($stmt = $conexion->prepare($sql_get_comentarios)) {
        
        // Ejecución con parámetros si hay búsqueda
        if ($busqueda) {
            $stmt->bind_param($types, ...$params); 
        }

        $stmt->execute();
        $resultado = $stmt->get_result();

        while ($fila = $resultado->fetch_assoc()) {
            $comentarios_para_moderar[] = $fila;
        }
        $stmt->close();
    } else {
        $_SESSION['error_fetch'] = "Error al consultar los comentarios: " . $conexion->error;
    }
    // --- FIN DE LA NUEVA LÓGICA DE OBTENCIÓN Y BÚSQUEDA ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Comentario</title> <link rel="stylesheet" href="/Pagina/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
</head>
<body>
<header>
    <h1>Eliminar Comentario <i class="fa-solid fa-futbol"></i></h1>
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

            <li>
                <a href="../logout.php"> <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
            </li>

            <li class="admin-dropdown push-bottom"> 
                <a href="#" class="dropdown-toggle" id="adminToggle"> <i class="fa-solid fa-user-tie"></i> Modo Admin </a>
                <ul>
                    <li><a href="/Pagina/Admin/AprobarP.php"> <i class="fa-solid fa-thumbs-up"></i> Aprobar Publicacion</a></li>
                    <li><a href="/Pagina/Admin/GestionarM.php"> <i class="fa-solid fa-list-check"></i> Gestionar Mundial</a></li>
                    <li><a href="/Pagina/Admin/Mundial.php"> <i class="fa-solid fa-globe"></i> Crear Mundial</a></li>
                </ul>
            </li> 
                 
            <li>
                <a href="MisPosts.php"><i class="fas fa-cog"></i> Perfil</a>
            </li>
        </ul>
    </nav>

    <main>
        <div class="main-search-container">
            <form action="" method="GET"> <div class="search-box">
                    <input type="text" name="Busqueda" placeholder="Buscar en comentarios..."
                        value="<?php echo isset($_GET['Busqueda']) ? htmlspecialchars($_GET['Busqueda']) : ''; ?>"> <button type="submit"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-zoom"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
                    </button>
                </div>
            </form>
        </div>
        <div class="admin-content">
            <h1>Gestionar Comentarios</h1>

            <?php if (!empty($fetch_error)): ?>
                
                <div class="Comentario-card"> <div class="Header">
                        <h1>Error al cargar comentarios</h1>
                    </div>
                    <div class="content">
                        <p style="color: red;"><?php echo htmlspecialchars($fetch_error); ?></p>
                    </div>
                </div>

            <?php elseif (empty($comentarios_para_moderar)): ?>

                <div class="Comentario-card"> <div class="Header">
                        <h1>No hay comentarios.</h1>
                    </div>
                    <div class="content">
                        <p>No se encontraron comentarios en la base de datos.</p>
                    </div>
                </div>

            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Comentario</th>
                            <th>En Publicación</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comentarios_para_moderar as $com): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($com['Nombre_Usuario']); ?></td>
                                
                                <td class="comment-text">
                                    <?php echo nl2br(htmlspecialchars($com['Comentario'])); ?>
                                </td>
                                
                                <td><?php echo htmlspecialchars($com['Titulo_Publicacion']); ?></td>
                                
                                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($com['Fecha_Comentario']))); ?></td>

                                <td><?php echo htmlspecialchars(date("H:i", strtotime($com['Fecha_Comentario']))); ?></td>
                                
                                <td class="actions-cell">
                                    <form action="EliminarC.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="id_comentario" value="<?php echo $com['ID_Comentario']; ?>">
                                        <button type="submit" name="accion" value="eliminar" class="btn-noadmitir">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </div> 
    </main>
</div>
<footer>
<p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921 <p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($session_error)) : ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Error',
    text: <?= json_encode($session_error) ?>,
    confirmButtonColor: '#d33'
});
</script>
<?php endif; ?>

<?php if (isset($session_success)) : ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Éxito',
    text: <?= json_encode($session_success) ?>,
    confirmButtonColor: '#3085d6'
});
</script>
<?php endif; ?>

</body>
</html>