<?php

session_start();


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: InicioSesion.php");
    exit;
}


$username_session = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Publicacion</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <h1>Crear Publicacion <svg  xmlns="http://www.w3.org/2000/svg"  width="30"  height="30"  viewBox="0 0 24 24"  fill="none"  stroke="#6cd085"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-ball-football"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7l4.76 3.45l-1.76 5.55h-6l-1.76 -5.55z" /><path d="M12 7v-4m3 13l2.5 3m-.74 -8.55l3.74 -1.45m-11.44 7.05l-2.56 2.95m.74 -8.55l-3.74 -1.45" /></svg></h1>
        <div class="user">
            <img src="MostrarImagen.php" alt="Foto de perfil" height="50" width="50">
            <a href="MisPosts.php" ><h3><?php echo htmlspecialchars($username_session); ?></h3></a>
            <a href="Admin.php"><h3>Modo admin</h3></a>
        </div>
    </header>

    <div class= "layout">
        <nav class="sidebar">
            <ul> 
                <li> 
                    <a href ="#">Categorias</a>
                    <ul>
                        <li><a href="#">Faltas</a></li>
                        <li><a href="#">Jugadas polémicas</a></li>
                        <li><a href="#">Goles</a></li>
                        <li><a href="#">Partidos</a></li>
                        <li><a href="#">Otro</a></li>
                    </ul>
                </li>
                <li><a href ="Pagina.php">Menu Principal</a></li>
            </ul>
        </nav>

        <main>
            <form action="Pagina.php" class="Formulario">
                <h2>Crear Publicacion</h2>
                <input type="text" name="Titulo" placeholder="Ingresa el titulo...">
                <input type="text" name="Descripcion" placeholder="Ingresa la descripcion...">
                <select name="Categoria" required>
                    <option value="">Selecciona una categoría...</option>
                    <option value="mundial">Faltas</option>
                    <option value="copa-america">Copa América</option>
                    <option value="eurocopa">Jugadas polémicas</option>
                    <option value="champions-league">Goles</option>
                    <option value="liga-local">Partidos</option>
                    <option value="otro">Otro</option>
                </select>
                <input type="file" name="Archivo">
                <button>Publicar</button>
            </form>
        </main>
    </div>
    <footer>
        <p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921</p>
    </footer>
</body>
</html>