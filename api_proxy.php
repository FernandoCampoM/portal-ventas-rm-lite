<?php
/**
 * API Proxy para el Dashboard
 * 
 * Este script funciona como intermediario entre el frontend JavaScript y la API del sistema.
 * Maneja la autenticación, el enrutamiento de solicitudes y el manejo de errores.
 */

// Incluir el archivo de configuración que contiene funciones y constantes
require_once 'config.php';
require_once 'setup/config_functions.php';
session_start();
$configBackend = get_configBackend();

// 2. Si el usuario ha iniciado sesión, el script continúa
// A partir de aquí, puedes acceder a los datos de la sesión:
$userID = -1;
$username = "";
$companyName = "";
// 1. Verificar si el usuario ha iniciado sesión
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $userID = $_SESSION['UserID'];
    $username = $_SESSION['Username'];
    $companyName = $_SESSION['InfoCompany']['Name'] ?? 'Tienda 1';
}



// Verificar que se proporcionó un endpoint
if (!isset($_GET['endpoint'])) {
    sendErrorResponse('No se especificó un endpoint', 400);
    exit;
}

$endpoint = $_GET['endpoint'];

// Lista de endpoints permitidos para solicitudes GET
$allowedGetEndpoints = [
    'validez',
    'LogInDashboard',
    'inventoryMonthsOfCover', 
    'ProdMovementChart',
    'ProdCreateNewItem',
    'ProdPreOrdChange',
    'ProdLabelPrint',
    'ProdUnits',
    'RecReceiveInventory',
    'ClientCreateNew',
    'GetLoggedUserId',
    'GetNewClientID',
    'InfoCompany', 
    'SalesTotals', 
    'SalesByCategory', 
    'SalesByDepartment', 
    'SalesByHour', 
    'SalesByMethod',
    'SaleTrendByMonth', 
    'InventoryDepartments', 
    'InventoryCategories', 
    'GetAllProducts', 
    'GetProduct', 
    'GetProductDetails',
    'LowLevelItems', 
    'InventoryValue', 
    'TopSellProducts',
    'LowSellProducts',
    'Clients',
    'ClientCategories',
    'GetProductImage',
    'GetEmployees',
    'ProdNameChange',
    'ProdBarcodeChange',
    'ProdCostChange',
    'ProdPriceChange',
    'ProdDepartmentChange',
    'ProdCategoryChange'
];

// Lista de endpoints permitidos para solicitudes POST
$allowedPostEndpoints = [
    'CreateProduct',
    'UpdateProduct',
    'DeleteProduct',
    'CreateClient',
    'UpdateClient',
    'DeleteClient',
    'UploadProductImage',
    'DeleteProductImage'
];

// Obtener los parámetros de la solicitud
$params = [];
foreach ($_GET as $key => $value) {
    if ($key !== 'endpoint') {
        $params[$key] = $value;
    }
}

// Log de la solicitud para depuración
$requestLogMessage = date('Y-m-d H:i:s') . " - Endpoint: {$endpoint} - Params: " . json_encode($params);
file_put_contents(__DIR__ . '/logs/api_requests.log', $requestLogMessage . PHP_EOL, FILE_APPEND);

