// Verifica si hay un token en el localStorage
const token = localStorage.getItem('token');
const nombre = localStorage.getItem('name');
const nombreRol = localStorage.getItem('rol');
const apiUrl = localStorage.getItem('apiUrl');
const API_URL_LOGOUT = `${apiUrl}/logout`;

if (!token || !nombreRol) {
    // Si no hay un token o rol, redirige al usuario a la página de inicio de sesión
    window.location.href = '../index.html'; // Reemplaza 'index.html' con la página de inicio de sesión
}

//MOSTRAR EN LA BARRA DE NAVEGACION EL ROL Y USUARIO CORRESPONDIENTE
const nameAndRolElement = document.getElementById('nameAndRol');
if (nameAndRolElement && nombre) {
    nameAndRolElement.textContent = `${nombre} (${nombreRol})`;
}

// Agrega un evento de escucha al enlace "Logout"
const logoutLink = document.getElementById('logout-link');
if (logoutLink) {
    logoutLink.addEventListener('click', function (event) {
        event.preventDefault(); // Evita que el enlace realice la acción predeterminada

        // Llamada a la API para cerrar la sesión
        fetch(API_URL_LOGOUT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        })
            .then(response => {
                if (response.ok) {
                    // Si la respuesta es exitosa, eliminar el token y nombre del localStorage
                    localStorage.removeItem('token');
                    localStorage.removeItem('name');
                    localStorage.removeItem('rol');
                    localStorage.removeItem('apiUrl');

                    // Redirigir al usuario a la página de inicio
                    location.href = '../../login.html'; // Reemplaza 'index.html' con la página de inicio deseada
                } else {
                    // Manejar el error si la llamada a la API falla
                    console.error('Error al cerrar la sesión en la API');
                }
            })
            .catch(error => {
                console.error('Error de red:', error);
            });
    });
}
