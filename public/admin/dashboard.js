document.addEventListener('DOMContentLoaded', function () {

    const apiUrl = localStorage.getItem('apiUrl');
    const API_URL = `${apiUrl}`;
    const token = localStorage.getItem('token');

    // Función genérica para realizar el fetch y mostrar los datos
    function fetchDataAndDisplay(endpoint, elementId, dataKey) {
        fetch(`${API_URL}/${endpoint}`, {
            headers: {
                'Content-Type': 'application/json',
                'token': token
            }
        })
            .then(response => response.json())
            .then(data => {
                const element = document.getElementById(elementId);
                if (element && data[dataKey] !== undefined) {
                    element.textContent = data[dataKey];
                }
            })
            .catch(error => console.error(`Error al obtener ${dataKey}: `, error));
    }

    // Pedidos
    fetchDataAndDisplay('pedidosDiaAnterior', 'ventasDiaAnterior', 'ventas de ayer');
    fetchDataAndDisplay('pedidosDiaActual', 'ventasDiaActual', 'ventas de hoy');
    fetchDataAndDisplay('pedidosMesAnterior', 'ventasMesAnterior', 'ventas del mes anterior');
    fetchDataAndDisplay('pedidosMesActual', 'ventasMesActual', 'ventas del mes actual');

    // Clientes
    fetchDataAndDisplay('clientesNuevosDiaActual', 'clientesDiaActual', 'clientes nuevos hoy');
    fetchDataAndDisplay('clientesNuevosDiaAnterior', 'clientesDiaAnterior', 'clientes nuevos ayer');
    fetchDataAndDisplay('clientesNuevosMesActual', 'clientesMesActual', 'clientes nuevos este mes');
    fetchDataAndDisplay('clientesNuevosMesAnterior', 'clientesMesAnterior', 'clientes nuevos mes anterior');

    // Función para mostrar las categorías más demandadas
    function fetchCategoriasMasDemandadas() {
        fetch(`${API_URL}/categoriasMasDemandadas`, {
            headers: {
                'Content-Type': 'application/json',
                'token': token
            }
        })
            .then(response => response.json())
            .then(data => {
                const topProducts = data.slice(0, 5);
                const productLabels = topProducts.map(product => product.nombreCategoria);
                const productQuantities = topProducts.map(product => product.totalPedidos);

                const chartOptions = {
                    chart: {
                        type: 'donut',
                        width: '100%',
                        height: 280,
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    plotOptions: {
                        pie: {
                            customScale: 0.8,
                            donut: {
                                size: '75%',
                            },
                        },
                    },
                    series: productQuantities,
                    labels: productLabels,
                    legend: {
                        position: 'left',
                        offsetY: 80,
                    },
                };

                const donut = new ApexCharts(document.querySelector('#chart-donut'), chartOptions);
                donut.render();
            })
            .catch(error => console.error('Error al obtener las categorías más demandadas: ', error));
    }

    // Llamar a la función para mostrar las categorías más demandadas
    fetchCategoriasMasDemandadas();
});