try {
    // Manejar la solicitud según su método
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!in_array($endpoint, $allowedGetEndpoints)) {
            sendErrorResponse("Endpoint no permitido para solicitudes GET: {$endpoint}", 403);
            exit;
        }
        if($endpoint === 'GetLoggedUserId') {
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'userId' => $userID, 'username' => $username, 'companyName' => $companyName]);
            exit;
            
        }
        if($endpoint === 'inventoryMonthsOfCover') {
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'inventoryMonthsOfCover' => $configBackend['inventoryMonthsOfCover'] ?? 0]);
            exit;
            
        }
        if($endpoint === 'GetNewClientID') {
            $newClientID = '0000';
            //Obtenemos todos los clientes para determinar el siguiente ID
             $allClients = callAPI('Clients', []);
            $newClientID = '0000';
            if ($allClients === false) {
                $newClientID = '0001'; // Si hay un error, empezamos desde 0001
            }else{
                // Convertimos a números
                $idsNumericos = array_map(function($e) {
                    return intval($e["ClientID"]);
                }, $allClients);

                // Filtramos los que no son válidos (NaN en PHP sería cuando no es numérico, pero intval siempre devuelve número)
                $idsNumericos = array_filter($idsNumericos, function($n) {
                    return is_numeric($n);
                });

                // Obtenemos el máximo
                $maxId = max($idsNumericos);

                // Nuevo ID sumando 1 al máximo     
                $nuevoId = $maxId + 1;

                // Si quieres con ceros a la izquierda (4 dígitos)
                $nuevoIdStr = str_pad($nuevoId, 4, "0", STR_PAD_LEFT);
                $newClientID = $nuevoIdStr;
            }     
            // Generar un nuevo ClientID único
            
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'userId' => $newClientID]);
            
            exit;
        }
        // Realizar la llamada a la API
        $response = callAPI($endpoint, $params);

        // Log de la respuesta para depuración
        $responseLogMessage = date('Y-m-d H:i:s') . " - Success: {$endpoint}";
        file_put_contents(__DIR__ . '/logs/api_requests.log', $responseLogMessage . PHP_EOL, FILE_APPEND);
        if ($response && isset($response['status']) && $response['status'] === 403) {
            // Si la licencia falló, enviamos el código 403 y el JSON de respuesta.
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        // Si la respuesta es false, hubo un error en la API
        if ($response === false) {
            // Try to load mock data for easier development and testing
            if (DEBUG_MODE) {
                $mockData = getMockData($endpoint);
                if (!empty($mockData)) {
                    header('Content-Type: application/json');
                    echo json_encode($mockData);
                    exit;
                }
            }
            
            sendErrorResponse("Error calling API endpoint: {$endpoint}", 500);
            exit;
        }
        
        // Devolver la respuesta
        header('Content-Type: application/json');
        echo json_encode($response);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!in_array($endpoint, $allowedPostEndpoints)) {
            sendErrorResponse("Endpoint no permitido para solicitudes POST: {$endpoint}", 403);
            exit;
        }
        
        // Manejar endpoints que requieren carga de archivos
        if ($endpoint === 'UploadProductImage') {
            handleFileUpload();
            exit;
        }
        
        // Obtener el cuerpo de la solicitud
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            sendErrorResponse("JSON inválido en el cuerpo de la solicitud: " . json_last_error_msg(), 400);
            exit;
        }
        
        // Procesamiento específico según el endpoint
        switch ($endpoint) {
            case 'CreateProduct':
            case 'UpdateProduct':
                $response = handleProductOperation($endpoint, $data);
                break;
                
            case 'DeleteProduct':
                $response = handleProductDeletion($params);
                break;
                
            case 'CreateClient':
            case 'UpdateClient':
                $response = handleClientOperation($endpoint, $data);
                break;
                
            case 'DeleteClient':
                $response = handleClientDeletion($params);
                break;
                
            case 'DeleteProductImage':
                $response = handleProductImageDeletion($params);
                break;
                
            default:
                sendErrorResponse("Operación no implementada: {$endpoint}", 501);
                exit;
        }
        
        // Devolver la respuesta
        header('Content-Type: application/json');
        echo json_encode($response);
        
    } else {
        sendErrorResponse("Método no permitido: {$_SERVER['REQUEST_METHOD']}", 405);
        exit;
    }
} catch (Exception $e) {
    logError("Exception in API proxy: " . $e->getMessage());
    sendErrorResponse("Error del servidor: " . $e->getMessage(), 500);
}

/**
 * Envía una respuesta de error al cliente
 * 
 * @param string $message Mensaje de error
 * @param int $statusCode Código de estado HTTP
 */
function sendErrorResponse($message, $statusCode = 400) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => $message
    ]);
    
    // Log del error
    $errorLogMessage = date('Y-m-d H:i:s') . " - Error: {$message} (Status: {$statusCode})";
    file_put_contents(__DIR__ . '/logs/api_requests.log', $errorLogMessage . PHP_EOL, FILE_APPEND);
}

