// js/maintenance.js (versión mejorada)

// Global variables to store product departments and categories
let productDepartments = []
let productCategories = []
let productsMaintenanceTable
let productsData = []
let stockBackend2Cache = {};
// Variable global para guardar los datos de movimiento por mes
let monthlyData = [];
let companyName = "";
let username = "";
const extraBackends = window.APP_CONFIG.extra_backends || [];


// Variable global para guardar la instancia del gráfico y poder destruirla/actualizarla
let productChart = null;

async function getCompanyNameFromApi(url) {
    const data = await fetchDataUseURL(url,"InfoCompany");
    if(data.length > 0){
        return data[0].Name || "Otra Tienda";
    }
    return "Otra Tienda";
}
function updateStockCell(productCode){

  let table = $("#productsMaintenanceTable").DataTable();

  table.rows().every(function(){

      let rowData = this.data();

      if(String(rowData[0]).trim() === String(productCode).trim()){

         let mainStock = rowData[5].split("<br>")[0];

         let secondStock = stockBackend2Cache[productCode] || 0;

         let newRow = [...rowData]; // 🔥 copia segura

         newRow[5] =
           mainStock +
           "<br>" +
           stockToHtml(
             safeToLocaleString(secondStock),
             stockBackend2Cache["companyName"]
           );

         this.data(newRow); // actualizar fila segura
      }

  });

  table.draw(false);

}
async function loadSecondStockParallel(products) {

  try {

    // Si ya tenemos datos en cache no volver a consultar
    if (Object.keys(stockBackend2Cache).length > 0) {

      products.forEach(p => updateStockCell(p.ProductCode));
      return;
    }

    // 🔹 UNA SOLA CONSULTA
    const data = await fetchDataUseURL(extraBackends[0].ip+":"+extraBackends[0].port,"GetAllProducts");
    const companyName = await getCompanyNameFromApi(extraBackends[0].ip+":"+extraBackends[0].port);
    stockBackend2Cache["companyName"] = companyName;
    /*
    Ejemplo esperado:

    [
      { ProductCode: "P001", Stock: 10 },
      { ProductCode: "P002", Stock: 5 },
      { ProductCode: "P003", Stock: 22 }
    ]
    */

    // Guardar en cache
    data.forEach(item => {

      stockBackend2Cache[item.ProductCode] = item.CurrentStock || 0;

    });

     
    // Actualizar tabla
    products.forEach(product => {
      
      updateStockCell(product.ProductCode);

    });
    
  }
  catch(e){

    console.error("Error cargando stocks backend2:", e);

  }

}

// Function to format currency
function formatCurrency(number) {
  return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(number)
}

// Function to safely convert to locale string, handling null/undefined
function safeToLocaleString(value) {
  return value != null ? value.toLocaleString() : ""
}

// Function to toggle loading state
function toggleLoading(isLoading) {
  
  const loadingOverlay = document.getElementById("loadingOverlay")
  if (loadingOverlay) {
    loadingOverlay.style.display = isLoading ? "flex" : "none"
  }
}

// Enhanced function to show toast notifications with console logging
function showToast(title, message, type) {
  
  // Si estás usando una biblioteca de toast, implementa esto aquí
  // De lo contrario, puedes usar alert por ahora
  alert(`${title}: ${message}`)
}

// Function to extract data from API response
function extractApiData(data) {
  // Check if data has RESULT property (nested structure)
  if (data && data.RESULT && Array.isArray(data.RESULT)) {
    // If RESULT is an array of arrays, take the first array
    if (Array.isArray(data.RESULT[0])) {
      return data.RESULT[0]
    }
    // If RESULT is just an array of objects, return it directly
    return data.RESULT
  }

  // If data is already an array, return it as is
  if (Array.isArray(data)) {
    return data
  }

  // If we can't determine the structure, return empty array
  console.error("Unknown data structure:", data)
  return []
}

// Debug function to log API responses
function logApiResponse(endpoint, data) {
  
  const extractedData = extractApiData(data)
  
  if (!extractedData || extractedData.length === 0) {
    console.warn(`Warning: Empty data extracted from ${endpoint}`)
  }
  return extractedData
}

