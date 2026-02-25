// js/clients.js

// Variables globales para almacenar categorías de clientes
let clientCategories = [];
let clientsTable;

// Función para formatear moneda
function formatCurrency(number) {
  return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(number);
}
// Función para formatear fecha
function formatDate(dateString) {
  if (!dateString) return "";
  const date = new Date(dateString);

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0"); // meses son 0-11
  const day = String(date.getDate()).padStart(2, "0");

  const hours = String(date.getHours()).padStart(2, "0");
  const minutes = String(date.getMinutes()).padStart(2, "0");
  const seconds = String(date.getSeconds()).padStart(2, "0");

  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}


// Función para mostrar notificaciones toast con registro en consola
function showToast(title, message, type) {
  // Si estás usando una biblioteca de toast, implementa aquí
  // De lo contrario, puedes usar alert por ahora
  alert(`${title}: ${message}`);
}

// Función para activar/desactivar estado de carga
function toggleLoading(isLoading) {
  const loadingOverlay = document.getElementById("loadingOverlay");
  if (loadingOverlay) {
    loadingOverlay.style.display = isLoading ? "flex" : "none";
  }
}

// Función para extraer datos de la respuesta de la API
function extractApiData(data) {
  // Verifica si data tiene propiedad RESULT (estructura anidada)
  if (data && data.RESULT && Array.isArray(data.RESULT)) {
    // Si RESULT es un array de arrays, toma el primer array
    if (Array.isArray(data.RESULT[0])) {
      return data.RESULT[0];
    }
    // Si RESULT es solo un array de objetos, devuélvelo directamente
    return data.RESULT;
  }

  // Si data ya es un array, devuélvelo como está
  if (Array.isArray(data)) {
    return data;
  }

  // Si no podemos determinar la estructura, devolver array vacío
  console.error("Estructura de datos desconocida:", data);
  return [];
}

// Función para registrar respuestas de la API
function logApiResponse(endpoint, data) {
  const extractedData = extractApiData(data);
  if (!extractedData || extractedData.length === 0) {
    console.warn(`Advertencia: Datos vacíos extraídos de ${endpoint}`);
  }
  return extractedData;
}

// Función para cargar clientes desde la API
export async function loadClients() {
  
  // Cargar categorías primero para asegurar que las tenemos disponibles
  await loadClientCategories();
  await loadClientsData();
}