/**
 * Maneja operaciones CRUD de productos (crear/actualizar)
 * 
 * @param string $operation Tipo de operación (CreateProduct/UpdateProduct)
 * @param array $data Datos del producto
 * @return array Respuesta de la operación
 */
function handleProductOperation($operation, $data) {
    // Validar datos mínimos requeridos
    $requiredFields = ['ProductName', 'Price', 'Cost', 'Department', 'Category'];
    if ($operation === 'UpdateProduct') {
        $requiredFields[] = 'ProductCode';
    }
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            return [
                'success' => false,
                'message' => "Campo requerido faltante: {$field}",
                'errorCode' => 'INVALID_INPUT'
            ];
        }
    }
    
    // Preparar los parámetros de la API
    $apiParams = [
        'ItemCode' => $data['ProductCode'] ?? '',
        'Description' => $data['ProductName'],
        'Department' => $data['Department'],
        'Category' => $data['Category'],
        'Price' => $data['Price'],
        'Cost' => $data['Cost'],
        'Stock' => $data['Stock'] ?? 0,
        'Active' => $data['Active'] ?? '1'
    ];
    
    // Si hay código de barras, añadirlo
    if (!empty($data['BarCode'])) {
        $apiParams['Barcode'] = $data['BarCode'];
    }
    
    // Realizar la llamada a la API
    $apiEndpoint = ($operation === 'CreateProduct') ? 'CreateInventoryItem' : 'UpdateInventoryItem';
    $response = callAPI($apiEndpoint, $apiParams);
    
    // Si la respuesta es false, simular una respuesta exitosa para desarrollo
    if ($response === false && DEBUG_MODE) {
        return [
            'success' => true,
            'message' => "Producto " . ($operation === 'CreateProduct' ? 'creado' : 'actualizado') . " correctamente (MODO DEBUG)",
            'productId' => $data['ProductCode'] ?? 'NEW' . rand(1000, 9999)
        ];
    } elseif ($response === false) {
        return [
            'success' => false,
            'message' => "Error al " . ($operation === 'CreateProduct' ? 'crear' : 'actualizar') . " el producto",
            'errorCode' => 'API_ERROR'
        ];
    }
    
    // Procesar la respuesta de la API
    if (isset($response['success']) && $response['success']) {
        return [
            'success' => true,
            'message' => "Producto " . ($operation === 'CreateProduct' ? 'creado' : 'actualizado') . " correctamente",
            'productId' => $response['productId'] ?? $data['ProductCode']
        ];
    } else {
        return [
            'success' => false,
            'message' => $response['message'] ?? "Error al " . ($operation === 'CreateProduct' ? 'crear' : 'actualizar') . " el producto",
            'errorCode' => $response['errorCode'] ?? 'API_ERROR'
        ];
    }
}

/**
 * Maneja la eliminación de productos
 * 
 * @param array $params Parámetros de la solicitud
 * @return array Respuesta de la operación
 */
function handleProductDeletion($params) {
    // Validar que se proporcionó un código de producto
    if (empty($params['ItemCode'])) {
        return [
            'success' => false,
            'message' => "No se especificó un código de producto para eliminar",
            'errorCode' => 'INVALID_INPUT'
        ];
    }
    
    // Realizar la llamada a la API
    $response = callAPI('DeleteInventoryItem', $params);
    
    // Si la respuesta es false, simular una respuesta exitosa para desarrollo
    if ($response === false && DEBUG_MODE) {
        return [
            'success' => true,
            'message' => "Producto eliminado correctamente (MODO DEBUG)"
        ];
    } elseif ($response === false) {
        return [
            'success' => false,
            'message' => "Error al eliminar el producto",
            'errorCode' => 'API_ERROR'
        ];
    }
    
    // Procesar la respuesta de la API
    if (isset($response['success']) && $response['success']) {
        return [
            'success' => true,
            'message' => "Producto eliminado correctamente"
        ];
    } else {
        return [
            'success' => false,
            'message' => $response['message'] ?? "Error al eliminar el producto",
            'errorCode' => $response['errorCode'] ?? 'API_ERROR'
        ];
    }
}