// Function to load products from the API with enhanced debugging
async function loadProducts() {
  await loadProductDepartments();
  await loadProductCategories();
  await loadProductsData();
  
}
async function loadProductsWithFilters() {

  await filterProductsData();
  
}
function stockToHtml(stock, companyName = "Fernnandez Muebles") {
  
  return `${stock} - ${companyName}`;
}
async function filterProductsData() {
  if(companyName == ""){
    const userLoggedIn = await fetchData("GetLoggedUserId");
    companyName= userLoggedIn.companyName
    username = userLoggedIn.username
  }
  console.log("Applying filters to products data with companyName:", companyName)

  // Obtener valores de filtros
  const codeFilter = document.getElementById("productCodeFilter")
    ? document.getElementById("productCodeFilter").value
    : ""
  const nameFilter = document.getElementById("productNameFilter")
    ? document.getElementById("productNameFilter").value
    : ""
  const departmentSelect = document.getElementById("productDepartmentFilter");
let departmentFilter = departmentSelect
  ? departmentSelect.options[departmentSelect.selectedIndex].text
  : "";
  const categorySelect = document.getElementById("productCategoryFilter");
  let categoryFilter = categorySelect
    ? categorySelect.options[categorySelect.selectedIndex].text
  : "";

  

  if (departmentFilter && departmentFilter == "Todos los departamentos") departmentFilter = ""
  if (categoryFilter && categoryFilter == "Todas las categorías") categoryFilter = ""
console.log("Filter values:", {
    code: codeFilter,
    name: nameFilter,
    department: departmentFilter,
    category: categoryFilter,
  })
  try {
    console.log("Filtering products with filters:")
    let products = productsData.slice() // Copy the global products data
    products = products.filter(p => {
      const matchesCode = !codeFilter || p.ProductCode.toLowerCase().trim() == codeFilter.toLowerCase().trim();
      const matchesName = !nameFilter || p.ProductName.toLowerCase().includes(nameFilter.toLowerCase());
      const matchesDepartment = !departmentFilter || p.Department.toLowerCase().trim() == departmentFilter.toLowerCase();
      const matchesCategory = !categoryFilter || p.Category.toLowerCase().trim() == categoryFilter.toLowerCase();
      return matchesCode && matchesName && matchesDepartment && matchesCategory;
    });
    if (products && products.length > 0) {
      console.log(`Found ${products.length} products after filtering.`)
      // Preparar datos para la tabla
      const tableData = products.map((product) => {
        // Log each product for debugging


        // Buscar el nombre del departamento
        const department = productDepartments.find((d) => d.DepartmentName === product.Department)

        const departmentName = department ? department.DepartmentName : product.Department

        // Buscar el nombre de la categoría
        const category = productCategories.find((c) => c.CategoryName === product.Category)

        const categoryName = category ? category.CategoryName : product.Category

        // Crear botones de acción
        //const editButton = `<button class="btn btn-sm btn-primary edit-product" data-id="${product.ProductCode}"><i class="fas fa-edit"></i></button>`
        const deleteButton = `<button class="btn btn-sm btn-danger ms-1 delete-product" data-id="${product.ProductCode}"><i class="fas fa-trash"></i></button>`
        const recibirInventario = `<button class="btn btn-sm btn-secondary ms-1 receive-inventory" data-id="${product.ProductCode}" data-name="${product.ProductName}" data-barcode="${product.BarCode}"><i class="fas fa-square-plus"></i></button>`
        const tagButton = `<button class="btn btn-sm btn-secondary ms-1 tag-product" data-id="${product.ProductCode}" data-name="${product.ProductName}" data-barcode="${product.BarCode}"><i class="fas fa-tag"></i></button>`
        const pre_ordenButton = `<button class="btn btn-sm btn-secondary ms-1 pre-orden" data-id="${product.ProductCode}" data-id="${product.ProductCode}" data-name="${product.ProductName}" data-barcode="${product.BarCode}">Pre-Orden</button>`
        return [
          product.ProductCode || "",
          product.ProductName || "",
          product.BarCode || "",
          formatCurrency(product.Price || 0),
          formatCurrency(product.Cost || 0),
          stockToHtml(safeToLocaleString(product.CurrentStock),companyName) + "<br>" +"Cargando...",
          departmentName, // Mostrar el nombre del departamento en lugar del ID
          categoryName, // Mostrar el nombre de la categoría en lugar del ID
          // recibirInventario + tagButton + deleteButton, //+ editButton
          // pre_ordenButton
        ]
      })



      try {
        // Check if table element exists
        const tableElement = document.getElementById("productsMaintenanceTable")
        if (!tableElement) {
          console.error("Table element not found")
          showToast("Error", "No se encontró la tabla de productos", "error")
          return
        }



        // Check if jQuery is available
        if (typeof jQuery === "undefined") {
          console.error("jQuery is not loaded!")
          showToast("Error", "jQuery no está cargado. Verifique las dependencias.", "error")
          return
        }

        // Use jQuery safely
        const $ = jQuery

        // Check if DataTables is available
        if (!$.fn.DataTable) {
          console.error("DataTables is not loaded!")
          showToast("Error", "DataTables no está cargado. Verifique las dependencias.", "error")
          return
        }
        // Define columns with proper width and alignment
        const columns = [
          { title: "Código", data: 0, width: "10%" },
          { title: "Descripción", data: 1, width: "15%", className: "editable" },
          { title: "Código de Barras", data: 2, width: "10%", className: "editable" },
          { title: "Precio", data: 3, width: "8%", className: "editable" },
          { title: "Costo", data: 4, width: "8%", className: "editable" },
          { title: "Stock", data: 5, width: "20%", },
          { title: "Departamento", data: 6, width: "12%" },
          { title: "Categoría", data: 7, width: "12%" },
          // { title: "Acciones", data: 8, width: "10%", className: "text-center", orderable: false },
          // { title: "Movimiento", data: 9, width: "8%", className: "text-center", orderable: false },
        ]

        // Inicializar o actualizar la tabla
        if ($.fn.DataTable.isDataTable("#productsMaintenanceTable")) {

          $("#productsMaintenanceTable").DataTable().clear().rows.add(tableData).draw()
          setTimeout(() => {
            
              loadSecondStockParallel(products);
            }, 0);
        } else {

          productsMaintenanceTable = $("#productsMaintenanceTable").DataTable({
            data: tableData,
            columns: columns,
            language: {
              url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-AR.json",
            },
            responsive: true,
            autoWidth: false, // Disable auto width calculation
            columnDefs: [
              { responsivePriority: 1, targets: [0, 1] }, // These columns are most important
              { responsivePriority: 2, targets: [3, 7] }, // These columns are next important
            ],
            dom:
              '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
              '<"row"<"col-sm-12"tr>>' +
              '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            initComplete: function () {
              // Add some custom styling after table is initialized
              $(this).closest(".dataTables_wrapper").addClass("card-body p-0")
            },
          })
          showToast("Éxito", "Productos filtrados correctamente", "success")
          setTimeout(() => {
            
              loadSecondStockParallel(products);
            }, 0);
          /* // Añadir eventos a los botones de acción - only add once
          $(document).off("click", ".edit-product").on("click", ".edit-product", function () {
            
            const productCode = $(this).data("id")
            
            editProduct(productCode)
          })
*/
         /*  $(document).off("click", ".delete-product").on("click", ".delete-product", function () {

            const productCode = $(this).data("id")

            deleteProduct(productCode)
          })
          $(document).off("click", ".receive-inventory").on("click", ".receive-inventory", function () {
            fetch('modalReceiveInventory.php')
              .then(response => response.text())
              .then(html => {
                document.getElementById('receiveInventoryContainer').innerHTML = html;
                const productBarCode = $(this).data("barcode");
                const productName = $(this).data("name");
                const productCode = $(this).data("id");
                document.getElementById('movementContainer').classList.add('d-none');
                document.getElementById('ProdMovementChart').classList.add('d-none');
                document.getElementById('unidadesProductoGroup').classList.add('d-none'); // oculta
                document.getElementById('codigoProducto').innerText = productCode;
                document.getElementById('nombreProducto').innerText = productName;
                document.getElementById('barcodeProducto').innerText = productBarCode;
                document.getElementById('recibirProductoLabel').innerText = `Recibir Producto`;
                var configModal = new bootstrap.Modal(document.getElementById('recibirProductoModal'));
                configModal.show();
                // Agregar evento al botón de confirmar
                document.getElementById('btnRecibirInventario').addEventListener('click', function () {
                  const QuantityReceived = document.getElementById('cantidadProducto').value;
                  const userId = $("#userIdSpan").text()
                  receiveInventory(userId, productCode, QuantityReceived);
                  configModal.hide();
                });
              });
          });
          $(document).off("click", ".tag-product").on("click", ".tag-product", function () {
            const productCode = $(this).data("id");
            productLabelPrint(productCode);
          });
          $(document).off("click", ".pre-orden").on("click", ".pre-orden", function () {
            fetch('modalReceiveInventory.php')
              .then(response => response.text())
              .then(html => {

                document.getElementById('receiveInventoryContainer').innerHTML = html;
                const productBarCode = $(this).data("barcode");
                const productName = $(this).data("name");
                const productCode = $(this).data("id");
                getUnitsByProduct(productCode).then(units => {
                  if (!units || units.length === 0) {
                    const option = document.createElement('option');
                    option.value = 1;
                    option.text = 'Unidad- Each';
                    document.getElementById('unidadesProducto').appendChild(option);
                  }
                  units.forEach(unit => {
                    const option = document.createElement('option');
                    option.value = unit.UnitID;
                    option.text = unit.UnitDescription;
                    document.getElementById('unidadesProducto').appendChild(option);
                  });
                });
                loadChart(productCode);
                document.getElementById('movementContainer').classList.remove('d-none');
                document.getElementById('ProdMovementChart').classList.remove('d-none');
                document.getElementById('unidadesProductoGroup').classList.remove('d-none'); // muestra
                document.getElementById('codigoProducto').innerText = productCode;
                document.getElementById('nombreProducto').innerText = productName;
                document.getElementById('barcodeProducto').innerText = productBarCode;
                document.getElementById('recibirProductoLabel').innerText = `Pre-Orden Change`;
                var configModal = new bootstrap.Modal(document.getElementById('recibirProductoModal'));
                configModal.show();
                // Agregar evento al botón de confirmar
                document.getElementById('btnRecibirInventario').addEventListener('click', function () {
                  const QuantityReceived = document.getElementById('cantidadProducto').value;
                  const UnitID = document.getElementById('unidadesProducto').value;
                  const userId = $("#userIdSpan").text()
                  productPreOrderChange(userId, productCode, QuantityReceived, UnitID);
                  configModal.hide();
                });
              });
          }); */

        }
        //--------------
        // 🔹 Agregar modal al hacer un solo clic en una celda
        $('#productsMaintenanceTable tbody').on('click', 'td.editable', function (event) {
          let cell = productsMaintenanceTable.cell(this);
          let oldValue = cell.data();
          if (cell.index().column === 3 || cell.index().column === 4) {
            oldValue = oldValue.replace("$", "")
          }
          var row = cell.index().row;
          var valueCol0 = productsMaintenanceTable.cell(row, 0).data()
          // Llamamos al modal dinámico
          openDialog2("string", event, {
            id: cell.index().row,       // fila como referencia
            column: cell.index().column, // columna
            oldValue: oldValue, cell: cell, itemCode: valueCol0 // nodo TD
          });

          // Opcional: pasar el valor actual al input del modal
          setTimeout(() => {
            const input = document.querySelector("#datoInput");
            if (input) input.value = oldValue;
          }, 0);
        });

      } catch (error) {
        console.error("Error initializing DataTable:", error)
        showToast("Error", "Error al inicializar la tabla de productos: " + error.message, "error")
      }
    } else {
      console.error("No products found in data:", productsData)
      Swal.fire({
        icon: 'info',
        title: 'Información',
        text: 'No se encontraron productos con los filtros seleccionados',
        showConfirmButton: true,
        timer: 3000

      })
      
    }
  }
  catch (error) {
    console.error("Error cargando productos:", error)
    showToast("Error", "No se pudieron cargar los productos: " + error.message, "error")
  }
}

