const BASE_URL = '/dashboard-rm/';

// Este es el código JavaScript (ejecutado en el navegador)
// que llama al script PHP que contiene la función callAPI()
function encodeParams(params) {
    return Object.keys(params)
        .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(params[key]))
        .join('&');
}
async function fetchData(endpoint, params) {
    try {
        let url = 'api_proxy.php?endpoint=' + endpoint;
        if(params && Object.keys(params).length > 0){
            url += '&' + encodeParams(params);
        }
        const response = await fetch(url, {

        });

        // 1. Verificar el código de estado HTTP de la respuesta
        if (response.status === 403) {
            const errorData = await response.json();
            
            // 2. Mostrar el Swal.fire usando el mensaje del backend
            // Swal.fire devuelve una Promesa. Usamos .then() para capturar el resultado.
            Swal.fire({
                icon: 'error',
                title: 'Licencia Expirada',
                text: errorData.message,
                footer: 'Llámenos al 787-466-2091'
            }).then((result) => {
                // El objeto 'result' contiene información sobre cómo se cerró la alerta.
                
                if (result.isConfirmed) {
                    window.location.href = BASE_URL+"authentication/logout.php";
                } else {
                    window.location.replace(BASE_URL+"authentication/logout.php");
                }
            });
            
            return null; // Detener el procesamiento
        }

        if (!response.ok) {
            // Manejar otros errores HTTP (400, 500, etc.)
            throw new Error(`Error HTTP: ${response.status}` + ' ' + response.statusText);
        }

        return await response.json();

    } catch (error) {
        console.error("Fallo al obtener datos:", error);
        // Mostrar un error genérico si falla la red o JSON
        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
        return null;
    }
}
async function fetchDataUseURL(urlApi,endpoint, params) {
    try {
        if(!urlApi.startsWith('http://') && !urlApi.startsWith('https://')){
            urlApi = 'http://' + urlApi;
        }
        if(!urlApi.endsWith('/')){
            urlApi += '/';
        }
        urlApi += 'cse.api.v1/' 
        let url = urlApi + endpoint;
        if(params && Object.keys(params).length > 0){
            url += '&' + encodeParams(params);
        }
       
        const response = await fetch(url, {

        });

        // 1. Verificar el código de estado HTTP de la respuesta
        if (response.status === 403) {
            const errorData = await response.json();
            
            // 2. Mostrar el Swal.fire usando el mensaje del backend
            // Swal.fire devuelve una Promesa. Usamos .then() para capturar el resultado.
            Swal.fire({
                icon: 'error',
                title: 'Licencia Expirada',
                text: errorData.message,
                footer: 'Llámenos al 787-466-2091'
            }).then((result) => {
                // El objeto 'result' contiene información sobre cómo se cerró la alerta.
                
                if (result.isConfirmed) {
                    window.location.href = BASE_URL+"authentication/logout.php";
                } else {
                    window.location.replace(BASE_URL+"authentication/logout.php");
                }
            });
            
            return null; // Detener el procesamiento
        }

        if (!response.ok) {
            // Manejar otros errores HTTP (400, 500, etc.)
            throw new Error(`Error HTTP: ${response.status}` + ' ' + response.statusText);
        }

        return await response.json();

    } catch (error) {
        console.error("Fallo al obtener datos:", error);
        // Mostrar un error genérico si falla la red o JSON
        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
        return null;
    }
}