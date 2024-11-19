// Escuchar el evento submit del formulario de inicio de sesión
document.getElementById('form-login').addEventListener('submit', async function(event) {
    event.preventDefault();
    const API_URL_LOGIN = 'http://192.168.1.8/PublicArte/public/api/login';

    const name = document.getElementById('name').value;
    const password = document.getElementById('password').value;

    fetch(API_URL_LOGIN, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ name, password }),
    })
        .then(response => {
            if (response.ok) {
                // return response.json();
                response.json().then(resp => {
                    localStorage.setItem('token', resp.token);
                    localStorage.setItem('name', resp.name);
                    localStorage.setItem('rol', resp.nombreRol);
                    localStorage.setItem('apiUrl', 'http://192.168.1.8/PublicArte/public/api');

                    if (resp.nombreRol.toLowerCase() === 'administrador') {
                        window.location.href = 'admin/dashboard.html'; // Redirige al dashboard del administrador
                    }else if(resp.nombreRol.toLowerCase() === 'vendedor'){
                        window.location.href = 'vendedor/pedidos/verPedidos.html'
                    }
                     else {
                        window.location.href = 'index.html';
                    }

                    

                });
            } else {
                throw new Error('Credenciales inválidas');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Credenciales invalidas'
            });
        });
});