// Función que realmente carga los datos de clientes
async function loadClientsData() {

  // Obtener valores de filtros
  const nameFilter = document.getElementById("clientNameFilter") 
    ? document.getElementById("clientNameFilter").value 
    : "";
  const categoryFilter = document.getElementById("clientCategoryFilter")
    ? document.getElementById("clientCategoryFilter").value
    : "";
  const cityFilter = document.getElementById("clientCityFilter")
    ? document.getElementById("clientCityFilter").value
    : "";

 

  // Construir parámetros de consulta
  let queryParams = {};
  if (nameFilter) queryParams.Name = encodeURIComponent(nameFilter);
  if (categoryFilter) queryParams.Category = encodeURIComponent(categoryFilter);
  if (cityFilter) queryParams.City = encodeURIComponent(cityFilter);

  try {
    const userLoggedResponse = await fetchData("GetLoggedUserId");
    if(!userLoggedResponse || !userLoggedResponse.success || !userLoggedResponse.username){
      showToast("Error", "No se pudo obtener el nombre del usuario logueado", "error");
      return;
    }
    const username = userLoggedResponse.username;
    const data = await fetchData("Clients", queryParams);
    const clients = logApiResponse("Clients", data);

      if (clients && clients.length > 0) {

        // Preparar datos para la tabla
        const tableData = clients.filter((client) => {
          return username.trim().toLowerCase().includes(client.SalespersonName.toLowerCase().trim());
        }).map((client) => {

          // Buscar el nombre de la categoría
          const category = clientCategories.find((c) => c.CategoryID === client.Category);
          const categoryName = category ? category.CategoryName : client.Category;

          // Crear botones de acción
          const editButton = `<button class="btn btn-sm btn-primary edit-client" data-id="${client.ClientID}"><i class="fas fa-edit"></i></button>`;
          const deleteButton = `<button class="btn btn-sm btn-danger ms-1 delete-client" data-id="${client.ClientID}"><i class="fas fa-trash"></i></button>`;

          return [
            client.ClientID || "",
            client.ClientName || "",
            client.LastName || "",
            client.City || "",
            client.Phone || "",
            client.Email || "",
            client.Category || "",
            formatCurrency(client.Balance || 0),
            formatDate(client.LastPurchaseDate || 0),
            client.IsActive === "Si" ? 
              '<span class="badge bg-success">Activo</span>' : 
              '<span class="badge bg-danger">Inactivo</span>',
            editButton + deleteButton
          ];
        });


        try {
          // Verificar si existe el elemento de la tabla
          const tableElement = document.getElementById("clientsTable");
          if (!tableElement) {
            console.error("Elemento de tabla no encontrado");
            showToast("Error", "No se encontró la tabla de clientes", "error");
            return;
          }


          // Verificar si jQuery está disponible
          if (typeof jQuery === "undefined") {
            console.error("¡jQuery no está cargado!");
            showToast("Error", "jQuery no está cargado. Verifique las dependencias.", "error");
            return;
          }

          // Usar jQuery de forma segura
          const $ = jQuery;

          // Verificar si DataTables está disponible
          if (!$.fn.DataTable) {
            console.error("¡DataTables no está cargado!");
            showToast("Error", "DataTables no está cargado. Verifique las dependencias.", "error");
            return;
          }

          // Definir columnas con ancho y alineación adecuados
          const columns = [
            { title: "ID", data: 0, width: "5%" },
            { title: "Nombre", data: 1, width: "15%" },
            { title: "Apellido", data: 2, width: "15%" },
            { title: "Ciudad", data: 3, width: "10%" },
            { title: "Teléfono", data: 4, width: "12%" },
            { title: "Email", data: 5, width: "15%" },
            { title: "Categoría", data: 6, width: "10%" },
            { title: "Balance", data: 7, width: "8%", className: "text-center" },
            { title: "Última Compra", data: 8, width: "8%", className: "text-center" },
            { title: "Estado", data: 9, width: "8%", className: "text-center" },
            { title: "Acciones", data: 10, width: "10%", className: "text-center", orderable: false }
          ];

          // Inicializar o actualizar la tabla
          if ($.fn.DataTable.isDataTable("#clientsTable")) {
            $("#clientsTable").DataTable().clear().rows.add(tableData).draw();
          } else {
            clientsTable = $("#clientsTable").DataTable({
              data: tableData,
              columns: columns,
              language: {
                url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-AR.json",
              },
              responsive: true,
              autoWidth: false, // Desactivar cálculo automático de ancho
              columnDefs: [
                { responsivePriority: 1, targets: [0, 1, 8] }, // Estas columnas son las más importantes
                { responsivePriority: 2, targets: [4, 7] }     // Estas columnas son las siguientes más importantes
              ],
              dom:
                '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                '<"row"<"col-sm-12"tr>>' +
                '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
              initComplete: function () {
                // Añadir algo de estilo personalizado después de inicializar la tabla
                $(this).closest(".dataTables_wrapper").addClass("card-body p-0");
              },
            });

            // Añadir eventos a los botones de acción - solo añadir una vez
            $(document).on("click", ".edit-client", function () {
              const clientId = $(this).data("id");
              editClient(clientId);
            });

            $(document).on("click", ".delete-client", function () {
              const clientId = $(this).data("id");
              deleteClient(clientId);
            });
          }

        } catch (error) {
          console.error("Error al inicializar DataTable:", error);
          showToast("Error", "Error al inicializar la tabla de clientes: " + error.message, "error");
        }
      } else {
        console.error("No se encontraron clientes en los datos:", data);
        showToast("Información", "No se encontraron clientes con los filtros seleccionados", "info");
      }
    }
   catch(error) {
      clearTimeout(timeoutId); // Limpiar el timeout
      console.error("Error cargando clientes:", error);
      showToast("Error", "No se pudieron cargar los clientes: " + error.message, "error");
    }
}

