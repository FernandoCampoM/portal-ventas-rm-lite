<?php
/**
 * Configuración para el Dashboard Ejecutivo
 * 
 * Este archivo maneja la configuración básica y funciones de utilidad
 * para la comunicación con la API del sistema
 */

// Configuración básica
require_once 'setup/config_functions.php';
//validar si existe una configuracion del backend guardada
$config = get_configBackend();
if (!$config) {
    header("Location: /dashboard-rm/setup/setup-backend.php");
    exit;
}

define('API_BASE_URL', "http://{$config['backend_ip']}:{$config['backend_port']}/cse.api.v1/");
define('API_USERNAME', 'testserver'); // Cambiar por tu usuario real
define('API_PASSWORD', 'testserver'); // Cambiar por tu contraseña real
define('DEBUG_MODE', false); // Activar para ver errores en pantalla

function get_licence_validity() {
    $response= callAPI('validez', []);
    $licenceDays = 0;
    if($response['success'] == true) {
        $licenceDays = (int)$response['message'];
        //$licenceDays = 10;
    }
    return 10;
}
// Función para hacer peticiones a la API
function callAPI($endpoint, $params = []) {

    if( $endpoint != 'validez' && get_licence_validity() <= 0) {
        // Si hay un error de conexión o la respuesta no es válida, usar datos mock
        $rta = [
            "success" => false,
            "message" => "Gracias por usar los servicios de RetailManager. Notamos un inconveniente con su licencia. Por favor, llámenos al 787-466-2091 o escribanos al correo info@retailmanagerpr.com para resolverlo.",
            "status" => 403
        ];
        return $rta;
    }
    $url = API_BASE_URL . $endpoint;
    
    // Agregar parámetros a la URL
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    // Inicializar cURL
    $curl = curl_init();
    
    // Configurar opciones de cURL
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Cache-Control: no-cache"
        ],
        // Agregar autenticación básica
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => API_USERNAME . ":" . API_PASSWORD,
        // Opciones adicionales para depuración
        CURLOPT_VERBOSE => DEBUG_MODE,
        CURLOPT_SSL_VERIFYPEER => false, // Desactivar verificación SSL para desarrollo
    ]);
    
    // Ejecutar petición
    $response = curl_exec($curl);
    if($response === false) {
        mostrarErrorBackend();
        return false;
    }
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    // Guardar información para depuración
    $requestInfo = [
        'url' => $url,
        'http_code' => $httpCode,
        'error' => $err,
        'response' => $response
    ];
    
    // Guardar información de la solicitud en un log
    if (DEBUG_MODE) {
        logRequest($endpoint, $requestInfo);
    }
    
    // Cerrar cURL
    curl_close($curl);
    
    // Verificar errores
    if ($err) {
        
        mostrarErrorBackend();
        return false;
    }
    
    // Verificar código HTTP
    if ($httpCode >= 400) {
        mostrarErrorBackend();
        return false;
    }
    
    // Decodificar respuesta JSON
    $decodedResponse = json_decode($response, true);
    
    // Verificar si la respuesta es válida
    if (json_last_error() !== JSON_ERROR_NONE) {
       mostrarErrorBackend();
        // Si la respuesta no es JSON válido pero no hubo errores HTTP o cURL,
        // podemos devolver la respuesta directamente (por ejemplo, para 'NoMatch' de InfoBarCode)
        if ($httpCode >= 200 && $httpCode < 300) {
            return $response;
        }
        
        return false;
    }
    
    return $decodedResponse;
}

/**
 * Registra información de solicitudes HTTP para depuración
 * 
 * @param string $endpoint Endpoint de la API
 * @param array $info Información de la solicitud
 */
