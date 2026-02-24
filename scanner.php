<?php
    session_start();
    // Necesitas estas variables para que la barra lateral no de error
    $isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    $userRole = $isLoggedIn && isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modo Escaneo - Mundial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    
    <style>
        :root {
            --sidebar-bg: #222;
            --main-bg: #1a1a1a;
            --accent-color: #e6e6e6;
            --btn-scan: #fff;
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Roboto', sans-serif;
            background-color: var(--main-bg);
            color: white;
            overflow: hidden; /* Evita scroll para sentirlo como App */
        }

            .controls-row {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 50px; /* Espacio entre los botones */
            z-index: 5;
        }

        .side-control {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        }

        .control-label {
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        text-shadow: 1px 1px 4px rgba(0,0,0,0.8);
        color: #fff;
        }


        .layout {
            display: flex;
            height: 100vh;
        }

        /* Reutilizando el estilo de tu Sidebar */
        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            padding: 20px 0;
            border-right: 1px solid #333;
            z-index: 10;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }

        .sidebar li a {
            color: #ccc;
            text-decoration: none;
            padding: 15px 25px;
            display: block;
            transition: 0.3s;
            font-size: 16px;
        }

        .sidebar li a:hover {
            background-color: #333;
            color: #fff;
        }

        .sidebar i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Contenedor de la Cámara */
        .camera-container {
            flex-grow: 1;
            position: relative;
            background: linear-gradient(135deg, #1a1a1a 25%, #2a2a2a 100%);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Simulación del visor de la cámara */
        .camera-view {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            background-color: #000; /* Fondo negro para el video */
        }

        /* Guía de escaneo (El cuadro en el centro) */
        .scan-guide {
            position: relative;
            width: 280px;
            height: 280px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.5); /* Oscurece lo de afuera */
            z-index: 2;
        }

        /* Esquinas animadas para la guía */
        .scan-guide::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px; width: 40px; height: 40px;
            border-top: 4px solid #fff; border-left: 4px solid #fff;
            border-radius: 20px 0 0 0;
        }
        .scan-guide::after {
            content: '';
            position: absolute;
            bottom: -2px; right: -2px; width: 40px; height: 40px;
            border-bottom: 4px solid #fff; border-right: 4px solid #fff;
            border-radius: 0 0 20px 0;
        }

        /* Botón de Escaneo Central */
        .scan-button-container {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 5;
        }

        .btn-scan {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: white;
            border: 5px solid rgba(255,255,255,0.3);
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            color: #222;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            transition: transform 0.2s, background-color 0.2s;
        }

        .btn-scan:active {
            transform: translateX(-50%) scale(0.9); /* Efecto click */
            background-color: #ddd;
        }

        /* Info de ayuda */
        .scan-instructions {
            position: absolute;
            top: 30px;
            background: rgba(0,0,0,0.6);
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 14px;
            backdrop-filter: blur(5px);
        }

        .btn-secondary {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid #fff;
        backdrop-filter: blur(5px);
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 18px;
        transition: 0.3s;
        }
        .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.4);
        transform: scale(1.1);
        }

        .btn-scan {
        width: 75px;
        height: 75px;
        border-radius: 50%;
        background-color: white;
        border: 5px solid rgba(255,255,255,0.3);
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 28px;
        color: #222;
        box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }

        .split-scan-container {
        position: relative;
        width: 85px; /* Un poco más grande para acomodar el contenido */
        height: 85px;
        border-radius: 50%;
        /* Mantenemos el anillo exterior del diseño original */
        border: 5px solid rgba(255,255,255,0.3);
        box-shadow: 0 0 20px rgba(0,0,0,0.5);
        /* Flex para poner los botones lado a lado */
        display: flex;
        /* CRUCIAL: Esto recorta los botones cuadrados internos a la forma circular */
        overflow: hidden;
        background-color: white;
        z-index: 10; /* Asegurar que esté por encima */
        }

        .split-btn {
        flex: 1; /* Ambos ocupan el 50% del espacio */
        height: 100%;
        border: none;
        cursor: pointer;
        display: flex;
        flex-direction: column; /* Icono arriba, texto abajo */
        justify-content: center;
        align-items: center;
        color: #222;
        padding: 0;
        transition: all 0.2s ease;
        /* Ajuste fino para que los elementos no queden muy pegados al borde curvo */
        padding-top: 2px;
        }
        
        /* Estilo para los iconos dentro de la división */
        .split-btn i {
            font-size: 22px;
            margin-bottom: 2px;
        }

        /* Estilo para las pequeñas etiquetas de texto (opcional) */
        .split-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* 3. Estilos específicos para diferenciar izquierda y derecha */

        /* Mitad Izquierda (Foto) */
        .split-btn.left {
            background-color: #f2f2f2; /* Un blanco ligeramente gris para contraste */
            border-right: 1px solid #d0d0d0; /* La línea divisoria central */
            padding-right: 5px; /* Compensar visualmente la curva */
        }

        /* Mitad Derecha (Scan) */
        .split-btn.right {
            background-color: #ffffff; /* Blanco puro */
            padding-left: 5px; /* Compensar visualmente la curva */
        }

        /* 4. Efectos Hover y Active (Click) */

        .split-btn:hover {
            background-color: #e6e6e6; /* Gris más oscuro al pasar el mouse */
            color: #000;
        }

        .split-btn:active {
            background-color: #cccccc; /* Efecto de click */
            transform: scale(0.98); /* Ligero efecto de pulsación */
}

    </style>
