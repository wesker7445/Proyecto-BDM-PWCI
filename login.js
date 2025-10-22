// Función para configurar el botón de mostrar/ocultar contraseña
function PasswordToggle(inputId, toggleId) {
    const passwordInput = document.getElementById(inputId);
    const toggleButton = document.getElementById(toggleId);
 
    if (!passwordInput || !toggleButton) {
        console.error("No se encontró el input o el botón para el toggle de contraseña.");
        return;
    }

    const iconShow = toggleButton.querySelector('.icon-show');
    const iconHide = toggleButton.querySelector('.icon-hide');

    if (!iconShow || !iconHide) {
        console.error("No se encontraron los iconos dentro del botón:", toggleId);
        return;
    }

    toggleButton.addEventListener('click', function() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
        } else {
                passwordInput.type = 'password';
        }

        iconShow.classList.toggle('d-none');
        iconHide.classList.toggle('d-none');
    });
}

// Llamar a la función solo para el campo de contraseña de este formulario
PasswordToggle('Password', 'togglePassword');