// Function that actually loads the product data
async function loadProductsData() {
  
  if(companyName == ""){
    const userLoggedIn = await fetchData("GetLoggedUserId");
    companyName= userLoggedIn.companyName
    username = userLoggedIn.username
  }



  // Obtener valores de filtros
  const codeFilter = document.getElementById("productCodeFilter")
    ? document.getElementById("productCodeFilter").value
    : ""
  const nameFilter = document.getElementById("productNameFilter")
    ? document.getElementById("productNameFilter").value
    : ""
  const departmentFilter = document.getElementById("productDepartmentFilter")
    ? document.getElementById("productDepartmentFilter").value
    : ""
  const categoryFilter = document.getElementById("productCategoryFilter")
    ? document.getElementById("productCategoryFilter").value
    : ""

  console.log("Filter values:", {
    code: codeFilter,
    name: nameFilter,
    department: departmentFilter,
    category: categoryFilter,
  })

  // Construir parámetros de consulta
  let queryParams = {}

  if (codeFilter) queryParams.ItemCode = encodeURIComponent(codeFilter)
  if (nameFilter) queryParams.Description = encodeURIComponent(nameFilter)
  if (departmentFilter) queryParams.Department = encodeURIComponent(departmentFilter)
  if (categoryFilter) queryParams.Category = encodeURIComponent(categoryFilter)

  const apiUrl = `api_proxy.php?endpoint=GetAllProducts${queryParams}`
  try {
    const data = await fetchData("GetAllProducts", queryParams)
    const products = logApiResponse("GetAllProducts", data)
    productsData = products.slice() // Store globally for filtering later
    if(codeFilter){
      products = products.filter(p => p.ProductCode.toLowerCase() === codeFilter.toLowerCase())
    }
    if(nameFilter){
      products = products.filter(p => p.Description.toLowerCase() === nameFilter.toLowerCase())
    }
    if (products && products.length > 0) {

      // Preparar datos para la tabla
        const tableData = products.map((product) => {
          // Log each product for debugging
          

          // Buscar el nombre del departamento
          const department = productDepartments.find((d) => d.DepartmentName === product.Department)
          
          const departmentName = department ? department.DepartmentName : product.Department

          // Buscar el nombre de la categoría
          const category = productCategories.find((c) => c.CategoryName === product.Category)
          
          const categoryName = category ? category.CategoryName : product.Category

          // Crear botones de acción
          //const editButton = `<button class="btn btn-sm btn-primary edit-product" data-id="${product.ProductCode}"><i class="fas fa-edit"></i></button>`
          // const deleteButton = `<button class="btn btn-sm btn-danger ms-1 delete-product" data-id="${product.ProductCode}"><i class="fas fa-trash"></i></button>`
          // const recibirInventario = `<button class="btn btn-sm btn-secondary ms-1 receive-inventory" data-id="${product.ProductCode}" data-name="${product.ProductName}" data-barcode="${product.BarCode}"><i class="fas fa-square-plus"></i></button>`
          // const tagButton = `<button class="btn btn-sm btn-secondary ms-1 tag-product" data-id="${product.ProductCode}" data-name="${product.ProductName}" data-barcode="${product.BarCode}"><i class="fas fa-tag"></i></button>`
          // const pre_ordenButton = `<button class="btn btn-sm btn-secondary ms-1 pre-orden" data-id="${product.ProductCode}" data-id="${product.ProductCode}" data-name="${product.ProductName}" data-barcode="${product.BarCode}">Pre-Orden</button>`
          return [
            product.ProductCode || "",
            product.ProductName || "",
            product.BarCode || "",
            formatCurrency(product.Price || 0),
            formatCurrency(product.Cost || 0),
            stockToHtml(
            safeToLocaleString(product.CurrentStock),companyName)+"<br>" +"Cargando...",
            departmentName, // Mostrar el nombre del departamento en lugar del ID
            categoryName // Mostrar el nombre de la categoría en lugar del ID
            //recibirInventario + tagButton + deleteButton, //+ editButton
            //pre_ordenButton
          ]
        })

        

        try {
          // Check if table element exists
          const tableElement = document.getElementById("productsMaintenanceTable")
          if (!tableElement) {
            console.error("Table element not found")
            showToast("Error", "No se encontró la tabla de productos", "error")
            return
          }

          

          // Check if jQuery is available
          if (typeof jQuery === "undefined") {
            console.error("jQuery is not loaded!")
            showToast("Error", "jQuery no está cargado. Verifique las dependencias.", "error")
            return
          }

          // Use jQuery safely
          const $ = jQuery

          // Check if DataTables is available
          if (!$.fn.DataTable) {
            console.error("DataTables is not loaded!")
            showToast("Error", "DataTables no está cargado. Verifique las dependencias.", "error")
            return
          }
          // Define columns with proper width and alignment
          const columns = [
            { title: "Código", data: 0, width: "10%" },
            { title: "Descripción", data: 1, width: "15%",className: "editable" },
            { title: "Código de Barras", data: 2, width: "10%",className: "editable" },
            { title: "Precio", data: 3, width: "8%",className: "editable" },
            { title: "Costo", data: 4, width: "8%", className: "editable" },
            { title: "Stock", data: 5, width: "20%",  },
            { title: "Departamento", data: 6, width: "12%" },
            { title: "Categoría", data: 7, width: "12%" },
            //{ title: "Acciones", data: 8, width: "10%", className: "text-center", orderable: false },
            //{ title: "Movimiento", data: 9, width: "8%", className: "text-center", orderable: false },
          ]

          // Inicializar o actualizar la tabla
          if ($.fn.DataTable.isDataTable("#productsMaintenanceTable")) {
            
            $("#productsMaintenanceTable").DataTable().clear().rows.add(tableData).draw()
          } else {
            
            productsMaintenanceTable = $("#productsMaintenanceTable").DataTable({
              data: tableData,
              columns: columns,
              language: {
                url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-AR.json",
              },
              responsive: true,
              autoWidth: false, // Disable auto width calculation
              columnDefs: [
                { responsivePriority: 1, targets: [0, 1] }, // These columns are most important
                { responsivePriority: 2, targets: [3, 7] }, // These columns are next important
              ],
              dom:
                '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                '<"row"<"col-sm-12"tr>>' +
                '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
              initComplete: function () {
                // Add some custom styling after table is initialized
                $(this).closest(".dataTables_wrapper").addClass("card-body p-0")
              },
            })
            setTimeout(() => {
              loadSecondStockParallel(products);
            }, 0);

            /* // Añadir eventos a los botones de acción - only add once
            $(document).off("click", ".edit-product").on("click", ".edit-product", function () {
              
              const productCode = $(this).data("id")
              
              editProduct(productCode)
            })
 */
           /*  $(document).off("click", ".delete-product").on("click", ".delete-product", function () {
              
              const productCode = $(this).data("id")
              
              deleteProduct(productCode)
            })
            $(document).off("click", ".receive-inventory").on("click", ".receive-inventory", function () {
              fetch('modalReceiveInventory.php')
              .then(response => response.text())
              .then(html => {
                document.getElementById('receiveInventoryContainer').innerHTML = html;
                const productBarCode = $(this).data("barcode");
              const productName = $(this).data("name");
              const productCode = $(this).data("id");
              document.getElementById('movementContainer').classList.add('d-none');
              document.getElementById('ProdMovementChart').classList.add('d-none');
              document.getElementById('unidadesProductoGroup').classList.add('d-none'); // oculta
              document.getElementById('codigoProducto').innerText = productCode;
              document.getElementById('nombreProducto').innerText = productName;
              document.getElementById('barcodeProducto').innerText = productBarCode;
              document.getElementById('recibirProductoLabel').innerText = `Recibir Producto`;
                var configModal = new bootstrap.Modal(document.getElementById('recibirProductoModal'));
              configModal.show();
              // Agregar evento al botón de confirmar
              document.getElementById('btnRecibirInventario').addEventListener('click', function() {
                const QuantityReceived = document.getElementById('cantidadProducto').value;
                const userId = $("#userIdSpan").text()
                receiveInventory(userId, productCode, QuantityReceived);
                configModal.hide();
              });
              });
            });
            $(document).off("click", ".tag-product").on("click", ".tag-product", function () {
              const productCode = $(this).data("id");
              productLabelPrint(productCode);
            });
            $(document).off("click", ".pre-orden").on("click", ".pre-orden", function () {
              fetch('modalReceiveInventory.php')
              .then(response => response.text())
              .then(html => {
                
                document.getElementById('receiveInventoryContainer').innerHTML = html;
                const productBarCode = $(this).data("barcode");
              const productName = $(this).data("name");
              const productCode = $(this).data("id");
              getUnitsByProduct(productCode).then(units => {
                if (!units || units.length === 0) {
                 const option = document.createElement('option');
                  option.value = 1;
                  option.text = 'Unidad- Each';
                  document.getElementById('unidadesProducto').appendChild(option);
                }
                units.forEach(unit => {
                  const option = document.createElement('option');
                  option.value = unit.UnitID;
                  option.text = unit.UnitDescription;
                  document.getElementById('unidadesProducto').appendChild(option);
                });
              });
              loadChart(productCode);
              document.getElementById('movementContainer').classList.remove('d-none');
              document.getElementById('ProdMovementChart').classList.remove('d-none');
              document.getElementById('unidadesProductoGroup').classList.remove('d-none'); // muestra
              document.getElementById('codigoProducto').innerText = productCode;
              document.getElementById('nombreProducto').innerText = productName;
              document.getElementById('barcodeProducto').innerText = productBarCode;
              document.getElementById('recibirProductoLabel').innerText = `Pre-Orden Change`;
                var configModal = new bootstrap.Modal(document.getElementById('recibirProductoModal'));
              configModal.show();
              // Agregar evento al botón de confirmar
              document.getElementById('btnRecibirInventario').addEventListener('click', function() {
                const QuantityReceived = document.getElementById('cantidadProducto').value;
                const UnitID = document.getElementById('unidadesProducto').value;
                const userId = $("#userIdSpan").text()
                productPreOrderChange(userId, productCode, QuantityReceived, UnitID);
                configModal.hide();
              });
              });
            }); */

          }
          //--------------
       // 🔹 Agregar modal al hacer un solo clic en una celda
$('#productsMaintenanceTable tbody').on('click', 'td.editable', function (event) {
  let cell = productsMaintenanceTable.cell(this);
  let oldValue = cell.data();
  if(cell.index().column===3 || cell.index().column===4){
    oldValue=oldValue.replace("$", "")
  }
  var row = cell.index().row;
  var valueCol0 = productsMaintenanceTable.cell(row, 0).data()
  // Llamamos al modal dinámico
  openDialog2("string", event, { 
    id: cell.index().row,       // fila como referencia
    column: cell.index().column, // columna
    oldValue: oldValue, cell: cell, itemCode: valueCol0 // nodo TD
  });

  // Opcional: pasar el valor actual al input del modal
  setTimeout(() => {
    const input = document.querySelector("#datoInput");
    if (input) input.value = oldValue;
  }, 0);
});
          
        } catch (error) {
          console.error("Error initializing DataTable:", error)
          showToast("Error", "Error al inicializar la tabla de productos: " + error.message, "error")
        }
      } else {
        console.error("No products found in data:", data)
        showToast("Información", "No se encontraron productos con los filtros seleccionados", "info")
      }
    }
    catch(error){
      
      console.error("Error cargando productos:", error)
      showToast("Error", "No se pudieron cargar los productos: " + error.message, "error")
    }
}
async function loadChart(itemCode) {
    try {
        const data = await fetchData('ProdMovementChart', { ItemCode: itemCode });
        
        if(!data || data.length === 0){
            // Manejo de error o no data
            document.getElementById('movementContainer').classList.add('d-none');
            document.getElementById('ProdMovementChart').classList.add('d-none');
            document.getElementById('ProdMovementChartMessage').classList.remove('d-none');
            document.getElementById('ProdMovementChartMessage').innerText = 'No hay datos de movimiento para este producto.';
            calculateAndDisplayAnnualSummary([]);
            monthlyData = [];
            return;
        }
        document.getElementById('movementContainer').classList.remove('d-none');

        // 1. **Guardar los datos completos en la variable global**
        monthlyData = data; 
        
        // --- Lógica del Gráfico (manteniendo lo que ya tenías) ---
        
        const labels = data.map(d => d.MonthName); 
        const sales = data.map(d => d.NetSalesQuantity); 
        const receipts = data.map(d => d.ReceiptsQuantity);

        const ctx = document.getElementById("ProdMovementChart").getContext("2d");

        // Destruir la instancia anterior del gráfico si existe
        if (productChart) {
            productChart.destroy();
        }
        productChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Ventas",
                        data: sales,
                        backgroundColor: "rgba(0, 123, 255, 0.8)" 
                    },
                    {
                        label: "Recibos",
                        data: receipts,
                        backgroundColor: "rgba(220, 53, 69, 0.8)" 
                    }
                ]
            },
            options: {
                 animation: {
    onComplete: (animation) => {
      const chart = animation.chart;   // ✅ aquí sí tienes el chart
      const ctx = chart.ctx;

      chart.data.datasets.forEach((dataset, i) => {
        const meta = chart.getDatasetMeta(i);
        meta.data.forEach((bar, index) => {
          const value = dataset.data[index];
          ctx.fillStyle = "black";
          ctx.textAlign = "center";
          ctx.textBaseline = "bottom";
          // 👇 aquí ajustas la fuente con negrilla
          ctx.font = "bold 12px Arial";  
          ctx.fillText(value, bar.x, bar.y - 5);
        });
      });
    }
  }
            },// <-- activar el plugin
        });

        // 2. **Generar los botones de meses y añadir listeners**
        generateMonthButtons(data);
        
        // 3. **Mostrar el resumen del primer mes (o el que quieras por defecto)**
        // Asume que la data ya está ordenada por fecha.
        if (data.length > 0) {
            updateMonthlySummary(data[0].MonthName); 
        }
        // Calcular y mostrar el resumen anual y orden sugerida 
        calculateAndDisplayAnnualSummary(data);
    } catch (error) {
        console.error("Error cargando gráfico:", error);
    }
}
function calculateAndDisplayAnnualSummary(data) {
    if (!data || data.length === 0) {
        console.warn("No hay datos para calcular el resumen anual.");
        document.getElementById('annual-total-sales-units').textContent = '0';
        document.getElementById('annual-gross-sales-value').textContent = '$0.00';
        document.getElementById('annual-total-costs').textContent = '$0.00';
        document.getElementById('annual-total-profit').textContent = '$0.00';
        document.getElementById('demand-weekly').textContent = '0.00';
        document.getElementById('demand-monthly').textContent = '0.00';
        document.getElementById('suggested-order-quantity').textContent = '0000';
    document.getElementById('suggested-order-excess-message').textContent = '';
    document.getElementById('current-inventory').textContent = '0';
        return;
    }

    // --- Resumen Anual ---
    let totalSalesUnits = 0;
    let totalGrossSales = 0; // Valor bruto de ventas
    let totalCosts = 0;
    let totalProfit = 0;

    data.forEach(month => {
        totalSalesUnits += month.NetSalesQuantity || 0;
        totalGrossSales += month.GrossSalesValue || 0; // Asumiendo que 'SalesValue' es el valor bruto de ventas
        totalCosts += month.CurrentCost || 0; // Asumiendo que 'CostValue' es el costo
        // Si ProfitValue no viene directamente, lo calculamos
        totalProfit += month.ProfitValue || (month.GrossSalesValue - month.CurrentCost) || 0;
    });

    // Actualizar elementos HTML del Resumen Anual
    document.getElementById('annual-total-sales-units').textContent = totalSalesUnits.toString();
    document.getElementById('annual-gross-sales-value').textContent = `${formatCurrency(totalGrossSales)}`;
    document.getElementById('annual-total-costs').textContent = `${formatCurrency(totalCosts)}`;
    document.getElementById('annual-total-profit').textContent = `${formatCurrency(totalProfit)}`;


    // --- Demanda (basada en los últimos 3 meses) ---
    const lastThreeMonthsSales = data
        .slice(-3) // Obtener los últimos 3 elementos (meses)
        .reduce((sum, month) => sum + (month.NetSalesQuantity || 0), 0);

    const avgMonthlySalesLast3Months = lastThreeMonthsSales / 3;
    const avgWeeklySalesLast3Months = avgMonthlySalesLast3Months / 4; //4 semanas por mes

    document.getElementById('demand-weekly').textContent = avgWeeklySalesLast3Months.toFixed(2);
    document.getElementById('demand-monthly').textContent = avgMonthlySalesLast3Months.toFixed(2);

    // --- Orden Sugerida ---
    const monthsOfCoverage = data[0].CurrentStock / avgMonthlySalesLast3Months; // "producto en exceso para cubrir 6 meses"
        // --- Orden Sugerida ---
    fetch('api_proxy.php?endpoint=inventoryMonthsOfCover')
    .then(response => response.json())
    .then(inventoryMonthsOfCover => {
      const inventoryMonthsOfCoverData = inventoryMonthsOfCover;
      const suggestedOrderQuantity = Math.max(0, (avgMonthlySalesLast3Months * inventoryMonthsOfCoverData['inventoryMonthsOfCover']));
      // Si la orden sugerida es 0 o negativa, significa que tenemos exceso de inventario.
    // El texto "producto en exceso para cubrir 6 meses" debería aparecer si suggestedOrderQuantity <= 0
    let suggestedOrderText = suggestedOrderQuantity.toFixed(0) > 0 
        ? suggestedOrderQuantity.toFixed(0) // Redondear a entero
        : "0000"; // Como en tu imagen, "0000" para exceso
      document.getElementById('suggested-order-quantity').textContent = suggestedOrderText;
    })
    .catch(error => {
      console.error('Error fetching inventoryMonthsOfCover:', error);
    });
    

    let excessMessage = '';
    if (data[0].CurrentStock > avgMonthlySalesLast3Months) {
        // Calcular cuántos meses cubre el inventario actual si no se necesita ordenar
        excessMessage = `*producto en exceso para cubrir ${monthsOfCoverage.toFixed(0)} meses `;
    }
   
    document.getElementById('suggested-order-excess-message').textContent = excessMessage;
    console.log("Current Stock:", data[0].CurrentStock);
    document.getElementById('current-inventory').textContent = String(data[0].CurrentStock); // Formato 0075
}
// Función para generar los botones de meses
function generateMonthButtons(data) {
    const buttonContainer = document.getElementById('monthButtonsContainer'); // Debes añadir este ID a tu contenedor
    buttonContainer.innerHTML = ''; // Limpiar botones anteriores

    data.forEach((monthData, index) => {
        const button = document.createElement('button');
        button.className = 'btn btn-secondary rounded-pill month-button'; // Clases para estilizar el botón
        button.textContent = monthData.MonthName;
        button.setAttribute('data-month', monthData.MonthName);
        
        // Si es el primer mes, hacerlo el botón activo/seleccionado
        if (index === 0) {
             button.classList.remove('btn-secondary');
             button.classList.add('btn-primary'); // O la clase de color que uses para 'activo', como se ve en la imagen
        }

        button.addEventListener('click', function() {
            // Desactivar el botón activo actual
            document.querySelectorAll('.month-button').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            });

            // Activar el botón clickeado
            this.classList.remove('btn-secondary');
            this.classList.add('btn-primary');

            // Actualizar la tabla
            updateMonthlySummary(monthData.MonthName);
        });

        buttonContainer.appendChild(button);
    });
}

