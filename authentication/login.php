<?php

/**
 * Script de inicio de sesión para el Dashboard Ejecutivo
 * Este archivo maneja tanto la presentación del formulario como la lógica de validación
 * utilizando la API de Retail Manager.
 */

// Incluir el archivo de configuración y funciones de la API
 // Asegúrate de que tu archivo de configuración se llama config.php

session_start(); // Iniciar la sesión para manejar el estado de autenticación
require_once '../config.php';
require_once '../setup/config_functions.php';
//validar si existe una configuracion del backend guardada


if (!$config) {
     header("Location: ../setup/setup-backend.php");
    exit;
}  

 // 1. Verificar si el usuario ha iniciado sesión
// Se comprueba si la variable de sesión 'loggedin' está establecida y es verdadera
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
    echo "<script>alert('¡La condición se cumplió! Redirigiendo a la página de configuración.');</script>";
    // Si ha iniciado sesión, lo redirigimos al dashboard
    header('Location: ../index.php');
    exit; // Es crucial usar exit() después de una redirección para detener la ejecución del script
}


$error_message = ''; // Variable para almacenar mensajes de error
$responseUsers = []; 
$userID =-1;
$apiErrorDuringEmployeeFetch = false;
// Llamada a la API para obtener los empleados y listarlos en autenticación/login.php
// En el Dashboard normal se usa el endpoint /LogInDashboard para obtener la lista de empleados disponibles para iniciar sesión.
// pero para probar la version lite usaremos GetEmployees poque aqui estan los salesman que tienen clientes
$responseUsers = callAPI('LogInDashboard', []); // Llamada a la API para obtener los empleados
if($responseUsers === false) {
    $apiErrorDuringEmployeeFetch = true; // Indicar que hubo un error al obtener los empleados
    // Manejar el error de la llamada a la API
    $error_message = "Error al conectar con el servidor de la API. Por favor, inténtelo de nuevo más tarde.";
} else {
    // Verificar si la llamada a la API fue exitosa y si se obtuvieron datos
    if ($responseUsers && isset($responseUsers['status']) && $responseUsers['status'] === 403) {
        $error_message =$responseUsers['message'];
    }else if (!empty($responseUsers) && is_array($responseUsers)) {
        $error_message = "";
        
    }else{
        $error_message = "No se encontraron empleados. Por favor, verifique la configuración de la API o inténtelo de nuevo más tarde.";
    }
}
// 1. Procesar el formulario si se ha enviado (método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpiar y validar los datos de entrada
    
    $userID = isset($_POST['UserID']) ? sanitizeInput($_POST['UserID']) : '';
    $username = isset($_POST['Username']) ? sanitizeInput($_POST['Username']) : '';
    $password = isset($_POST['UserPass']) ? $_POST['UserPass'] : ''; // No se limpia la contraseña con htmlspecialchars para evitar problemas de autenticación
    if (is_array($userID)) {
        $userID = ''; // Si es un arreglo, la forzamos a ser una cadena vacía.
    }
    if (is_array($username)) {
        $username = ''; // Si es un arreglo, la forzamos a ser una cadena vacía.
    }
     // A. Obtener el Username correspondiente al SelectedUserID del array $responseUsers
    $username = '';
    if (!empty($responseUsers) && is_array($responseUsers)) {
        
        foreach ($responseUsers as $employee) {
            
            if (isset($employee['ID']) && (string)$employee['ID'] === (string)$userID) {
                
                
                $username = isset($employee['Name']) ? $employee['Name'] : '';
                break;
            }
        }
    }
  
    // Verificar que todos los campos requeridos estén llenos
    if (empty($userID) || empty($username) || empty($password)) {
        $error_message = "Por favor, complete todos los campos requeridos.";
    } else {
        // Preparar los parámetros para la llamada a la API
        $params = [
            'UserID'   => $userID,
            'UserName' => $username,
            'UserPass' => $password
        ];

        // 2. Realizar la llamada a la API para validar el usuario
        // El endpoint es /ValidateUser con los parámetros UserID, UserName y UserPass
        // La documentación indica que el método es GET y los parámetros son requeridos.
        $response = callAPI('ValidateUser', $params);
        

        // 3. Evaluar la respuesta de la API
        if ($response !== false) {
            $response_status = '';
            // La API devuelve una cadena de texto como 'Valid', 'Invalid', o 'Inactive'
            if (is_array($response) && isset($response['message'])) {
                // Si es un arreglo (JSON decodificado), obtenemos el valor de la clave 'message'
                $response_status = trim(str_replace('"', '', $response['message']));
            } else {
                // Si la respuesta es una cadena de texto (como indica la documentación)
                $response_status = trim(str_replace('"', '', $response));
            }
            switch ($response_status) {
                case "Valid":
                    // Usuario validado correctamente.
                    // Almacenar información en la sesión y redirigir al dashboard
                    $_SESSION['loggedin'] = true;
                    $_SESSION['UserID'] = $userID;
                    $_SESSION['Username'] = $username;
                    // El endpoint es /GetEmployees y acepta el parámetro ID 
                    $employeeInfoResponse = callAPI('GetEmployees', ['ID' => $userID]);
                    
                    // Verificar si la llamada a la API fue exitosa y si se obtuvieron datos
                    if ($employeeInfoResponse !== false && !empty($employeeInfoResponse)) {
                        // La API GetEmployees devuelve los datos del empleado.
                        // Puede ser un array de un solo elemento o un objeto directo dependiendo de la implementación de callAPI.
                        // Asumimos que si es un array, el primer elemnto contiene los datos.
                        $employeeData = is_array($employeeInfoResponse) ? $employeeInfoResponse[0] : $employeeInfoResponse;
                        $_SESSION['Employee'] = $employeeData;
                        if($employeeData==null){
                            $error_message = "No se pudo obtener la información del empleado." .$employeeInfoResponse['message'] ?? '';
                            break; // Salir del switch para evitar redirección si hay un error
                        }
                        
                        // Guardar la información específica del empleado en la sesión 
                        if (isset($employeeData['Name'])) {
                            $_SESSION['EmployeeName'] = $employeeData['Name'];
                        }
                        if (isset($employeeData['SecurityLevel'])) {
                            $_SESSION['SecurityLevel'] = $employeeData['SecurityLevel'];
                        }
                        if (isset($employeeData['Acces'])) {
                            $_SESSION['AccessLevel'] = $employeeData['Acces'];
                            
                            
                        }
                        if (isset($employeeData['Salesman'])) {
                            //  'Yes'/'No' segun el empleado
                            $_SESSION['IsSalesman'] = $employeeData['Salesman'];
                        }
                        $infoCompanyResponse = callAPI('InfoCompany', ['ID' => $userID]);
                        if ($infoCompanyResponse !== false && !empty($infoCompanyResponse) && is_array($infoCompanyResponse)) {
                            // Guardar la información de la empresa en la sesión
                            $_SESSION['InfoCompany'] = $infoCompanyResponse[0] ?? $infoCompanyResponse; // Asumimos que es un array con un solo elemento o un objeto
                        } else {
                            // Manejar el caso donde no se pudo obtener la información de la empresa
                            // Por ejemplo, loggear el error o establecer valores por defecto
                            error_log("No se pudo obtener la información de la empresa para UserID: " . $userID);
                            $error_message = "No se pudo obtener la información de la empresa. Por favor, inténtelo de nuevo más tarde.";
                            break; // Salir del switch para evitar redirección si hay un error
                        }
                        
                    } else {
                        // Manejar el caso donde no se pudo obtener la información detallada del empleado
                        // Por ejemplo, loggear el error o establecer valores por defecto
                        error_log("No se pudo obtener la información detallada del empleado para UserID: " . $userID);
                        // Opcional: podrías poner valores por defecto para evitar errores en otras páginas
                        $error_message = "No se pudo obtener la información del empleado. Por favor, inténtelo de nuevo más tarde.";
                        break; // Salir del switch para evitar redirección si hay un error
                    }
                    header("Location: ../index.php"); // Redirigir a la página principal del dashboard
                    exit();
                case "Invalid":
                    $error_message = "Credenciales de inicio de sesión no válidas.";
                    break;
                case "Inactive":
                    $error_message = "La cuenta del usuario está inactiva.";
                    break;
                default:
                    // Manejar cualquier otra respuesta inesperada
                    $error_message = "Respuesta inesperada de la API: " . htmlspecialchars(print_r($response, true));
                    break;
            }
        } else {
            // Manejar errores de la llamada a la API (conexión, etc.)
            $error_message = "Error al conectar con el servidor de la API. Por favor, inténtelo de nuevo más tarde.";
            if (DEBUG_MODE) {
                // Si el modo de depuración está activo, se mostrarán errores detallados desde la función callAPI
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retail Manager Dashboard - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="../images/velcimetro.png" alt="Velocímetro" class="logo-background">
            <img src="../images/retail_manager_logo.jpg" alt="Retail Manager Dashboard Logo" class="logo-foreground">
            <h2 class="text-foreground"> <span class="title-line"></span>DASHBOARD <span class="title-line"></span></h2>
        </div>
        <h2>Log In</h2>
        <?php if (!empty($error_message)): ?>
            <div style="color: red; margin-bottom: 15px;"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <div class="form-login-container">
        <form action="login.php" method="POST">
            
           <div class="form-group">
                    <label for="userID">Selecciona tu usuario:</label>
                    <select class="form-select" name="UserID" id="userID" required <?php echo $apiErrorDuringEmployeeFetch ? 'disabled' : ''; ?>>
                        <option value="">-- Seleccione un usuario --</option>
                        <?php
                        if (!empty($responseUsers) && is_array($responseUsers)) {
                            foreach ($responseUsers as $employee) {
                                
                                // Ajusta 'ID' y 'Name' si las claves de tu API son diferentes (ej. 'UserID', 'EmployeeName')
                                $employeeID = isset($employee['ID']) ? htmlspecialchars($employee['ID']) : '';
                                $employeeName = isset($employee['Name']) ? htmlspecialchars($employee['Name']) : '';
                                $employeeAccess = isset($employee['Acces']) ? htmlspecialchars($employee['Acces']) : '';
                                
                                if (!empty($employeeID) && !empty($employeeName) && !empty($employeeAccess) && $employeeAccess==1) {
                                    $selected = ($userID === $employeeID) ? 'selected' : '';
                                    echo "<option value=\"{$employeeID}\" {$selected}>{$employeeName} (ID: {$employeeID})</option>";
                                }
                            }
                        } else {
                            echo "<option value=\"\" disabled>No hay usuarios disponibles</option>";
                        }
                        ?>
                    </select>
                </div>
            <div class="form-group">
                <input type="password" name="UserPass" placeholder="Password" required>
            </div>
             
            <button type="submit" class="login-button">Log In</button>
            <!-- Enlace de cambio de IP/puerto -->
<p class="mt-2 mb-0 text-center">
    <a href="../setup/setup-backend.php?edit=1" class="text-muted small">
        Cambiar IP y puerto del servidor
    </a>
</p>


        </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>