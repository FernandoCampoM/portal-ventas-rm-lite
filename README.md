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

cd C:\xampp\htdocs\dashboard-rm-lite

### Paso 4

Ejecute el instalador:

install.ps1

Si PowerShell bloquea la ejecución:

Set-ExecutionPolicy RemoteSigned -Scope CurrentUser

Luego ejecute nuevamente:

.\install.ps1

---

## Primer Inicio

Abrir en el navegador:

http://localhost

Configurar el Backend principal.

---

## Estructura

dashboard-rm-lite/

authentication/
config/
css/
js/
images/
install.ps1
index.php

---

## Configuración

El sistema permite configurar:

- Backend principal
- Backends adicionales
- Parámetros de inventario

---

## Autor

Fernando Campo M