// Función para actualizar la tabla de resumen
function updateMonthlySummary(monthName) {
    // 1. Buscar los datos del mes seleccionado
    console.log("Updating summary for month:", monthName);
    console.log("Monthly data available:", monthlyData);
    const dataForMonth = monthlyData.find(d => d.MonthName === monthName);
    console.log("Data for selected month:", dataForMonth);
    if (!dataForMonth) {
        console.error(`Datos no encontrados para el mes: ${monthName}`);
        return;
    }


    // 3. Actualizar los elementos de la tabla
    // **NOTA:** Necesitas asignar IDs a las celdas de tu tabla, por ejemplo: 
    // <span id="summary-ventas"></span>, <span id="summary-costo"></span>, etc.

    document.getElementById('summary-ventas').textContent = formatCurrency(dataForMonth.GrossSalesValue); 
    document.getElementById('summary-costo').textContent = formatCurrency(dataForMonth.CurrentCost);
    // Asumo que tu JSON tiene la propiedad para el Profit
    document.getElementById('summary-profit').textContent = formatCurrency(dataForMonth.ProfitValue || (dataForMonth.GrossSalesValue - dataForMonth.CurrentCost)); 
    document.getElementById('summary-recibos').textContent = dataForMonth.ReceiptsQuantity;
    document.getElementById('summary-vendidos').textContent = dataForMonth.NetSalesQuantity;

}

