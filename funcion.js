const miFormulario = document.getElementById('registroForm');

        miFormulario.addEventListener('submit', function(event) {
            const password = document.getElementById('Password').value;
            const passwordVer = document.getElementById('PasswordVer').value;
            const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

            if (password !== passwordVer) {
                alert('Las contraseñas no coinciden.');
                event.preventDefault(); 
                return; 
            }

            if (!regex.test(password)) {
                alert('La contraseña no es segura.\n\nDebe tener al menos:\n- 8 caracteres\n- Una mayúscula\n- Una minúscula\n- Un número\n- Un carácter especial (@$!%*?&)');
                event.preventDefault();
            }
        });

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


        function verificarCorreo(){
            const inputElemento = document.getElementById("email").value;
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if(!regex.test(inputElemento)){
                alert("Por favor, ingresa un correo válido");
                return false;
            }
            return true;
        }
    
        function soloLetras(input) {
        input.value = input.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, '');  
        }

