<?php
function get_configBackend() {
    $file = __DIR__ . '/backend_config.json';
    if (file_exists($file)) {
        $json = file_get_contents($file);
        return json_decode($json, true);
    }
    return null;
}

function save_configBackend($backend_ip, $backend_port, $extra_backends = []) {
    $file_path = __DIR__ . '/backend_config.json';

    // 1. Leer y 2. Decodificar
    if (file_exists($file_path)) {
        $current_content = file_get_contents($file_path);
        $existing_config = json_decode($current_content, true);
    } else {
        $existing_config = [];
    }
    
    // 3. Crear los nuevos datos y Combinar (Merge)
    $new_data = [
        'backend_ip' => $backend_ip,
        'backend_port' => $backend_port,
        'extra_backends' => $extra_backends
    ];
    
    $merged_config = array_merge($existing_config, $new_data);

    // 4. Escribir la matriz combinada de vuelta
    file_put_contents($file_path, json_encode($merged_config, JSON_PRETTY_PRINT));
}
function save_inventoryMonthsOfCover($months) {
    $file_path = __DIR__ . '/backend_config.json';

    // 1. Leer y 2. Decodificar
    if (file_exists($file_path)) {
        $current_content = file_get_contents($file_path);
        $existing_config = json_decode($current_content, true);
    } else {
        $existing_config = [];
    }
    
    // 3. Crear los nuevos datos y Combinar (Merge)
    $new_data = [
        'inventoryMonthsOfCover' => $months
    ];
    
    $merged_config = array_merge($existing_config, $new_data);

    // 4. Escribir la matriz combinada de vuelta
    file_put_contents($file_path, json_encode($merged_config, JSON_PRETTY_PRINT));
}

?>