function logRequest($endpoint, $info) {
    $logFile = __DIR__ . '/logs/api_requests_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    
    // Crear directorio de logs si no existe
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Formatear mensaje
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] [$endpoint]\n";
    $formattedMessage .= "URL: {$info['url']}\n";
    $formattedMessage .= "HTTP Code: {$info['http_code']}\n";
    
    if (!empty($info['error'])) {
        $formattedMessage .= "Error: {$info['error']}\n";
    }
    
    $formattedMessage .= "Response: " . substr($info['response'], 0, 1000) . "\n\n";
    
    // Escribir en el archivo de log
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

// Función para formatear moneda
function formatCurrency($amount) {
    
    return '$' . number_format($amount, 2, '.', ',');
}

// Función para formatear porcentaje
function formatPercentage($value) {
    return number_format($value, 1, '.', ',') . '%';
}

// Función para obtener datos mock en caso de error de conexión
function getMockData($endpoint) {
    switch($endpoint) {
        case 'InfoCompany':
            return [
                'Name' => 'Licencia para Demostración',
                'Address1' => 'test',
                'Address2' => '',
                'ZipCode' => '00725',
                'City' => 'Caguas',
                'Country' => 'PR',
                'Phone' => '0000000000',
                'Phone2' => '0000000000',
                'Fax' => '0000000000',
                'Email' => 'info@demo.com'
            ];
            
        case 'SalesTotals':
            return [
                'TotalInvoices' => 24589,
                'TotalSales' => 1458963.52,
                'TotalDiscounts' => 58963.25,
                'TotalCityTax' => 18965.32,
                'TotalStateTax' => 29856.21,
                'TransactionCount' => 24589,
                'TotalCost' => 985632.15,
                'AverageTicketAmount' => 59.33,
                'AverageProfitPerTransaction' => 19.25
            ];
            
        case 'SalesByHour':
            return array_map(function($hour) {
                return [
                    'HourOfDay' => $hour,
                    'TransactionCount' => rand(100, 1000),
                    'TotalSales' => rand(1000, 10000),
                    'TotalProfit' => rand(300, 3000),
                    'AverageTicketAmount' => rand(20, 100),
                    'TotalItemsSold' => rand(500, 5000)
                ];
            }, range(0, 23));
            
        case 'SalesByCategory':
            $categories = ['Frutas', 'Verduras', 'Carnes', 'Lácteos', 'Panadería', 'Bebidas', 'Limpieza', 'Congelados', 'Snacks', 'Mascotas'];
            return array_map(function($i, $cat) {
                return [
                    'CategoryID' => $i + 1,
                    'CategoryName' => $cat,
                    'InvoiceCount' => rand(500, 5000),
                    'QuantitySold' => rand(2000, 20000),
                    'TotalSales' => rand(10000, 100000),
                    'TotalProfit' => rand(3000, 30000),
                    'AveragePrice' => rand(5, 50),
                    'ProfitMarginPercentage' => rand(10, 40)
                ];
            }, array_keys($categories), $categories);
            
        case 'SalesByDepartment':
            $departments = ['Alimentos Frescos', 'Abarrotes', 'Limpieza', 'Hogar', 'Electrónica', 'Farmacia'];
            return array_map(function($i, $dept) {
                return [
                    'DepartmentID' => $i + 1,
                    'DepartmentName' => $dept,
                    'InvoiceCount' => rand(800, 8000),
                    'QuantitySold' => rand(3000, 30000),
                    'TotalSales' => rand(20000, 200000),
                    'TotalProfit' => rand(6000, 60000),
                    'AveragePrice' => rand(8, 80),
                    'ProfitMarginPercentage' => rand(15, 45)
                ];
            }, array_keys($departments), $departments);
            
        case 'SalesByMethod':
            $dates = [];
            $currentDate = new DateTime();
            for ($i = 0; $i < 30; $i++) {
                $date = clone $currentDate;
                $date->modify("-$i days");
                $dates[] = $date->format('Y-m-d');
            }
            
            return array_map(function($date) {
                $totalSales = rand(10000, 50000);
                $cashPercent = rand(30, 50) / 100;
                $creditPercent = rand(20, 35) / 100;
                $debitPercent = rand(10, 25) / 100;
                $checkPercent = rand(5, 10) / 100;
                $athPercent = 1 - ($cashPercent + $creditPercent + $debitPercent + $checkPercent);
                
                return [
                    'SaleDate' => $date,
                    'TotalInvoices' => rand(100, 500),
                    'TotalSales' => $totalSales,
                    'Subtotal' => $totalSales * 0.9,
                    'TotalDiscounts' => $totalSales * 0.05,
                    'TotalCityTax' => $totalSales * 0.02,
                    'TotalStateTax' => $totalSales * 0.03,
                    'CashPayments' => $totalSales * $cashPercent,
                    'CreditCardPayments' => $totalSales * $creditPercent,
                    'DebitCardPayments' => $totalSales * $debitPercent,
                    'CheckPayments' => $totalSales * $checkPercent,
                    'AthMovilPayments' => $totalSales * $athPercent,
                    'TransactionCount' => rand(100, 500),
                    'AverageTicketAmount' => rand(50, 100),
                    'TotalProfit' => $totalSales * rand(20, 30) / 100,
                    'AverageProfitPerTransaction' => rand(10, 30)
                ];
            }, $dates);
            
        case 'LowLevelItems':
            $products = [];
            for ($i = 0; $i < 20; $i++) {
                $products[] = [
                    'ProductCode' => 'PROD' . (1000 + $i),
                    'ProductName' => 'Producto de bajo stock ' . ($i + 1),
                    'CurrentStock' => rand(1, 10),
                    'MinimumLevel' => 15,
                    'MaximumLevel' => 50,
                    'Price' => rand(5, 50),
                    'Cost' => rand(2, 30),
                    'Category' => ['Frutas', 'Verduras', 'Carnes', 'Lácteos'][rand(0, 3)],
                    'Department' => ['Alimentos Frescos', 'Abarrotes'][rand(0, 1)],
                    'PrimarySupplier' => ['Proveedor A', 'Proveedor B', 'Proveedor C'][rand(0, 2)]
                ];
            }
            return $products;
            
        case 'GetAllProducts':
            $products = [];
            for ($i = 0; $i < 100; $i++) {
                $products[] = [
                    'ProductCode' => 'PROD' . (2000 + $i),
                    'ProductName' => 'Producto ' . ($i + 1),
                    'CurrentStock' => rand(10, 100),
                    'Price' => rand(2, 100),
                    'Cost' => rand(1, 50),
                    'BarCode' => '7890' . (100000 + $i),
                    'BarCode2' => '8901' . (100000 + $i),
                    'Category' => ['Frutas', 'Verduras', 'Carnes', 'Lácteos', 'Panadería', 'Bebidas'][rand(0, 5)],
                    'Department' => ['Alimentos Frescos', 'Abarrotes', 'Limpieza', 'Hogar'][rand(0, 3)]
                ];
            }
            return $products;
            
        case 'TopSellProducts':
            $products = [];
            for ($i = 0; $i < 50; $i++) {
                $products[] = [
                    'ProductCode' => 'PROD' . (3000 + $i),
                    'ProductName' => 'Producto Top ' . ($i + 1),
                    'Department' => ['Alimentos Frescos', 'Abarrotes', 'Limpieza', 'Hogar'][rand(0, 3)],
                    'Category' => ['Frutas', 'Verduras', 'Carnes', 'Lácteos', 'Panadería', 'Bebidas'][rand(0, 5)],
                    'TotalQuantitySold' => rand(500, 5000),
                    'TotalSales' => rand(5000, 50000),
                    'TotalProfit' => rand(1500, 15000),
                    'AveragePrice' => rand(5, 50),
                    'ProfitMarginPercentage' => rand(10, 45),
                    'CurrentStock' => rand(20, 200)
                ];
            }
            return $products;
            
        case 'InventoryValue':
            $depts = ['Alimentos Frescos', 'Abarrotes', 'Limpieza', 'Hogar', 'Electrónica', 'Farmacia'];
            $result = array_map(function($dept) {
                return [
                    'Department' => $dept,
                    'ItemCount' => rand(100, 1000),
                    'TotalCost' => rand(10000, 100000),
                    'TotalRetail' => rand(20000, 200000),
                    'AverageCost' => rand(5, 20),
                    'AverageRetail' => rand(10, 40)
                ];
            }, $depts);
            
            // Agregar fila de total
            $result[] = [
                'Department' => 'TOTAL',
                'ItemCount' => array_sum(array_column($result, 'ItemCount')),
                'TotalCost' => array_sum(array_column($result, 'TotalCost')),
                'TotalRetail' => array_sum(array_column($result, 'TotalRetail')),
                'AverageCost' => array_sum(array_column($result, 'AverageCost')) / count($result),
                'AverageRetail' => array_sum(array_column($result, 'AverageRetail')) / count($result)
            ];
            
            return $result;
            
        case 'SaleTrendByMonth':
            $months = [];
            $currentDate = new DateTime();
            for ($i = 0; $i < 24; $i++) {
                $date = clone $currentDate;
                $date->modify("-$i months");
                $months[] = $date;
            }
            
            return array_map(function($date) {
                $totalSales = rand(100000, 500000);
                return [
                    'Year' => $date->format('Y'),
                    'Month' => $date->format('n'),
                    'MonthYear' => $date->format('Y-m'),
                    'InvoiceCount' => rand(1000, 5000),
                    'TotalSales' => $totalSales,
                    'Subtotal' => $totalSales * 0.9,
                    'TotalDiscounts' => $totalSales * 0.05,
                    'TotalCityTax' => $totalSales * 0.02,
                    'TotalStateTax' => $totalSales * 0.03,
                    'TotalProfit' => $totalSales * rand(20, 30) / 100,
                    'AverageTicketAmount' => rand(20, 100),
                    'GrossMarginPercentage' => rand(10, 40)
                ];
            }, $months);
            
        case 'ProductInfo':
            $ref = isset($_GET['Referencia']) ? $_GET['Referencia'] : '';
            $barcode = isset($_GET['Barcode']) ? $_GET['Barcode'] : '';
            
            if (empty($ref) && empty($barcode)) {
                return [];
            }
            
            return [
                'Description' => 'Producto ' . ($ref ?: $barcode),
                'Suplier' => 'Proveedor ' . rand(1, 5),
                'Price' => rand(5, 100),
                'OnHand' => rand(10, 200),
                'Department' => 'Departamento ' . rand(1, 6),
                'Category' => 'Categoría ' . rand(1, 10),
                'Barcode' => '78901234567' . rand(10, 99),
                'Location' => 'Pasillo ' . rand(1, 20) . ', Estante ' . rand(1, 10),
                'Cost' => rand(2, 50),
                'IsFood' => rand(0, 1),
                'IsService' => 0,
                'LongDesc' => 'Descripción detallada del producto ' . ($ref ?: $barcode),
                'BCde13' => '5901234123457',
                'BCFranchise' => rand(0, 1) ? 'Franquicia A' : 'Franquicia B'
            ];
            
        case 'InfoBarCode':
            $barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
            
            if (empty($barcode)) {
                return 'NoMatch';
            }
            
            if (rand(0, 10) < 2) {
                return 'NoMatch'; // Simular que algunos códigos no existen
            }
            
            return [
                'ProductCode' => 'PROD' . rand(1000, 5000),
                'Barcode' => $barcode,
                'Barcode2' => '8' . substr($barcode, 1)
            ];
            
        case 'Especial':
            $ref = isset($_GET['Referencia']) ? $_GET['Referencia'] : '';
            
            if (empty($ref) || rand(0, 10) < 7) {
                return []; // La mayoría de productos no tienen precio especial
            }
            
            $currentDate = new DateTime();
            $endDate = clone $currentDate;
            $endDate->modify('+' . rand(1, 30) . ' days');
            
            return [
                'SpecialPrice' => rand(2, 50),
                'DateFrom' => $currentDate->format('Y-m-d'),
                'DateUntil' => $endDate->format('Y-m-d')
            ];
            
        default:
            return [];
    }
}

