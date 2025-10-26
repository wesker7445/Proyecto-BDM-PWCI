<?php 

// Iniciar la sesión al principio de todo
session_start();

// Verificar si el usuario está logueado, si no, redirigirlo a la página de login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: InicioSesion.php");
    exit;
}

// El nombre de usuario se obtiene de la sesión para mayor seguridad y eficiencia.
$username_session = $_SESSION['username'];

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
</head>
<body>
    <header>
        <h1>Mi Perfil <svg  xmlns="http://www.w3.org/2000/svg"  width="30"  height="30"  viewBox="0 0 24 24"  fill="none"  stroke="#6cd085"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-ball-football"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7l4.76 3.45l-1.76 5.55h-6l-1.76 -5.55z" /><path d="M12 7v-4m3 13l2.5 3m-.74 -8.55l3.74 -1.45m-11.44 7.05l-2.56 2.95m.74 -8.55l-3.74 -1.45" /></svg></h1>
        <div class="user">
            <img src="MostrarImagen.php" alt="Foto de perfil" height="50" width="50">
            <a href="MisPosts.php" ><h3><?php echo htmlspecialchars($username_session); ?></h3></a>
            <a href="Admin.php"><h3>Modo admin</h3></a>
        </div>
    </header>
    <div class= "layout">
        <nav class="sidebar">
            <ul> 
                <li><a href="logout.php">Cerrar Sesión</a></li>
                <li><a href="CrearP.php">Crear Publicacion</a></li>
                <li><a href="Pagina.php">Menu Principal</a></li>
            </ul>
        </nav>

        <main class="main-stacked">
            <div class="perfil-completo-misposts">
                <div class="foto-perfil-misposts">
                    <img src="MostrarImagen.php" alt="Foto de perfil" height="50" width="50">
                </div>
                <div class="info-perfil-misposts">
                    <h1><?php echo htmlspecialchars($username_session); ?></h1>
                    <p class="stats-misposts">Total de posts: 3</p>
                    <p class="descripcion-misposts">Apasionado del fútbol y los mundiales</p>
                    <button class="btn-editar-perfil" onclick="window.location.href='CrearC.php'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Editar Perfil
                    </button>
                </div>
            </div>

            <div class="posts-container">
                <h2>Mis Publicaciones</h2>
                <a href="Publicacion.php">
                    <article class="post">
                        <h3>Los 5 Momentos Inolvidables del Mundial de 2014</h3>
                        <div class="post-info"> 
                            <span>Publicado el: 17 de septiembre de 2025</span> • 
                            <span>Categoría: Goles</span>
                        </div>
                        <p>El Mundial de Brasil 2014 nos dejó jugadas para la historia y emociones a flor de piel. Acompáñanos a revivir los cinco momentos que definieron este torneo...</p>
                    </article>
                </a>

            <a href="Publicacion.php">
                <article class="post">
                    <h3>¡La gran final ha llegado y entre dos potencias del futbol!</h3>
                        <div class="post-info"> 
                            <span>Publicado el: <strong>17 de septiembre de 2025</strong>   </span> • 
                            <span>Categoría: <strong>Partidos</strong> </span> • 
                            <span>Creado por <strong> Juan </strong> </span>
                    </div>
                <p>Argentina, liderada por el icónico Lionel Messi, busca su tercer título mundial después de 36 años...</p>
            </article>
        </a>

        
    </main>
</div>
    <footer>
        <p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921</p>
    </footer>
</body>
</html>