/**
 * Maneja operaciones CRUD de clientes (crear/actualizar)
 * 
 * @param string $operation Tipo de operación (CreateClient/UpdateClient)
 * @param array $data Datos del cliente
 * @return array Respuesta de la operación
 */
function handleClientOperation($operation, $data) {
    // Validar datos mínimos requeridos
    $requiredFields = ['FirstName'];
    if ($operation === 'UpdateClient') {
        $requiredFields[] = 'ClientID';
    }
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            return [
                'success' => false,
                'message' => "Campo requerido faltante: {$field}",
                'errorCode' => 'INVALID_INPUT'
            ];
        }
    }
    
    // Preparar los parámetros de la API
    $apiParams = [
        'ClientID' => $data['ClientID'] ?? '',
        'FirstName' => $data['FirstName'],
        'LastName' => $data['LastName'] ?? '',
        'Address1' => $data['Address1'] ?? '',
        'Address2' => $data['Address2'] ?? '',
        'City' => $data['City'] ?? '',
        'ZipCode' => $data['ZipCode'] ?? '',
        'Country' => $data['Country'] ?? 'PR',
        'Phone' => $data['Phone'] ?? '',
        'Email' => $data['Email'] ?? '',
        'Category' => $data['Category'] ?? '',
        'Active' => $data['Active'] ?? 'S'
    ];
    
    // Realizar la llamada a la API
    $apiEndpoint = ($operation === 'CreateClient') ? 'CreateClient' : 'UpdateClient';
    $response = callAPI($apiEndpoint, $apiParams);
    
    // Si la respuesta es false, simular una respuesta exitosa para desarrollo
    if ($response === false && DEBUG_MODE) {
        return [
            'success' => true,
            'message' => "Cliente " . ($operation === 'CreateClient' ? 'creado' : 'actualizado') . " correctamente (MODO DEBUG)",
            'clientId' => $data['ClientID'] ?? 'NEW' . rand(1000, 9999)
        ];
    } elseif ($response === false) {
        return [
            'success' => false,
            'message' => "Error al " . ($operation === 'CreateClient' ? 'crear' : 'actualizar') . " el cliente",
            'errorCode' => 'API_ERROR'
        ];
    }
    
    // Procesar la respuesta de la API
    if (isset($response['success']) && $response['success']) {
        return [
            'success' => true,
            'message' => "Cliente " . ($operation === 'CreateClient' ? 'creado' : 'actualizado') . " correctamente",
            'clientId' => $response['clientId'] ?? $data['ClientID']
        ];
    } else {
        return [
            'success' => false,
            'message' => $response['message'] ?? "Error al " . ($operation === 'CreateClient' ? 'crear' : 'actualizar') . " el cliente",
            'errorCode' => $response['errorCode'] ?? 'API_ERROR'
        ];
    }
}

/**
 * Maneja la eliminación de clientes
 * 
 * @param array $params Parámetros de la solicitud
 * @return array Respuesta de la operación
 */
function handleClientDeletion($params) {
    // Validar que se proporcionó un ID de cliente
    if (empty($params['ClientID'])) {
        return [
            'success' => false,
            'message' => "No se especificó un ID de cliente para eliminar",
            'errorCode' => 'INVALID_INPUT'
        ];
    }
    
    // Realizar la llamada a la API
    $response = callAPI('DeleteClient', $params);
    
    // Si la respuesta es false, simular una respuesta exitosa para desarrollo
    if ($response === false && DEBUG_MODE) {
        return [
            'success' => true,
            'message' => "Cliente eliminado correctamente (MODO DEBUG)"
        ];
    } elseif ($response === false) {
        return [
            'success' => false,
            'message' => "Error al eliminar el cliente",
            'errorCode' => 'API_ERROR'
        ];
    }
    
    // Procesar la respuesta de la API
    if (isset($response['success']) && $response['success']) {
        return [
            'success' => true,
            'message' => "Cliente eliminado correctamente"
        ];
    } else {
        return [
            'success' => false,
            'message' => $response['message'] ?? "Error al eliminar el cliente",
            'errorCode' => $response['errorCode'] ?? 'API_ERROR'
        ];
    }
}