// Function to load product departments
async function loadProductDepartments(callback) {

  try {
    const data = await fetchData('InventoryDepartments', { Short: 'yes' });
    const departments = logApiResponse("InventoryDepartments", data);


      if (departments && departments.length > 0) {
        productDepartments = departments
        

        // Llenar el select de departamentos para filtros
        const filterSelect = document.getElementById("productDepartmentFilter")
        if (filterSelect) {
          filterSelect.innerHTML = '<option value="">Todos los departamentos</option>'

          departments.forEach((department) => {
            
            const filterOption = document.createElement("option")
            filterOption.value = department.DepartmentID
            filterOption.textContent = department.DepartmentName
            filterSelect.appendChild(filterOption)
          })
        } else {
          console.warn("Department filter select not found")
        }

        // Llenar el select de departamentos para el formulario
        const formSelect = document.getElementById("productDepartment")
        if (formSelect) {
          formSelect.innerHTML = '<option value="">Seleccionar departamento</option>'

          departments.forEach((department) => {
            const formOption = document.createElement("option")
            formOption.value = department.DepartmentID
            formOption.textContent = department.DepartmentName
            formSelect.appendChild(formOption)
          })
        }

        // Call the callback function if provided
        if (typeof callback === "function") {
          callback()
        }
      } else {
        console.error("No departments found in data:", data)
        showToast("Error", "No se encontraron departamentos", "error")

        // Call the callback function even on error to continue the flow
        if (typeof callback === "function") {
          callback()
        }
      }
    }
    catch(error) {
      clearTimeout(timeoutId) // Clear the timeout
      console.error("Error cargando departamentos de productos:", error)
      showToast("Error", "No se pudieron cargar los departamentos: " + error.message, "error")

      // Call the callback function even on error to continue the flow
      if (typeof callback === "function") {
        callback()
      }
    }
}

// Function to load product categories
async function loadProductCategories(callback) {
  try {
    const data = await fetchData('InventoryCategories');
    const categories = logApiResponse("InventoryCategories", data);

      if (categories && categories.length > 0) {
        productCategories = categories

        // Llenar el select de categorías para filtros
        const filterSelect = document.getElementById("productCategoryFilter")
        if (filterSelect) {
          filterSelect.innerHTML = '<option value="">Todas las categorías</option>'

          categories.forEach((category) => {
            
            const filterOption = document.createElement("option")
            filterOption.value = category.CategoryID
            filterOption.textContent = category.CategoryName
            filterSelect.appendChild(filterOption)
          })
        } else {
          console.warn("Category filter select not found")
        }

        // Llenar el select de categorías para el formulario
        const formSelect = document.getElementById("productCategory")
        if (formSelect) {
          formSelect.innerHTML = '<option value="">Seleccionar categoría</option>'

          categories.forEach((category) => {
            const formOption = document.createElement("option")
            formOption.value = category.CategoryID
            formOption.textContent = category.CategoryName
            formSelect.appendChild(formOption)
          })
        }

        // Call the callback function if provided
        if (typeof callback === "function") {
          callback()
        }
      } else {
        console.error("No categories found in data:", data)
        showToast("Error", "No se encontraron categorías", "error")

        // Call the callback function even on error to continue the flow
        if (typeof callback === "function") {
          callback()
        }
      }
    }
    catch(error) {
      clearTimeout(timeoutId) // Clear the timeout
      console.error("Error cargando categorías de productos:", error)
      showToast("Error", "No se pudieron cargar las categorías: " + error.message, "error")

      // Call the callback function even on error to continue the flow
      if (typeof callback === "function") {
        callback()
      }
    }
}

// Function to edit a product
function editProduct(productCode) {
  

  // Validate product code
  if (!productCode) {
    console.error("No product code provided")
    showToast("Error", "No se proporcionó un código de producto válido", "error")
    return
  }

  // Show loading indicator
  toggleLoading(true)

  // First, try to find the product in the existing table data
  
  let foundProduct = null

  // Check if jQuery and DataTables are available
  if (typeof jQuery !== "undefined" && jQuery.fn.DataTable) {
    const table = jQuery("#productsMaintenanceTable").DataTable()
    if (table) {
      // Get all data from the table
      const tableData = table.data().toArray()
      

      // Find the row with matching product code
      const productRow = tableData.find((row) => row[0] === productCode)
      if (productRow) {
        

        // Create a product object from the row data
        foundProduct = {
          ProductCode: productRow[0],
          ProductName: productRow[1],
          BarCode: productRow[2],
          Price: productRow[3].replace(/[^\d.,]/g, ""), // Remove currency symbols
          Cost: productRow[4].replace(/[^\d.,]/g, ""),
          CurrentStock: productRow[5],
          Department: productRow[6],
          Category: productRow[7],
          Active: productRow[8].includes("Activo") ? "1" : "0",
        }

        
        populateProductForm(foundProduct)
        return
      } else {
        console.log("Product not found in table data, will try API...")
      }
    }
  }

  // If we couldn't find the product in the table, try the API
  // Try different API endpoint formats
  const apiEndpoints = [
    `api_proxy.php?endpoint=GetProduct&ItemCode=${encodeURIComponent(productCode)}`,
    `api_proxy.php?endpoint=GetProductDetails&ItemCode=${encodeURIComponent(productCode)}`,
    `api_proxy.php?endpoint=GetAllProducts&ItemCode=${encodeURIComponent(productCode)}`,
  ]

  

  // Try each endpoint in sequence
  tryNextEndpoint(0)

  function tryNextEndpoint(index) {
    if (index >= apiEndpoints.length) {
      console.error("All API endpoints failed")
      
      showToast("Error", "No se pudo cargar los detalles del producto después de intentar múltiples endpoints", "error")
      return
    }

    const apiUrl = apiEndpoints[index]
    

    fetch(apiUrl)
      .then((response) => {
        
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`)
        }
        return response.json()
      })
      .then((data) => {
        

        // If data is false or empty, try the next endpoint
        if (!data || (typeof data === "object" && Object.keys(data).length === 0)) {
          
          tryNextEndpoint(index + 1)
          return
        }

        // Try to extract the product from different data structures
        const product = extractProductFromResponse(data, productCode)

        if (product) {
          
          populateProductForm(product)
        } else {
          console.log(`Couldn't extract product from endpoint ${index + 1}, trying next endpoint...`)
          tryNextEndpoint(index + 1)
        }
      })
      .catch((error) => {
        console.error(`Error with endpoint ${index + 1}:`, error)
        console.log("Trying next endpoint...")
        tryNextEndpoint(index + 1)
      })
  }

  // Helper function to extract product from different response structures
  function extractProductFromResponse(data, productCode) {
    // Case 1: data.RESULT is an array of products
    if (data.RESULT && Array.isArray(data.RESULT)) {
      // If it's an array of arrays, take the first array
      if (Array.isArray(data.RESULT[0])) {
        const products = data.RESULT[0]
        return products.find((p) => p.ProductCode === productCode || p.ItemCode === productCode)
      }
      // If RESULT is just an array of objects, search in it
      return data.RESULT.find((p) => p.ProductCode === productCode || p.ItemCode === productCode)
    }

    // Case 2: data is an array of products
    if (Array.isArray(data)) {
      return data.find((p) => p.ProductCode === productCode || p.ItemCode === productCode)
    }

    // Case 3: data is the product object itself
    if (typeof data === "object" && data !== null) {
      if (data.ProductCode === productCode || data.ItemCode === productCode) {
        return data
      }
    }

    return null
  }

  // Helper function to populate the form with product data
  function populateProductForm(product) {
    // Reset the form
    document.getElementById("productForm").reset()

    

    // Map the API field names to the form field IDs with fallbacks for different field names
    const fieldMappings = {
      // Try different possible API field names
      ProductCode: "productCode",
      ItemCode: "productCode", // Alternative field name

      ProductName: "productName",
      Description: "productName", // Alternative field name

      BarCode: "productBarCode",
      Barcode: "productBarCode", // Alternative field name

      Price: "productPrice",
      UnitPrice: "productPrice", // Alternative field name

      Cost: "productCost",
      UnitCost: "productCost", // Alternative field name

      CurrentStock: "productStock",
      Stock: "productStock", // Alternative field name
      Quantity: "productStock", // Alternative field name

      Department: "productDepartment",
      DepartmentID: "productDepartment", // Alternative field name

      Category: "productCategory",
      CategoryID: "productCategory", // Alternative field name

      Active: "productActive",
      IsActive: "productActive", // Alternative field name
    }

    // Log all available fields for debugging
    

    // Set form values based on mappings
    for (const [apiField, formField] of Object.entries(fieldMappings)) {
      const formElement = document.getElementById(formField)
      if (formElement && product[apiField] !== undefined) {
        if (formField === "productActive") {
          // Handle checkbox
          const activeValue = product[apiField]
          formElement.checked =
            activeValue === "1" ||
            activeValue === 1 ||
            activeValue === true ||
            activeValue === "true" ||
            activeValue === "S"
          
        } else {
          // Handle regular inputs
          formElement.value = product[apiField] || ""
          console.log(`Set ${formField} to "${formElement.value}"`)
        }
      }
    }

    // Show form
    document.getElementById("productFormTitle").textContent = "Editar Producto"
    // Declare jQuery if it's not already declared
    if (typeof jQuery === "undefined") {
      console.error("jQuery is not loaded!")
      showToast("Error", "jQuery no está cargado. Verifique las dependencias.", "error")
      return
    }
    jQuery("#productModal").modal("show")
  }
}

