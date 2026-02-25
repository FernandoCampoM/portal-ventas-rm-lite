<?php
require_once 'config_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leer el cuerpo crudo de la petición y decodificar JSON
    $input = json_decode(file_get_contents("php://input"), true);

    $backend_ip = $input['backend_ip'] ?? '';
    $backend_port = $input['backend_port'] ?? '';
    $extra_backends = $input['extra_backends'] ?? [];
    $monthsOfCover = $input['inventoryMonthsOfCover'] ?? '';

        if (!empty($monthsOfCover)) {
            save_inventoryMonthsOfCover($monthsOfCover);
            echo json_encode(["status" => "ok"]);
        }else if (!empty($backend_ip) && !empty($backend_port)) {
        save_configBackend($backend_ip, $backend_port, $extra_backends);
        $savedConfig = get_configBackend(); // Opcional para depuración

        echo json_encode(["status" => "ok"]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Por favor, ingresa IP y puerto."
        ]);
    }
}