/**
 * Maneja la carga de imágenes de productos
 * 
 * @return void
 */
function handleFileUpload() {
    // Verificar que se haya subido un archivo
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => "Error al subir el archivo: " . ($_FILES['image']['error'] ?? 'No se recibió ningún archivo')
        ]);
        return;
    }
    
    // Verificar que se proporcionó un código de producto
    if (empty($_POST['productCode'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => "No se especificó un código de producto"
        ]);
        return;
    }
    
    $productCode = $_POST['productCode'];
    $uploadedFile = $_FILES['image'];
    
    // Validar tipo de archivo (solo imágenes)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($uploadedFile['type'], $allowedTypes)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => "Tipo de archivo no permitido. Solo se permiten imágenes (JPEG, PNG, GIF, WEBP)"
        ]);
        return;
    }
    
    // Crear directorio para imágenes si no existe
    $uploadDir = __DIR__ . '/uploads/products/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generar nombre de archivo único
    $extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
    $fileName = $productCode . '_' . md5(uniqid()) . '.' . $extension;
    $destination = $uploadDir . $fileName;
    
    // Mover el archivo subido
    if (!move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => "Error al guardar el archivo"
        ]);
        return;
    }
    
    // En un caso real, aquí llamaríamos a la API para asociar la imagen con el producto
    // Por ahora, simulamos una respuesta exitosa
    
    $imageUrl = 'uploads/products/' . $fileName;
    
    // Guardar la relación entre producto e imagen en un archivo JSON
    $productImagesFile = __DIR__ . '/data/product_images.json';
    $productImages = [];
    
    if (file_exists($productImagesFile)) {
        $productImagesJson = file_get_contents($productImagesFile);
        $productImages = json_decode($productImagesJson, true) ?? [];
    }
    
    $productImages[$productCode] = $imageUrl;
    
    file_put_contents($productImagesFile, json_encode($productImages, JSON_PRETTY_PRINT));
    
    // Devolver la respuesta
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => "Imagen subida correctamente",
        'imageUrl' => $imageUrl
    ]);
}

/**
 * Maneja la eliminación de imágenes de productos
 * 
 * @param array $params Parámetros de la solicitud
 * @return array Respuesta de la operación
 */
function handleProductImageDeletion($params) {
    // Validar que se proporcionó un código de producto
    if (empty($params['ItemCode'])) {
        return [
            'success' => false,
            'message' => "No se especificó un código de producto"
        ];
    }
    
    $productCode = $params['ItemCode'];
    
    // Obtener la información de la imagen asociada al producto
    $productImagesFile = __DIR__ . '/data/product_images.json';
    
    if (!file_exists($productImagesFile)) {
        return [
            'success' => false,
            'message' => "No se encontró la información de imágenes"
        ];
    }
    
    $productImagesJson = file_get_contents($productImagesFile);
    $productImages = json_decode($productImagesJson, true) ?? [];
    
    // Verificar si existe una imagen asociada al producto
    if (!isset($productImages[$productCode])) {
        return [
            'success' => false,
            'message' => "Este producto no tiene una imagen asociada"
        ];
    }
    
    $imageUrl = $productImages[$productCode];
    $imagePath = __DIR__ . '/' . $imageUrl;
    
    // Eliminar el archivo físico
    if (file_exists($imagePath) && !unlink($imagePath)) {
        return [
            'success' => false,
            'message' => "No se pudo eliminar el archivo de imagen"
        ];
    }
    
    // Eliminar la relación del archivo JSON
    unset($productImages[$productCode]);
    file_put_contents($productImagesFile, json_encode($productImages, JSON_PRETTY_PRINT));
    
    return [
        'success' => true,
        'message' => "Imagen eliminada correctamente"
    ];
}