// Function to delete a product
function deleteProduct(productCode) {
  Swal.fire({
      title: "Confirmar Eliminación",
      text: "¿Está seguro que desea eliminar este producto? Esta acción no se puede deshacer.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Aceptar",
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
        title: "Funcionalidad no Implementada",
        text: "La funcionalidad de eliminación de productos no está implementada en este momento.",
        icon: "info",
        showCancelButton: false,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Aceptar",
      });
      }
    });

  
    
    //TODO: COMENTADO PORQUE EL API NO TIENE DELETEPRODUCT, CUANDO EL API LO TENGA, DESCOMENTAR
    /* fetch(`api_proxy.php?endpoint=DeleteProduct&ItemCode=${encodeURIComponent(productCode)}`, {
      method: "POST",
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`)
        }
        return response.json()
      })
      .then((result) => {
        

        if (result.success) {
          showToast("Éxito", "Producto eliminado correctamente", "success")
          loadProducts() // Reload products table
        } else {
          showToast("Error", result.message || "No se pudo eliminar el producto", "error")
        }
      })
      .catch((error) => {
        console.error("Error deleting product:", error)
        showToast("Error", "No se pudo eliminar el producto: " + error.message, "error")
      })
      .finally(() => {
        toggleLoading(false)
      }) */
}
function receiveInventory(userId, productCode, QuantityReceived) {
  toggleLoading(true)
  let params={ItemCode:productCode,QuantityReceived:QuantityReceived,UserID:userId};
  fetchData(`RecReceiveInventory`, params)
      .then(data => {
            if ( data.success) {
              Swal.fire({
                title: "Éxito",
                text: "Producto recibido correctamente",
                icon: "success",
                showCancelButton: false,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Aceptar",
                timer: 3000,                // 3 segundos
                timerProgressBar: true
              })
              loadProducts() // Reload products table
            } else {
              showToast("Error", data.message || "No se pudo RECIBIR el producto", "error")
            }
          })
          .catch((error) => {
        console.error("Error receiving product:", error)
        showToast("Error", "No se pudo RECIBIR el producto: " + error.message, "error")
      })
      .finally(() => {
        toggleLoading(false)
      })
}

function productPreOrderChange(userId, productCode, PreOrdeQty, UnitID) {
  toggleLoading(true);
  let params={ItemCode:productCode,PreOrdeQty:PreOrdeQty,UserID:userId,UnitID:UnitID};
  fetchData(`ProdPreOrdChange`, params)
          .then(data => {
            if ( data.success) {
              Swal.fire({
                title: "Éxito",
                text: "Pre - Orden cambiado correctamente",
                icon: "success",
                showCancelButton: false,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Aceptar",
                timer: 3000,                // 3 segundos
                timerProgressBar: true
              })
              loadProducts() // Reload products table
            } else {
              showToast("Error", data.message || "No se pudo cambiar la Pre - Orden", "error")
            }
          })
          .catch((error) => {
        console.error("Error changing pre-order:", error)
        showToast("Error", "No se pudo cambiar la Pre - Orden: " + error.message, "error")
      })
      .finally(() => {
        toggleLoading(false)
      })
}
function productLabelPrint(productCode) {
  toggleLoading(true);
  fetchData(`ProdLabelPrint`, {ItemCode:productCode})
      .then(data => {
            if ( data.success) {
              Swal.fire({
                title: "Éxito",
                text: "Etiquetas enviadas a imprimir correctamente",
                icon: "success",
                showCancelButton: false,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Aceptar",
                timer: 3000,                // 3 segundos
                timerProgressBar: true
              })
              loadProducts() // Reload products table
            } else {
              showToast("Error", data.message || "No se pudo imprimir las etiquetas", "error")
            }
          }).catch((error) => {
        console.error("Error printing labels:", error)
        showToast("Error", "No se pudo imprimir las etiquetas: " + error.message, "error")
      })
      .finally(() => {
        toggleLoading(false)
      })
}


function getUnitsByProduct(productCode) {
  toggleLoading(true);
  return fetchData(`ProdUnits`, {ItemCode:productCode})
  .then((data) => {
    if (Array.isArray(data)) {
      return data; // ✅ Caso en que la API devuelve un array
    } else if (typeof data === "object" && data !== null) {
      return []; // ✅ Caso en que la API devuelve un objeto (no es un array)
    } else {
      console.error("Formato inesperado:", data);
    }
    return []; // Retorna un array vacío en caso de formato inesperado
  })
  .catch((error) => {
    return []; // Retorna un array vacío en caso de error
  })
  .finally(() => {
    toggleLoading(false);
  });

}

// Function to save a product (create or update)
function saveProduct(event) {
  event.preventDefault()
  

  const productCode = document.getElementById("productCode").value
  const isNewProduct = true
  

  // Collect form data
  const formData = new FormData(document.getElementById("productForm"))
  const productData = {}
  for (const [key, value] of formData.entries()) {
    productData[key] = value
  }

  // Handle checkbox for active status
  if (document.getElementById("productActive")) {
    productData.Active = document.getElementById("productActive").checked ? "1" : "0"
  }
  // Determine endpoint based on whether it's a new product or an update
  const endpoint = isNewProduct ? "ProdCreateNewItem" : "UpdateProduct"
  let paramsObject = {
    ItemCode: productData.productCode,
    BarCode: productData.productBarcode,
    Description: encodeURIComponent(productData.productDescription),
    Price: productData.productPrice,
    Cost: productData.productCost,
    DepartmentNum: productData.productDepartment,
    CategoryNum: productData.productCategory,
    Supplier: encodeURIComponent(productData.productSupplier),
    Tax1: productData.productTax1,
    Tax2: productData.productTax2
  }
  toggleLoading(true)

  fetchData('ProdCreateNewItem', paramsObject)
  .then((result) => {
      if (result.success) {
        Swal.fire({
          title: "Éxito",
          text: `Producto ${isNewProduct ? "creado" : "actualizado"} correctamente`,
          icon: "success",
          showCancelButton: false,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Aceptar",
          timer: 3000,                // 3 segundos
          timerProgressBar: true
        })
        // Declare jQuery if it's not already declared
        if (typeof jQuery === "undefined") {
          console.error("jQuery is not loaded!")
          Swal.fire({
            title: "Error",
            text: "jQuery no está cargado. Verifique las dependencias.",
            icon: "error",
            showCancelButton: false,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Aceptar",
            timer: 3000,                // 3 segundos
            timerProgressBar: true
          })
          return
        }
        jQuery("#productModal").modal("hide")
        loadProducts() // Reload products table
      } else {
        Swal.fire({
          title: "Error",
          text: result.message || `No se pudo ${isNewProduct ? "crear" : "actualizar"} el producto`,
          icon: "error",
          showCancelButton: false,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Aceptar",
          timer: 3000,                // 3 segundos
          timerProgressBar: true
        })
      }
    })
    .catch((error) => {
      console.error(`Error ${isNewProduct ? "creating" : "updating"} product:`, error)
      Swal.fire({
        title: "Error",
        text: `No se pudo ${isNewProduct ? "crear" : "actualizar"} el producto: ${error.message}`,
        icon: "error",
        showCancelButton: false,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Aceptar",
        timer: 3000,                // 3 segundos
        timerProgressBar: true
      })
    })
    .finally(() => {
      toggleLoading(false)
    })
}