/**
 * Función para registrar errores en un archivo de log
 * 
 * @param string $message Mensaje de error
 * @param string $level Nivel de error (ERROR, WARNING, INFO)
 */
function logError($message, $level = 'ERROR') {
    $logFile = __DIR__ . '/logs/dashboard_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    
    // Crear directorio de logs si no existe
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Formatear mensaje
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Escribir en el archivo de log
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

/**
 * Función para limpiar y validar datos de entrada
 * 
 * @param mixed $data Datos a limpiar
 * @return mixed Datos limpios
 */
function sanitizeInput($data) {
    // Paso 1: Si la entrada es un arreglo, aplica la función a cada elemento de forma recursiva.
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    // Paso 2: **VALIDACIÓN CRÍTICA**
    // Si la entrada no es una cadena de texto (después de que ya no es un arreglo),
    // no la procesamos. Esto previene el error de tipo.
    if (!is_string($data)) {
        return ''; // Devuelve una cadena vacía o maneja el error de otra forma
    }
    
    // Paso 3: **Solo si es una cadena, aplicamos las funciones de saneamiento.**
    // Eliminar espacios en blanco al inicio y final
    $data = trim($data);
    
    // Eliminar barras invertidas. stripslashes() solo funciona en cadenas.
    $data = stripslashes($data);
    
    // Convertir caracteres especiales a entidades HTML.
    // Usamos ENT_QUOTES para manejar comillas simples y dobles.
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Función para validar fechas
 * 
 * @param string $date Fecha a validar en formato YYYY-MM-DD
 * @return bool True si la fecha es válida, false en caso contrario
 */
function isValidDate($date) {
    if (empty($date)) {
        return false;
    }
    
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Función para obtener la fecha actual en formato YYYY-MM-DD
 * 
 * @return string Fecha actual
 */
function getCurrentDate() {
    return date('Y-m-d');
}

/**
 * Función para obtener una fecha anterior en formato YYYY-MM-DD
 * 
 * @param int $days Número de días a restar
 * @return string Fecha anterior
 */
function getPastDate($days) {
    return date('Y-m-d', strtotime("-$days days"));
}

// Inicialización
// Establecer zona horaria
date_default_timezone_set('America/Puerto_Rico');

// Configurar manejo de errores
ini_set('display_errors', DEBUG_MODE ? 1 : 0);
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    logError("PHP Error [$errno]: $errstr in $errfile on line $errline");
}, E_ALL);

// Registrar errores de PHP
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logError("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}", 'FATAL');
    }
});


function mostrarErrorBackend(){

// Evitar que se muestre más de una vez
static $mostrado = false;

if($mostrado){
    return;
}

$mostrado = true;

?>

<div style="
    width: 500px;
    margin: 20px auto;
    padding: 25px;
    background: #f8d7da;
    border-radius: 10px;
    text-align:center;
    font-family: Arial;
    box-shadow: 0px 0px 10px #ccc;
">

<h2 style="color:#721c24;">
⚠ Sistema temporalmente no disponible
</h2>

<p>
No se pudo conectar con el servidor.
</p>

<p>
Intente nuevamente en unos minutos.
</p>

<button onclick="location.reload()" style="
padding:10px 20px;
background:#007bff;
color:white;
border:none;
border-radius:5px;
cursor:pointer;
">
Reintentar
</button>

</div>

<?php

}