</head>
<body>

<div class="layout">
    <nav class="sidebar">
        <ul>
            <li><a href="Pagina.php" class="active-link"><i class="fa-solid fa-house"></i> Feed</a></li>
            
            <?php if ($isLoggedIn): ?>
                <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a></li>
            <?php else: ?>
                <li><a href="InicioSesion.php"><i class="fa-solid fa-user"></i> Iniciar Sesión</a></li>
            <?php endif; ?>

            <?php if ($userRole == 1): ?>
                <li class="sidebar-section-title">Administración</li>
                <li><a href="/Pagina/Admin/GestionarM.php"><i class="fa-solid fa-list-check"></i> Gestionar Mundial</a></li>
                <?php endif; ?>

            <li class="sidebar-section-title">Herramientas</li>
            <li><a href="#"><i class="fa-solid fa-vr-cardboard"></i> Modo AR</a></li>
            <li><a href="scanner.php"><i class="fa-solid fa-expand"></i> Modo escaneo</a></li>
            <li><a href="MisPosts.php"><i class="fas fa-cog"></i> Perfil</a></li>
        </ul>
    </nav>
    <main class="camera-container">
        <div class="camera-view"></div> 

        <div id="model-overlay" style="
            display: none; 
            position: fixed; 
            top: 0; 
            left: 250px; 
            right: 0; 
            bottom: 0; 
            z-index: 9999; 
            background: rgba(0, 0, 0, 0.4); 
            backdrop-filter: blur(8px); 
            -webkit-backdrop-filter: blur(8px);
            align-items: center; 
            justify-content: center;
        ">
            <div style="
                position: relative;
                width: 85%; 
                max-width: 450px; 
                height: 65vh; 
                background: #222; 
                border-radius: 25px; 
                display: flex;
                flex-direction: column;
                overflow: hidden;
                box-shadow: 0 25px 50px rgba(0,0,0,0.6);
                border: 1px solid rgba(255,255,255,0.1);
            ">
                <button id="close-3d" style="position: absolute; top: 15px; right: 15px; z-index: 10; background: rgba(0,0,0,0.5); border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; color: white;">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <model-viewer 
                    id="viewer"
                    src="modelos/balon.glb" 
                    ar 
                    camera-controls 
                    auto-rotate 
                    rotation-speed="0.5"
                    shadow-intensity="1"
                    style="width: 100%; flex-grow: 1; background: radial-gradient(#444, #222);">
                </model-viewer>

                <div style="
                    height: 80px; 
                    background: rgba(0,0,0,0.3); 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    gap: 20px;
                    border-top: 1px solid rgba(255,255,255,0.1);
                ">
                    <button onclick="changeModel('modelos/balon.glb')" style="background: #333; border: 2px solid #555; color: white; width: 50px; height: 50px; border-radius: 10px; cursor: pointer;" title="Balón">
                        <i class="fa-solid fa-soccer-ball"></i>
                    </button>
                    <button onclick="changeModel('modelos/Trofeo.glb')" style="background: #333; border: 2px solid #555; color: white; width: 50px; height: 50px; border-radius: 10px; cursor: pointer;" title="Trofeo">
                        <i class="fa-solid fa-trophy"></i>
                    </button>
                    <button onclick="changeModel('modelos/Sustitudor.glb')" style="background: #333; border: 2px solid #555; color: white; width: 50px; height: 50px; border-radius: 10px; cursor: pointer;" title="Aviso de Sustitucion">
                        <i class="fa-solid fa-arrows-turn-to-dots"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="scan-instructions">
            Apunta un escudo del mundial
        </div>

        <div class="scan-guide"></div>

        <div class="controls-row">
            <div class="side-control">
                <span class="control-label">Modelo 3D</span>
                <button class="btn-secondary" id="btn-show-3d">
                    <i class="fa-solid fa-cube"></i>
                </button>
            </div>

            <div class="split-scan-container">
                <button class="split-btn left" title="Tomar Foto">
                    <i class="fa-solid fa-camera"></i>
                    <span class="split-label">Foto</span>
                </button>
                <button class="split-btn right" title="Escanear QR">
                    <i class="fa-solid fa-qrcode"></i>
                    <span class="split-label">Scan</span>
                </button>
            </div>

            <div class="side-control">
                <span class="control-label">Efectos</span>
                <button class="btn-secondary">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </button>
            </div>
        </div>
    </main>
</div>

<script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.3.0/model-viewer.min.js"></script>
<script>
    // Referencia al visor
    const modelViewer = document.getElementById('viewer');


    function changeModel(modelPath) {

        modelViewer.src = modelPath;
        

        modelViewer.style.opacity = '0.5';
        modelViewer.addEventListener('load', () => {
            modelViewer.style.opacity = '1';
        }, { once: true });
    }


    const btn3D = document.getElementById('btn-show-3d');
    const overlay = document.getElementById('model-overlay');
    const btnClose = document.getElementById('close-3d');

    btn3D.addEventListener('click', () => {
        overlay.style.display = 'flex';
    });

    btnClose.addEventListener('click', () => {
        overlay.style.display = 'none';
    });

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.style.display = 'none';
    });
</script>
</body>
</html>