// Function to initialize the product maintenance section
async function initProductMaintenance() {
  
  // Load initial data - this will load departments, categories, and then products in sequence
  await loadProducts()

  // Set up event listeners
  const filterForm = document.getElementById("filterProductsForm")
  if (filterForm) {
    filterForm.addEventListener("submit", (event) => {
      event.preventDefault()
      loadProducts()
    })
  } else {
    console.warn("Filter form not found")
    // Add direct event listener to the search button as fallback
    const searchBtn = document.getElementById("applyProductFiltersMaintenance")
    if (searchBtn) {
      searchBtn.addEventListener("click", async () => {
        toggleLoading(true)
        await loadProductsWithFilters()
        toggleLoading(false)
      })
    }
  }

  const resetFiltersBtn = document.getElementById("resetProductFilters")
  if (resetFiltersBtn) {
    resetFiltersBtn.addEventListener("click", () => {
      if (document.getElementById("filterProductsForm")) {
        document.getElementById("filterProductsForm").reset()
      } else {
        // Reset individual filter fields if the form doesn't exist
      if (document.getElementById("productCodeFilter")) document.getElementById("productCodeFilter").value = ""
      if (document.getElementById("productNameFilter")) document.getElementById("productNameFilter").value = ""
      if (document.getElementById("productDepartmentFilter")) document.getElementById("productDepartmentFilter").value = ""
      if (document.getElementById("productCategoryFilter")) document.getElementById("productCategoryFilter").value = ""
      
      loadProducts()
      }
    })
  } else {
    console.warn("Reset filters button not found")
  }

  const addProductBtn = document.getElementById("addProductBtn")
  if (addProductBtn) {
    addProductBtn.addEventListener("click", () => {
     
    
      document.getElementById("productForm").reset()
      document.getElementById("productModalLabel").textContent = "Añadir Producto"
      
      // Asegurarse de que el código de producto esté vacío para nuevos productos
      if (document.getElementById("productCode")) {
        document.getElementById("productCode").value = ""
        document.getElementById("productCode").removeAttribute("readonly") // Permitir editar código para nuevos productos
      }
      
      // Declare jQuery if it's not already declared
      if (typeof jQuery === "undefined") {
        console.error("jQuery is not loaded!")
        showToast("Error", "jQuery no está cargado. Verifique las dependencias.", "error")
        return
      }
      jQuery("#productModal").modal("show")
    })
  } else {
    console.warn("Add product button not found")
  }

  const add_Product_Btn = document.getElementById("add-product-btn")
  if (add_Product_Btn) {
    add_Product_Btn.addEventListener("click", () => {
      document.getElementById("productForm").reset()
      document.getElementById("productModalLabel").textContent = "Añadir Producto"
      
      // Asegurarse de que el código de producto esté vacío para nuevos productos
      if (document.getElementById("productCode")) {
        document.getElementById("productCode").value = ""
        document.getElementById("productCode").removeAttribute("readonly") // Permitir editar código para nuevos productos
      }
      
      // Declare jQuery if it's not already declared
      if (typeof jQuery === "undefined") {
        console.error("jQuery is not loaded!")
        showToast("Error", "jQuery no está cargado. Verifique las dependencias.", "error")
        return
      }
      jQuery("#productModal").modal("show")
    
    })
  } else {
    console.warn("Add product button not found")
  }
  
  
  // Añadir evento al botón de guardar dentro del modal
  const saveProductBtn = document.getElementById("saveProductBtn")
  if (saveProductBtn) {
    saveProductBtn.addEventListener("click", function() {
      const productForm = document.getElementById("productForm")
      if (productForm) {
        // Crear un evento para disparar el submit del formulario
        const event = new Event("submit", {
          bubbles: true,
          cancelable: true
        })
        productForm.dispatchEvent(event)
      }
    })
  }
}

// Función para probar la conectividad con la API
function testApiConnectivity() {
  

  // Test endpoints
  const endpoints = ["InventoryDepartments&Short=yes", "InventoryCategories&Short=yes", "GetAllProducts"]

  endpoints.forEach((endpoint) => {
    

    fetch(`api_proxy.php?endpoint=${endpoint}`)
      .then((response) => {
        
        return response.text()
      })
      .then((text) => {
         
        try {
          const json = JSON.parse(text)
          
        } catch (e) {
          console.error(`Endpoint ${endpoint} is not returning valid JSON:`, e)
        }
      })
      .catch((error) => {
        console.error(`Endpoint ${endpoint} error:`, error)
      })
  
  }) // <-- Close forEach
}

// Verificar los campos del formulario de producto
function validateProductForm() {
  const requiredFields = [
    { id: "productCode", name: "Código del producto" },
    { id: "productDescription", name: "Descripción del producto" },
    { id: "productPrice", name: "Precio" },
    { id: "productCost", name: "Costo" },
    { id: "productDepartment", name: "Departamento" },
    { id: "productCategory", name: "Categoría" }
  ]
  
  for (const field of requiredFields) {
    const element = document.getElementById(field.id)
    if (!element || !element.value.trim()) {
      showToast("Error de validación", `El campo ${field.name} es obligatorio`, "error")
      if (element) element.focus()
      return false
    }
  }
  
  // Validar que el precio y costo sean números válidos
  const price = parseFloat(document.getElementById("productPrice").value)
  const cost = parseFloat(document.getElementById("productCost").value)
  
  if (isNaN(price) || price < 0) {
    showToast("Error de validación", "El precio debe ser un número positivo", "error")
    document.getElementById("productPrice").focus()
    return false
  }
  
  if (isNaN(cost) || cost < 0) {
    showToast("Error de validación", "El costo debe ser un número positivo", "error")
    document.getElementById("productCost").focus()
    return false
  }
  
  return true
}

// Manejar cambio de departamento para filtrar categorías relacionadas
function handleDepartmentChange() {
  const departmentSelect = document.getElementById("productDepartment")
  const categorySelect = document.getElementById("productCategory")
  
  if (departmentSelect && categorySelect) {
    departmentSelect.addEventListener("change", function() {
      const departmentId = this.value
      const departmentName = this.options[this.selectedIndex].text
      // Limpiar las opciones actuales
      categorySelect.innerHTML = '<option value="">Seleccionar categoría</option>'
      
      if (!departmentId) return
      // Filtrar las categorías del departamento seleccionado
      const departmentCategories = productCategories.filter(
        category => category.Department === departmentName
      )
      
      // Agregar las categorías filtradas
      departmentCategories.forEach(category => {
        const option = document.createElement("option")
        option.value = category.CategoryID
        option.textContent = category.CategoryName
        categorySelect.appendChild(option)
      })
    })
  }
}

// Mejorar manejo de errores del servidor
function handleServerResponse(response, context) {
  if (response.success) {
    showToast("Éxito", `Operación de ${context} completada correctamente`, "success")
    return true
  } else {
    // Manejar diferentes tipos de errores del servidor
    if (response.errorCode) {
      switch (response.errorCode) {
        case "DUPLICATE_KEY":
          showToast("Error", `Ya existe un producto con este código`, "error")
          break
        case "NOT_FOUND":
          showToast("Error", `El producto no existe o fue eliminado`, "error")
          break
        case "INVALID_INPUT":
          showToast("Error", `Datos de entrada inválidos: ${response.message || "Verifique los campos"}`, "error")
          break
        case "PERMISSION_DENIED":
          showToast("Error", `No tiene permisos para realizar esta operación`, "error")
          break
        default:
          showToast("Error", response.message || `Error desconocido en la operación de ${context}`, "error")
      }
    } else {
      showToast("Error", response.message || `Error desconocido en la operación de ${context}`, "error")
    }
    return false
  }
}

// Función para subir imagen de producto
function uploadProductImage(productCode) {
  const fileInput = document.getElementById("productImage")
  if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
    console.log("No file selected, skipping image upload")
    return Promise.resolve(null)
  }
  
  const file = fileInput.files[0]
  const formData = new FormData()
  formData.append("image", file)
  formData.append("productCode", productCode)
  
  
  
  return fetch("api_proxy.php?endpoint=UploadProductImage", {
    method: "POST",
    body: formData
  })
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`)
      return response.json()
    })
    .then(result => {
      
      if (result.success) {
        showToast("Éxito", "Imagen subida correctamente", "success")
        return result.imageUrl
      } else {
        showToast("Advertencia", "No se pudo subir la imagen: " + (result.message || "Error desconocido"), "warning")
        return null
      }
    })
    .catch(error => {
      console.error("Error uploading image:", error)
      showToast("Error", "Error al subir la imagen: " + error.message, "error")
      return null
    })
}

// Función para mostrar o previsualizar la imagen del producto
function loadProductImage(productCode) {
  const imageContainer = document.getElementById("productImagePreview")
  if (!imageContainer) return
  
  // Mostrar indicador de carga
  imageContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando imagen...</div>'
  
  fetch(`api_proxy.php?endpoint=GetProductImage&ItemCode=${encodeURIComponent(productCode)}`)
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`)
      return response.json()
    })
    .then(result => {
      if (result.success && result.imageUrl) {
        imageContainer.innerHTML = `
          <div class="text-center">
            <img src="${result.imageUrl}" alt="Imagen del producto" class="img-fluid rounded product-image-preview" />
            <button class="btn btn-sm btn-danger mt-2" id="removeProductImage">
              <i class="fas fa-trash"></i> Eliminar imagen
            </button>
          </div>
        `
        
        // Añadir evento para eliminar imagen
        document.getElementById("removeProductImage").addEventListener("click", function() {
          if (confirm("¿Está seguro que desea eliminar la imagen?")) {
            deleteProductImage(productCode)
          }
        })
      } else {
        imageContainer.innerHTML = '<div class="text-center text-muted"><i class="fas fa-image"></i> Sin imagen</div>'
      }
    })
    .catch(error => {
      console.error("Error loading product image:", error)
      imageContainer.innerHTML = '<div class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> Error al cargar la imagen</div>'
    })
}

