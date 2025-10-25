function setupPasswordToggle(inputId, toggleId) {
    const passwordInput = document.getElementById(inputId);
    const toggleButton = document.getElementById(toggleId);
    

    if (!toggleButton) {
        console.error("No se encontró el botón con ID:", toggleId);
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


setupPasswordToggle('Password', 'togglePassword');
setupPasswordToggle('PasswordVer', 'togglePasswordVer');


function soloLetras(input) {
input.value = input.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, '');  
}

