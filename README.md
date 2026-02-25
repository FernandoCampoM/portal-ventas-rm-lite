# PORTAL DE VENTAS RM LITE

El **Portal de Ventas RM Lite** es una versión ligera del sistema Retail Manager diseñada para la gestión de clientes y productos.

Esta versión Lite permite conectarse a uno o varios servidores backend para consultar información como productos y su stock de forma rápida y eficiente.

---

## Características

- Consulta de productos
- Consulta de Clientes
- Soporte para múltiples backends
- Interfaz web moderna y responsiva
- Sistema rápido y optimizado

---

## Requisitos

- Windows 
- PowerShell habilitado

---

## Instalación

### Paso 1
Descargue o clone el repositorio.

### Paso 2
Abra PowerShell **como Administrador**.

### Paso 3
Ubíquese en la carpeta del proyecto.

Ejemplo:
```powershell
cd C:\xampp\htdocs\dashboard-rm-lite
```
### Paso 4

Ejecute el instalador:
```powershell
install.ps1
```
Si PowerShell bloquea la ejecución:
```powershell
Set-ExecutionPolicy RemoteSigned -Scope CurrentUser
```
Luego ejecute nuevamente:

```powershell
.\install.ps1
```
---

## Primer Inicio

Abrir en el navegador:

http://localhost

Configurar el Backend principal.

---

## Estructura

```
dashboard-rm-lite/
│
├── authentication/
├── css/
├── images/
├── js/
├── logs/
├── setup/
├── view/
│
├── .gitattributes
├── .gitignore
├── api-client.js
├── api_debug.php
├── api_proxy.php
├── check_api.php
├── config.php
├── config_check.php
├── dashboard.html
├── dashboard.php
├── debug.php
├── export_products.php
├── index.php
├── install.ps1
├── maintenance-sections.html
├── modalReceiveInventory.php
├── product_search.php
├── README.md
├── scripts.php
├── test_api.php
└── test_clientes.php
```
---

## Configuración

El sistema permite configurar:

- Backend principal
- Backends adicionales
- Parámetros de inventario

---

## Autor

Fernando Campo M