// Función para cargar categorías de clientes
async function loadClientCategories(callback) {
 try {
   const data = await fetchData("ClientCategories");
   const categories = logApiResponse("ClientCategories", data);

      if (categories && categories.length > 0) {
        clientCategories = categories;

        // Llenar el select de categorías para filtros
        const filterSelect = document.getElementById("clientCategoryFilter");
        if (filterSelect) {
          filterSelect.innerHTML = '<option value="">Todas las categorías</option>';

          categories.forEach((category) => {
            const filterOption = document.createElement("option");
            filterOption.value = category.CategoryID;
            filterOption.textContent = category.CategoryName;
            filterSelect.appendChild(filterOption);
          });
        } else {
          console.warn("Select de filtro de categorías no encontrado");
        }

        // Llenar el select de categorías para el formulario
        const formSelect = document.getElementById("clientCategory");
        if (formSelect) {
          formSelect.innerHTML = '<option value="">Seleccionar categoría</option>';

          categories.forEach((category) => {
            const formOption = document.createElement("option");
            formOption.value = category.CategoryID;
            formOption.textContent = category.CategoryName;
            formSelect.appendChild(formOption);
          });
        }

        // Llamar a la función callback si se proporciona
        if (typeof callback === "function") {
          callback();
        }
      } else {
        console.error("No se encontraron categorías en los datos:", data);
        showToast("Error", "No se encontraron categorías de clientes", "error");

        // Llamar a la función callback incluso en caso de error para continuar el flujo
        if (typeof callback === "function") {
          callback();
        }
      }
    }catch(error) {
      clearTimeout(timeoutId); // Limpiar el timeout
      console.error("Error cargando categorías de clientes:", error);
      showToast("Error", "No se pudieron cargar las categorías: " + error.message, "error");

      // Llamar a la función callback incluso en caso de error para continuar el flujo
      if (typeof callback === "function") {
        callback();
      }
    }
}

