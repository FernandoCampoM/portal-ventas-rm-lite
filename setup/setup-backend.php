<?php
require_once 'config_functions.php';
$configBackend = get_configBackend();
$editMode = isset($_GET['edit']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retail Manager Dashboard - Configuración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
    <style>
        body {
            background-color: #f4f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            text-align: center;
        }
        .card-config {
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>


<div class="login-container">
    <h2>Configuración de Backend Principal</h2>
    <div class="card card-config p-4">
        <div class="form-group mb-3">
            <label for="backend-ip" class="form-label text-start d-block">IP del backend:</label>
            <input type="text" id="backend-ip" class="form-control" placeholder="192.168.0.10" value="<?= htmlspecialchars($configBackend['backend_ip'] ?? '') ?>">
        </div>
        <div class="form-group mb-3">
            <label for="backend-port" class="form-label text-start d-block">Puerto del backend:</label>
            <input type="text" id="backend-port" class="form-control" placeholder="3000">
        </div>
        <hr class="mt-4">
        <h4>Backends secundarios</h4>
        <div id="extraBackendsContainer"></div>

        <button id="addBackend" class="btn btn-outline-primary mt-2">
        Agregar Backend
        </button>
        <button id="save-config" class="btn btn-primary w-100 mt-3">Guardar</button>
    </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let extraBackends = [];

function renderBackends(){

   const container =
      document.getElementById("extraBackendsContainer");

   container.innerHTML = "";

   extraBackends.forEach((b,i)=>{

      container.innerHTML += `
      <div class="card p-2 mb-2">

        <input class="form-control mb-1"
           placeholder="Nombre"
           value="${b.name || ''}"
           onchange="extraBackends[${i}].name=this.value">

        <input class="form-control mb-1"
           placeholder="IP"
           value="${b.ip || ''}"
           onchange="extraBackends[${i}].ip=this.value">

        <input class="form-control mb-1"
           placeholder="Puerto"
           value="${b.port || ''}"
           onchange="extraBackends[${i}].port=this.value">

        <button class="btn btn-sm btn-danger"
           onclick="removeBackend(${i})">
           Eliminar
        </button>

      </div>
      `;

   });
}

function removeBackend(i){

   extraBackends.splice(i,1);

   renderBackends();

}

document.getElementById("addBackend")
.addEventListener("click",()=>{

   extraBackends.push({
      name:"",
      ip:"",
      port:""
   });

   renderBackends();

});
function isEditMode() {
    return <?= $editMode ? 'true' : 'false' ?>;
}

document.getElementById("save-config").addEventListener("click", function () {
    const backend_ip = document.getElementById("backend-ip").value.trim();
    const backend_port = document.getElementById("backend-port").value.trim();
    
    if (backend_ip && backend_port) {
        const config = {
            backend_ip,
            backend_port,
            extra_backends: extraBackends
            };

        fetch("save_config.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(config)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "ok") {
                alert("Configuración guardada correctamente.");
                
                    window.location.href = "../index.php";
                
            } else {
                alert("Error al guardar la configuración en el servidor.");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Ocurrió un error de red al intentar guardar la configuración.");
        });
    } else {
        alert("Por favor ingresa IP y puerto.");
    }
});

window.addEventListener('DOMContentLoaded', () => {
    fetch("get_config.php")
        .then(res => res.json())
        .then(data => {
            if (data.status === "ok" && data.config) {
                const ip = data.config.backend_ip || '';
                const port = data.config.backend_port || '';
                document.getElementById('backend-ip').value = ip;
                document.getElementById('backend-port').value = port;
                extraBackends = data.config.extra_backends || [];
                renderBackends();

                if (!isEditMode()) {
                    window.location.href = "../authentication/login.php";
                }
            } else {
                document.getElementById('backend-ip').value = '';
                document.getElementById('backend-port').value = '';
            }
        })
        .catch(err => {
            console.error("Error al leer configuración del servidor:", err);
        });
});
</script>


</body>
</html>