// Función para eliminar la imagen del producto
function deleteProductImage(productCode) {
  fetch(`api_proxy.php?endpoint=DeleteProductImage&ItemCode=${encodeURIComponent(productCode)}`, {
    method: "POST"
  })
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`)
      return response.json()
    })
    .then(result => {
      if (result.success) {
        showToast("Éxito", "Imagen eliminada correctamente", "success")
        document.getElementById("productImagePreview").innerHTML = 
          '<div class="text-center text-muted"><i class="fas fa-image"></i> Sin imagen</div>'
      } else {
        showToast("Error", result.message || "No se pudo eliminar la imagen", "error")
      }
    })
    .catch(error => {
      console.error("Error deleting product image:", error)
      showToast("Error", "Error al eliminar la imagen: " + error.message, "error")
    })
}

// Función para previsualizar la imagen seleccionada antes de subirla
function setupImagePreview() {
  const fileInput = document.getElementById("productImage")
  const previewContainer = document.getElementById("productImagePreview")
  
  if (fileInput && previewContainer) {
    fileInput.addEventListener("change", function() {
      if (this.files && this.files[0]) {
        const reader = new FileReader()
        
        reader.onload = function(e) {
          previewContainer.innerHTML = `
            <div class="text-center">
              <img src="${e.target.result}" alt="Vista previa" class="img-fluid rounded product-image-preview" />
              <p class="text-muted small mt-1">Vista previa (no guardada)</p>
            </div>
          `
        }
        
        reader.readAsDataURL(this.files[0])
      } else {
        // Si no hay archivo seleccionado y hay código de producto, intentar cargar la imagen existente
        const productCode = document.getElementById("productCode").value
        if (productCode) {
          loadProductImage(productCode)
        } else {
          previewContainer.innerHTML = '<div class="text-center text-muted"><i class="fas fa-image"></i> Sin imagen</div>'
        }
      }
    })
  }
}

export async function initializeProductMaintenance(){

  if (document.getElementById("productsMaintenanceTable")) {
    
    await initProductMaintenance()
    setupImagePreview()
    handleDepartmentChange()
    
    // Modificar el formulario de producto para manejar la validación
    const productForm = document.getElementById("productForm")
    if (productForm) {
      productForm.addEventListener("submit", function(event) {
        event.preventDefault()
        
        if (validateProductForm()) {
          saveProduct(event)
        }
      })
    }
  } else {
    console.log("Products maintenance table not found, skipping initialization")
  }
}

// Add a global error handler
window.addEventListener("error", (event) => {
  console.error("Global error caught:", event.error)
  showToast("Error", "Se produjo un error en la aplicación. Consulte la consola para más detalles.", "error")
})

// Add unhandled promise rejection handler
window.addEventListener("unhandledrejection", (event) => {
  console.error("Unhandled promise rejection:", event.reason)
  showToast("Error", "Se produjo un error en una operación asíncrona. Consulte la consola para más detalles.", "error")
})

// Ejemplo de un modal dinámico con template
  let overlayRef = null;

  function openDialog2(type, event, element) {
    if(element.column===3 || element.column===4){
      element.oldValue=element.oldValue.replace("$", "")
    }
    // cerrar overlay anterior
    if (overlayRef) overlayRef.remove();

    // obtener template
    const template = document.querySelector("#modalActualizarProducto");
    const modalContent = template.content.cloneNode(true);

    // crear overlay
    overlayRef = document.createElement("div");
    overlayRef.classList.add("overlay");

    // colocar modal dentro del overlay
    overlayRef.appendChild(modalContent);

    // insertar en DOM
    document.body.appendChild(overlayRef);

    // posicionar relativo al target
    const target = event.target;
    const rect = target.getBoundingClientRect();

    const modalBox = overlayRef.querySelector(".modal-content");
    modalBox.style.position = "absolute";
    modalBox.style.top = window.scrollY + rect.bottom + "px"; 
    modalBox.style.left =  rect.left + "px"; 

// 🔹 Detectar input
  const input = overlayRef.querySelector("#datoInput");

  // 🔹 Si la columna es 3 o 4 → input numérico
  if (element.column === 3 || element.column === 4) {
    input.type = "number";
    input.min = "0"; // opcional
  } else {
    input.type = "text";
  }
    // cerrar con clic en backdrop
    overlayRef.addEventListener("click", (e) => {
      if (e.target === overlayRef) overlayRef.remove();
    });

    // botones de acción
    overlayRef.querySelector(".close-btn").addEventListener("click", () => {
      overlayRef.remove();
    });

    overlayRef.querySelector(".save-btn").addEventListener("click", () => {
      const value = overlayRef.querySelector("#datoInput").value;
       // 🚀 Aquí actualizamos la celda en DataTables
    const cell = element.cell
    Swal.fire({
      title: "¿Estás seguro?",
      text: `Vas a cambiar el valor a: "${value}"`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, guardar",
      cancelButtonText: "Cancelar"
    }).then((result) => {
      if (result.isConfirmed) {
        // 🚀 Actualizamos la celda en DataTable
        updateProductColumn(element.column,element.itemCode, value, cell); // Llamada a la función para actualizar en la API
        

        overlayRef.remove();
      }
    });
      
      
    });
  }
  function updateProductColumn(column, itemCode, newValue,cell) {
    let userId = -1; // Asegúrate de que currentUserId esté definido en tu contexto
    const urlApiUserId='api_proxy.php?endpoint=GetLoggedUserId';
    const timeoutIdD = setTimeout(() => {
    toggleLoading(false)
    showToast("Error", "La solicitud ha tardado demasiado tiempo. Por favor, inténtelo de nuevo.", "error")
  }, 30000) // 30 seconds timeout
    fetch(urlApiUserId)
    .then((response) => {
      clearTimeout(timeoutIdD) // Clear the timeout

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      const result = data
      if(result && result.success && result.success == true){
        userId = result.userId
      }
      let paramsObj={};
      let apiEndPoint = ``
    switch (column) {
      case 1:
        apiEndPoint = 'ProdNameChange'
        paramsObj = {ItemCode:itemCode,NewName:newValue,UserID:userId};
        break;
      case 2:
        apiEndPoint = 'ProdBarcodeChange'
        paramsObj = {ItemCode:itemCode,NewBarcode:newValue,UserID:userId};
        break;
      case 4:
        apiEndPoint = 'ProdCostChange'
        paramsObj = {ItemCode:itemCode,NewCost:newValue,UserID:userId};
        break;
      case 3:
        apiEndPoint = 'ProdPriceChange'
        paramsObj = {ItemCode:itemCode,NewPrice:newValue,UserID:userId};
        break;
      case 5:
        apiEndPoint = 'ProdDepartmentChange'
        paramsObj = {ItemCode:itemCode,NewDepartment:newValue,UserID:userId};
        break;
      case 6:
        apiEndPoint = 'ProdCategoryChange'
        paramsObj = {ItemCode:itemCode,NewCategory:newValue,UserID:userId};
        break;
    }
  fetchData(apiEndPoint, paramsObj)
    .then((data) => {
      const result = data
      if(result && result.success && result.success == true && result.status==200){
        if(column===3 || column===4){
          cell.data('$' + newValue).draw();
        }else{
          cell.data(newValue).draw();
        }
        Swal.fire({
          title: "Guardado",
          text: "El cambio se realizó con éxito",
          icon: "success",
          timer: 1500,
          showConfirmButton: false
        });
      }else{
        Swal.fire({
          title: "Error",
          text: "No se pudo realizar el cambio",
          icon: "error",
          timer: 1500,
          showConfirmButton: false
        });
        console.error("Error actualizando el producto:", result.message || "Error desconocido");
      }
    })
    .catch((error) => {
      clearTimeout(timeoutId) // Clear the timeout
      console.error("Error actualizando el producto:", error)
      showToast("Error", "No se pudo actualizar el producto: " + error.message, "error")
    })
    .finally(() => {
      toggleLoading(false)
    })
    })
    
  }

 
