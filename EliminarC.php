<?php 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina Principal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">


</head>
<body>
<header>
    <h1>Feed   <svg  xmlns="http://www.w3.org/2000/svg"  width="30"  height="30"  viewBox="0 0 24 24"  fill="none"  stroke="#6cd085"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-ball-football"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7l4.76 3.45l-1.76 5.55h-6l-1.76 -5.55z" /><path d="M12 7v-4m3 13l2.5 3m-.74 -8.55l3.74 -1.45m-11.44 7.05l-2.56 2.95m.74 -8.55l-3.74 -1.45" /></svg></h1>
    <div class="user">
        <img src="https://imgs.search.brave.com/bHAXnPjJGeaQY0fRdCk7KFKffJZzwToorrO27ygbV20/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9pLnBp/bmltZy5jb20vb3Jp/Z2luYWxzLzA4Lzhi/L2Y3LzA4OGJmNzYw/NzNhMjFjNWJlMDUx/YTY1YmUwOTgwYTc1/LmpwZw" height="50" width="50">
        <a href="MisPosts.php"><h3>Juan</h3></a>
        <a href="Pagina.php"><h3>Modo Normal</h3></a>
    </div>
</header>
<nav>
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
        <li><a href ="CrearP.php">Crear Publicacion</a></li> 
        <li><a href ="EliminarC.php">Eliminar Comentarios</a></li>
        <li><a href ="GestionarM.php">Gestionar Mundiales</a></li>
        <li class="container">  <form action="Noticias.php">
                <input type="text" name="Busqueda" placeholder="Buscar...">
                <button><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="#ffffffff"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-zoom"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg></button>
            </form>
        </li>
    </ul>
</nav>


<div class = "Comentario-card">
    <div class = "Header">
    <h1>Comentario de Juan</h1>
    </div>
    <div class="meta">
        <span>Publicado el: <strong>17 de septiembre de 2025</strong>   </span> • 
        <span>Categoría: <strong>Goles</strong> </span> • 
        <span>Publicacion de <strong> Los 5 Momentos Inolvidables del Mundial de 2014 </strong> </span>
    </div>
    <div class = "content">
    <p>Ese mundial fue emocionante, recuerdo cuando estaba en la sala sentado comiendo y bebiendo mientras lo veia.</p>
    </div>
    <div class="actions">
        <button class ="btn-admitir">Admitir</button>
        <button class ="btn-noadmitir">No admitir</button>
    </div>
</div>

<div class = "Comentario-card">
    <div class = "Header">
    <h1>Comentario de Pedro</h1>
    </div>
    <div class="meta">
        <span>Publicado el: <strong>17 de septiembre de 2025</strong>   </span> • 
        <span>Categoría: <strong>Goles</strong> </span> • 
        <span>Publicacion de <strong> Los 5 Momentos Inolvidables del Mundial de 2014 </strong> </span>
    </div>
    <div class = "content">
    <p>Recuerdo que yo grite cuando metieron gol y mi familia se me quedo viendo.</p>
    </div>
    <div class="actions">
        <button class ="btn-admitir">Admitir</button>
        <button class ="btn-noadmitir">No admitir</button>
    </div>
</div>

<footer>
<p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921 <p>
</footer>

</body>
</html>