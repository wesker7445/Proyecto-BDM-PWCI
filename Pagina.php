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
        <a href="MisPosts.php""><h3>Juan</h3></a>
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
        <li><a href ="InicioSesion.php">Inicio de Sesion</a></li>
        <li><a href ="CrearP.php">Crear Publicacion</a></li>
        <li class="container">  <form action="Noticias.php">
                <input type="text" name="Busqueda" placeholder="Buscar...">
                <button><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="#ffffffff"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-zoom"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg></button>
            </form>
        </li>
    </ul>
</nav>

<main class="publicacion">
    <article>
        <h1>Los 5 Momentos Inolvidables del Mundial de 2014</h1>
        <div class="info"> 
            <span>Publicado por: Juan</span> <br>
            <span>Fecha: 17 de septiembre de 2025</span>
        </div>

        <p class="introduccion">
      El Mundial de Brasil 2014 nos dejó jugadas para la historia y emociones a flor de piel. Acompáñanos a revivir los cinco momentos que definieron este torneo.
    </p>

    <div class="cuerpo-articulo">
      <p>
        El Mineirazo fue un momento que silenció a todo un país. La anfitriona Brasil cayó estrepitosamente ante una Alemania que se coronaría campeona...
      </p>

      <p>
        Corría el minuto 113 de una tensa final cuando Mario Götze controló con el pecho un pase de Schürrle y, sin dejarla caer, remató para darle la copa a Alemania...
      </p>
      
      </div>

    <div class="conclusion">
      <p>
        En resumen, el Mundial de 2014 fue un torneo lleno de sorpresas y drama. Para ti, ¿cuál fue el momento más memorable? ¡Déjanos tu opinión en los comentarios!
      </p>
    </div>

    <img src="https://imgs.search.brave.com/Vmog522NsuqwlLRfDd-RE_Bu-65KsU9yqCkmY7F_KiY/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9pY2hl/Zi5iYmNpLmNvLnVr/L2FjZS93cy82NDAv/YW16L3dvcmxkc2Vy/dmljZS9saXZlL2Fz/c2V0cy9pbWFnZXMv/MjAxNC8wNy8xNC8x/NDA3MTQwMDQzMTRf/bWFyaW9fZ29ldHpl/X2FuZHJlX3NjaHVl/cnJsZV9hbmRfamVy/b21lX2JvYXRlbmdf/XzYyNHgzNTFfYWZw/LmpwZy53ZWJw" alt="Jugadores alemanes celebrando">

    <form action="">
    <input type="text" name="Comentario" placeholder="Pienso en...">
    <button> Enviar </button>
    
    </article>


    </form>

</main>


<footer>
<p class="Resaltado">© 2025 Mi Pagina de Mundiales | MiPaginadeMundiales@gmail.com | 815678921 <p>
</footer>

</body>
</html>