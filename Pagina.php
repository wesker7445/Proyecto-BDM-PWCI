<?php
// Iniciar la sesión al principio de todo
session_start();

// Verificar si el usuario está logueado, si no, redirigirlo a la página de login
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

// El nombre de usuario se obtiene de la sesión para mayor seguridad y eficiencia.
if ($isLoggedIn) {
    $username_session = $_SESSION['username'];
}

if (isset($_SESSION['error'])){    
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

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
</head>
<body>
<header>
    <h1>Feed <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#6cd085" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-ball-football"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7l4.76 3.45l-1.76 5.55h-6l-1.76 -5.55z" /><path d="M12 7v-4m3 13l2.5 3m-.74 -8.55l3.74 -1.45m-11.44 7.05l-2.56 2.95m.74 -8.55l-3.74 -1.45" /></svg></h1>
    <?php if ($isLoggedIn): ?>
    <div class="user">
        <!-- La imagen ahora apunta a un script PHP que la sirve desde la BD -->
        <img src="MostrarImagen.php" alt="Foto de perfil" height="50" width="50">
        <!-- El nombre se obtiene de la variable de sesión -->
        <a href="MisPosts.php"><h3><?php echo htmlspecialchars($username_session); ?></h3></a>
        <a href="Admin.php"><h3>Modo admin</h3></a>
    </div>
    <?php endif; ?>
</header>
<div class= "layout">
    <nav class="sidebar">
        <ul>
            <li class="container"> 
                <form action="Noticias.php">
                    <div class="search-box">
                        <input type="text" name="Busqueda" placeholder="Buscar...">
                        <button><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-zoom"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg></button>
                    </div>
                </form>
            </li>
            <li> 
                <a href="#">Categorías</a>
                <ul>
                    <li><a href="#">Faltas</a></li>
                    <li><a href="#">Golres</a></li>
                    <li><a href="#">Controversia</a></li>
                    <li><a href="#">Jugadas</a></li>
                    </ul>
            </li>
            <?php if ($isLoggedIn): ?>
            <li><a href="logout.php">Cerrar Sesión</a></li>
            <li><a href="CrearP.php">Crear Publicación</a></li>
            <?php else: ?>
            <li><a href="InicioSesion.php">Iniciar Sesión</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="publicacion">
        <a href="Publicacion.php">
            <article class="post-preview">
                <h3>Los 5 Momentos Inolvidables del Mundial de 2014</h3>
                <div class="post-info-preview"> 
                    <span>Publicado el: <strong>20 de octubre de 2025</strong> </span> • 
                    <span>Categoría: <strong>Goles</strong> </span> • 
                    <span>Creado por <strong> Autor Real del Post </strong> </span>
                </div>
                <p>El Mundial de Brasil 2014 nos dejó jugadas para la historia y emociones a flor de piel. Acompáñanos a revivir los cinco momentos que definieron este torneo...</p>
            </article>
        </a>
        <a href="Publicacion.php">
            <article class="post-preview">
                <h3>¡La gran final ha llegado y entre dos potencias del futbol!</h3>
                <div class="post-info-preview"> 
                    <span>Publicado el: <strong>20 de octubre de 2025</strong> </span> • 
                    <span>Categoría: <strong>Partidos</strong> </span> • 
                    <span>Creado por <strong>Autor Real del Post</strong> </span>
                </div>
                <p>Argentina, liderada por el icónico Lionel Messi, busca su tercer título mundial después de 36 años...</p>
            </article>
        </a>

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
</div>
</main>
<footer>
    <p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921 <p>
</footer>
</body>
</html>