// Función para editar un cliente
function editClient(clientId) {
  
  // Validar ID de cliente
  if (!clientId) {
    console.error("No se proporcionó un ID de cliente");
    showToast("Error", "No se proporcionó un ID de cliente válido", "error");
    return;
  }
  
  // Mostrar indicador de carga
  toggleLoading(true);
  
  // Intentar diferentes formatos de endpoint de API
  const apiEndpoints = [
    `api_proxy.php?endpoint=Clients&ClientID=${encodeURIComponent(clientId)}`
  ];
  
  console.log("Probando endpoint antes del primero:"+apiEndpoints[0]);
  // Probar cada endpoint en secuencia
  tryNextEndpoint(0);
  
  function tryNextEndpoint(index) {
    console.log("Probando endpoint:"+index, apiEndpoints[index]);
    if (index >= apiEndpoints.length) {
      console.error("Todos los endpoints de API fallaron");
      toggleLoading(false);
      showToast("Error", "No se pudo cargar los detalles del cliente después de intentar múltiples endpoints", "error");
      return;
    }
    console.log("eNDPOINT A PROBAR:"+index, apiEndpoints[index]);
    const apiUrl = apiEndpoints[index];
    
    fetch(apiUrl)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`¡Error HTTP! Estado: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        console.log(`Datos recibidos del endpoint ${index}:`, data);
        // Si data es false o vacío, probar el siguiente endpoint
        if (!data || (typeof data === "object" && Object.keys(data).length === 0)) {
          tryNextEndpoint(index + 1);
          return;
        }
        
        // Intentar extraer el cliente de diferentes estructuras de datos
        const client = extractClientFromResponse(data, clientId);
        console.log(`Cliente con id ${clientId} extraído del endpoint ${index}:`, client);
        if (client) {
          populateClientForm(client);
        } else {
          tryNextEndpoint(index + 1);
        }
      })
      .catch((error) => {
        console.error(`Error con endpoint ${index + 1}:`, error);
        tryNextEndpoint(index + 1);
      });
  }
  
  // Función auxiliar para extraer cliente de diferentes estructuras de respuesta
  function extractClientFromResponse(data, clientId) {
    console.log("Extrayendo tipo dato:", Array.isArray(data)?"Array":"Objeto");
    // Caso 1: data.RESULT es un array de clientes
    if (data.RESULT && Array.isArray(data.RESULT)) {
      // Si es un array de arrays, tomar el primer array
      if (Array.isArray(data.RESULT[0])) {
        const clients = data.RESULT[0];
        return clients.find((c) => c.ClientID === clientId);
      }
      // Si RESULT es solo un array de objetos, buscar en él
      return data.RESULT.find((c) => c.ClientID === clientId);
    }
    
    // Caso 2: data es un array de clientes
    if (Array.isArray(data)) {
      const clientF=data.find((c) => c.ClientID == clientId);
      
      return clientF;
    }
    // Caso 3: data es el objeto cliente en sí
    if (typeof data === "object" && data !== null) {
      if (data.ClientID === clientId) {
        return data;
      }
    }
    
    return null;
  }
  
  // Función auxiliar para poblar el formulario con datos del cliente
  function populateClientForm(client) {

    // Resetear el formulario
    document.getElementById("clientForm").reset();
    
    document.getElementById("clientBalanceDiv").classList.remove("d-none");
    document.getElementById("clientLastPurchaseDateDiv").classList.remove("d-none");
    document.getElementById("saveClientBtn").classList.add("d-none");
    document.getElementById("apellidoDiv").classList.remove("d-none");
    // Mapear los nombres de campo de la API a los IDs de campo del formulario
    const fieldMappings = {
      ClientID: "clientId",
      ClientName: "clientName",
      LastName: "clientLastName",
      Address1: "clientAddress1",
      Address2: "clientAddress2",
      City: "clientCity",
      ZipCode: "clientZipCode",
      Country: "clientCountry",
      Phone: "clientPhone",
      Email: "clientEmail",
      Category: "clientCategory",
      IsActive: "clientActive",
      CreditLimit: "clientCreditLimit",
      Balance: "clientBalance",
      LastPurchaseDate: "clientLastPurchaseDate"
    };
    loadClientCategories( async () => {
      for (const [apiField, formField] of Object.entries(fieldMappings)) {
      const formElement = document.getElementById(formField);
      if (formElement && client[apiField] !== undefined) {
        switch(apiField) {
          case "IsActive":
            formElement.value = client[apiField] === "No" ? "N" : "S";
            break;
          case "LastPurchaseDate":
            formElement.value = formatDate(client[apiField] || "");
            break;
          case "Balance":
            formElement.value = formatCurrency(client[apiField] || 0);
            break;
          case "CreditLimit":
            formElement.value = formatCurrency(client[apiField] || 0);
            break;
          case "Category":
            formElement.value = await buscarIdCategoria(client[apiField]);
            break;
          default:
            formElement.value = client[apiField] || "";
        }
      }
    }
    })
    
    // Establecer valores del formulario basados en los mapeos
    
    
    // Mostrar formulario
    document.getElementById("clientModalLabel").textContent = "Editar Cliente";
    
    // Verificar si jQuery está definido
    if (typeof jQuery === "undefined") {
      console.error("¡jQuery no está cargado!");
      showToast("Error", "jQuery no está cargado. Verifique las dependencias.", "error");
      return;
    }
    
    jQuery("#clientModal").modal("show");
    toggleLoading(false);
  }
}
async function buscarIdCategoria(categoryName) {
  toggleLoading(true);


  try {
    const data = await fetchData('ClientCategories');
    const categories = logApiResponse("ClientCategories", data);
    if (categories && categories.length > 0) {
      clientCategories = categories;

      // Buscar la categoría
      const category = categories.find(c => c.CategoryName === categoryName);

      if (category) {
        return category.CategoryID; // ✅ Devuelve directamente el ID
      }
    }

    showToast("Error", "No se encontraron categorías de clientes", "error");
    return null;

  } catch (error) {
    if (error.name === "AbortError") {
      console.error("Error: solicitud abortada por timeout");
    } else {
      console.error("Error cargando categorías de clientes:", error);
      showToast("Error", "No se pudieron cargar las categorías: " + error.message, "error");
    }
    return null;

  } finally {
    toggleLoading(false);
  }
}

// Función para eliminar un cliente
function deleteClient(clientId) {
  
  if (confirm("¿Está seguro que desea eliminar este cliente?")) {
    toggleLoading(true);
    
    fetch(`api_proxy.php?endpoint=DeleteClient&ClientID=${encodeURIComponent(clientId)}`, {
      method: "POST"
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`¡Error HTTP! Estado: ${response.status}`);
        }
        return response.json();
      })
      .then((result) => {
        
        if (result.success) {
          showToast("Éxito", "Cliente eliminado correctamente", "success");
          loadClients(); // Recargar tabla de clientes
        } else {
          showToast("Error", result.message || "No se pudo eliminar el cliente", "error");
        }
      })
      .catch((error) => {
        showToast("Error", "No se pudo eliminar el cliente: " + error.message, "error");
      })
      .finally(() => {
        toggleLoading(false);
      });
  }
}

// Función para guardar un cliente (crear o actualizar)
function saveClient(event) {
  event.preventDefault();
 
  const clientId = document.getElementById("clientId").value;
  const isNewClient = !clientId;
  console.log("ID del cliente:", clientId, "Es nuevo:", isNewClient);
  if (isNewClient) {
    fetchData('GetNewClientID')
    .then(data => {
        console.log("Nuevo ID de cliente obtenido:", data.userId);
        if(data.success){
          let queryParams={Number:data.userId,
            Name: document.getElementById("clientName").value,
            Phone: document.getElementById("clientPhone").value,
            Email: document.getElementById("clientEmail").value,
            Category: document.getElementById("clientCategory").value,
            Salesman: 1,
            CreditLimit: document.getElementById("clientCreditLimit").value
          }; 
          
          const clientData = {
            ClientID: data.userId || "",
            FirstName: document.getElementById("clientName").value,
            LastName: document.getElementById("clientLastName").value,
            Address1: document.getElementById("clientAddress1").value,
            Address2: document.getElementById("clientAddress2").value,
            City: document.getElementById("clientCity").value,
            ZipCode: document.getElementById("clientZipCode").value,
            Country: document.getElementById("clientCountry").value,
            Phone: document.getElementById("clientPhone").value,
            Email: document.getElementById("clientEmail").value,
            Category: document.getElementById("clientCategory").value,
            Active: document.getElementById("clientActive").value
          };
           // Determinar endpoint basado en si es un cliente nuevo o una actualización
          const endpoint = 'ClientCreateNew';
          
          toggleLoading(true);
          
          fetchData(endpoint, queryParams)
            .then((result) => {
              console.log("Response status antes de comprobar:", result);
              if (result.status === false || result.status !=201) {
                throw new Error(`¡Error HTTP! Estado: ${result.message}`);
              }else{
                //showToast("Éxito", `Cliente ${isNewClient ? "creado" : "actualizado"} correctamente`, "success");
                Swal.fire({
                title: "Éxito",
                text: `Cliente ${isNewClient ? "creado" : "actualizado"} correctamente`,
                icon: "success",
                timer: 1500,
                showConfirmButton: false
              });
                // Verificar si jQuery está definido
                if (typeof jQuery === "undefined") {
                  console.error("¡jQuery no está cargado!");
                  showToast("Error", "jQuery no está cargado. Verifique las dependencias.", "error");
                  return;
                }
                
                jQuery("#clientModal").modal("hide");
                loadClients(); // Recargar tabla de clientes
              } 
            })
            .catch((error) => {
              console.error(`Error ${isNewClient ? "creando" : "actualizando"} cliente:`, error);
              showToast("Error", `No se pudo ${isNewClient ? "crear" : "actualizar"} el cliente: ` + error.message, "error");
            })
            .finally(() => {
              toggleLoading(false);
            });
        }
      });
    
  }else{

  // Recopilar datos del formulario
  const clientData = {
    ClientID: clientId,
    FirstName: document.getElementById("clientName").value,
    LastName: document.getElementById("clientLastName").value,
    Address1: document.getElementById("clientAddress1").value,
    Address2: document.getElementById("clientAddress2").value,
    City: document.getElementById("clientCity").value,
    ZipCode: document.getElementById("clientZipCode").value,
    Country: document.getElementById("clientCountry").value,
    Phone: document.getElementById("clientPhone").value,
    Email: document.getElementById("clientEmail").value,
    Category: document.getElementById("clientCategory").value,
    Active: document.getElementById("clientActive").value
  };
  
  
  // Determinar endpoint basado en si es un cliente nuevo o una actualización
  const endpoint = isNewClient ? "CreateClient" : "UpdateClient";
  
  toggleLoading(true);
  
  fetch(`api_proxy.php?endpoint=${endpoint}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(clientData)
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`¡Error HTTP! Estado: ${response.status}`);
      }
      return response.json();
    })
    .then((result) => {
      
      if (result.success) {
        showToast("Éxito", `Cliente ${isNewClient ? "creado" : "actualizado"} correctamente`, "success");
        
        // Verificar si jQuery está definido
        if (typeof jQuery === "undefined") {
          console.error("¡jQuery no está cargado!");
          showToast("Error", "jQuery no está cargado. Verifique las dependencias.", "error");
          return;
        }
        
        jQuery("#clientModal").modal("hide");
        loadClients(); // Recargar tabla de clientes
      } else {
        showToast("Error", result.message || `No se pudo ${isNewClient ? "crear" : "actualizar"} el cliente`, "error");
      }
    })
    .catch((error) => {
      console.error(`Error ${isNewClient ? "creando" : "actualizando"} cliente:`, error);
      showToast("Error", `No se pudo ${isNewClient ? "crear" : "actualizar"} el cliente: ` + error.message, "error");
    })
    .finally(() => {
      toggleLoading(false);
    });
  }
}
function GetNewClientID() {
  
  fetchData('GetNewClientID')
    .then(data => {
        const inputClientId = document.getElementById("clientId");
        if (inputClientId) {
          inputClientId.value = data.userId || "";
          console.log("Nuevo ID de cliente asignado:", inputClientId.value);
        } else {
          console.error("Elemento input para clientId no encontrado");
        }
      });
    
}
// Función para inicializar la sección de mantenimiento de clientes
export async function initClientMaintenance() {
  
  // Cargar datos iniciales
  await loadClients();

  // Configurar escuchadores de eventos
  const filterForm = document.getElementById("filterClientsForm");
  if (filterForm) {
    filterForm.addEventListener("submit", (event) => {
      event.preventDefault();
      loadClients();
    });
  }
  
  // Si no hay formulario de filtro, agregar escuchador al botón de búsqueda
  const applyFiltersBtn = document.getElementById("applyClientFilters");
  if (applyFiltersBtn) {
    applyFiltersBtn.addEventListener("click", () => {
      loadClients();
    });
  }
  
  const resetFiltersBtn = document.getElementById("resetClientFilters");
  if (resetFiltersBtn) {
    resetFiltersBtn.addEventListener("click", () => {
      // Si existe el formulario, limpiarlo
      if (filterForm) {
        filterForm.reset();
      } else {
        // Si no, limpiar los campos individuales
        const nameFilter = document.getElementById("clientNameFilter");
        if (nameFilter) nameFilter.value = "";
        
        const categoryFilter = document.getElementById("clientCategoryFilter");
        if (categoryFilter) categoryFilter.value = "";
        
        const cityFilter = document.getElementById("clientCityFilter");
        if (cityFilter) cityFilter.value = "";
      }
      loadClients();
    });
  }
  
  const addClientBtn = document.getElementById("addClientBtn");
  if (addClientBtn) {
    addClientBtn.addEventListener("click", () => {
      document.getElementById("clientForm").reset();
      document.getElementById("clientModalLabel").textContent = "Agregar Cliente";
      document.getElementById("clientId").value = ""; // Asegurarse de que el ID esté vacío para nuevos clientes
      document.getElementById("clientBalanceDiv").classList.add("d-none");
    document.getElementById("clientLastPurchaseDateDiv").classList.add("d-none");
    document.getElementById("saveClientBtn").classList.remove("d-none");
    document.getElementById("apellidoDiv").classList.add("d-none");
      // Verificar si jQuery está definido
      if (typeof jQuery === "undefined") {
        console.error("¡jQuery no está cargado!");
        showToast("Error", "jQuery no está cargado. Verifique las dependencias.", "error");
        return;
      }
      
      jQuery("#clientModal").modal("show");
    });
  }
  
  const clientForm = document.getElementById("clientForm");
  if (clientForm) {

    clientForm.addEventListener("submit", saveClient);
  }
  
  const saveClientBtn = document.getElementById("saveClientBtn");
  if (saveClientBtn) {

    saveClientBtn.addEventListener("click", function() {
      const clientForm = document.getElementById("clientForm");
      if (clientForm) {
        console.log("Disparando submit del formulario de cliente");
        // Crear un evento para disparar el submit del formulario
        const event = new Event("submit", {
          bubbles: true,
          cancelable: true
        });
        clientForm.dispatchEvent(event);
      }
    });
  }
}

/* // Inicializar cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  
  // Verificar si estamos en la página de mantenimiento de clientes
  if (document.getElementById("clientsTable")) {
    initClientMaintenance();
  } else {
    console.log("Tabla de clientes no encontrada, omitiendo inicialización");
  }
}); */

// Añadir manejador de errores global
window.addEventListener("error", (event) => {
  console.error("Error global capturado:", event.error);
  showToast("Error", "Se produjo un error en la aplicación. Consulte la consola para más detalles.", "error");
});

// Añadir manejador de rechazos de promesa no manejados
window.addEventListener("unhandledrejection", (event) => {
  console.error("Rechazo de promesa no manejado:", event.reason);
  showToast("Error", "Se produjo un error en una operación asíncrona. Consulte la consola para más detalles.", "error");
});
