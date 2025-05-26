<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Bins - Dashboard</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Firebase SDK -->
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/9.0.0/firebase-app.js';
        import { getDatabase, ref, onValue } from 'https://www.gstatic.com/firebasejs/9.0.0/firebase-database.js';
        
        // Configuración de Firebase (reemplaza con tu configuración)
        const firebaseConfig = {
            // Pega aquí tu configuración de Firebase
            apiKey: "tu-api-key",
            authDomain: "tu-auth-domain",
            databaseURL: "https://contenedorinteligente-8a0d5-default-rtdb.firebaseio.com/",
            projectId: "tu-project-id",
            storageBucket: "tu-storage-bucket",
            messagingSenderId: "tu-messaging-sender-id",
            appId: "tu-app-id"
        };

        // Inicializar Firebase
        const app = initializeApp(firebaseConfig);
        const database = getDatabase(app);
        
        // Hacer Firebase disponible globalmente
        window.firebaseDB = database;
        window.firebaseRef = ref;
        window.firebaseOnValue = onValue;
    </script>
    
    <style>
        [x-cloak] { display: none !important; }
        .pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(2.4); opacity: 0; }
        }
        .progress-bar {
            transition: width 0.5s ease-in-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-green-50 min-h-screen">
    
    <div x-data="smartBinsApp()" x-init="init()" x-cloak class="min-h-screen">
        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gradient-to-r from-blue-500 to-green-500 rounded-xl">
                                <i data-lucide="recycle" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-gray-900">Smart Bins</h1>
                                <p class="text-sm text-gray-500">Sistema de Contenedores Inteligentes</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Estado de conexión -->
                        <div class="flex items-center space-x-2">
                            <div :class="isConnected ? 'bg-green-500' : 'bg-red-500'" 
                                 class="w-2 h-2 rounded-full"></div>
                            <span :class="isConnected ? 'text-green-600' : 'text-red-600'" 
                                  class="text-sm font-medium">
                                <span x-text="isConnected ? 'Conectado' : 'Desconectado'"></span>
                            </span>
                        </div>
                        
                        <!-- Última actualización -->
                        <div class="text-sm text-gray-500">
                            <span x-text="'Actualizado: ' + lastUpdate"></span>
                        </div>
                        
                        <!-- Notificaciones -->
                        <div class="relative">
                            <button class="p-2 text-gray-400 hover:text-gray-500 relative">
                                <i data-lucide="bell" class="w-5 h-5"></i>
                                <span x-show="estadisticas?.necesitan_atencion > 0" 
                                      class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                    <span x-text="estadisticas?.necesitan_atencion || 0"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Estadísticas rápidas -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Contenedores -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Contenedores</p>
                            <p class="text-3xl font-bold text-gray-900" x-text="estadisticas?.total || 0"></p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <i data-lucide="trash-2" class="w-6 h-6 text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Contenedores Llenos -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Contenedores Llenos</p>
                            <p class="text-3xl font-bold text-red-600" x-text="estadisticas?.llenos || 0"></p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-xl">
                            <i data-lucide="alert-circle" class="w-6 h-6 text-red-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Promedio de Llenado -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Promedio Llenado</p>
                            <p class="text-3xl font-bold text-orange-600">
                                <span x-text="(estadisticas?.promedio_llenado || 0)"></span>%
                            </p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-xl">
                            <i data-lucide="bar-chart-3" class="w-6 h-6 text-orange-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Estado General -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Estado General</p>
                            <p class="text-2xl font-bold" 
                               :class="{
                                   'text-green-600': (estadisticas?.estado_general || 'Normal') === 'Normal',
                                   'text-yellow-600': (estadisticas?.estado_general || 'Normal') === 'Atención',
                                   'text-red-600': (estadisticas?.estado_general || 'Normal') === 'Crítico'
                               }" 
                               x-text="estadisticas?.estado_general || 'Cargando...'"></p>
                        </div>
                        <div class="p-3 rounded-xl"
                             :class="{
                                 'bg-green-100': (estadisticas?.estado_general || 'Normal') === 'Normal',
                                 'bg-yellow-100': (estadisticas?.estado_general || 'Normal') === 'Atención',
                                 'bg-red-100': (estadisticas?.estado_general || 'Normal') === 'Crítico'
                             }">
                            <i data-lucide="activity" class="w-6 h-6"
                               :class="{
                                   'text-green-600': (estadisticas?.estado_general || 'Normal') === 'Normal',
                                   'text-yellow-600': (estadisticas?.estado_general || 'Normal') === 'Atención',
                                   'text-red-600': (estadisticas?.estado_general || 'Normal') === 'Crítico'
                               }"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenedores Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <template x-for="contenedor in Object.values(contenedores || {})" :key="contenedor.Id">
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <!-- Header del contenedor -->
                        <div class="p-6 pb-4">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <div class="p-2 rounded-lg"
                                         :class="{
                                             'bg-green-100': contenedor.color === 'green',
                                             'bg-blue-100': contenedor.color === 'blue',
                                             'bg-orange-100': contenedor.color === 'orange',
                                             'bg-red-100': contenedor.color === 'red'
                                         }">
                                        <i :data-lucide="contenedor.icon" class="w-5 h-5"
                                           :class="{
                                               'text-green-600': contenedor.color === 'green',
                                               'text-blue-600': contenedor.color === 'blue',
                                               'text-orange-600': contenedor.color === 'orange',
                                               'text-red-600': contenedor.color === 'red'
                                           }"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900" x-text="contenedor.Nombre"></h3>
                                        <p class="text-sm text-gray-500" x-text="'ID: ' + contenedor.Id"></p>
                                    </div>
                                </div>
                                
                                <!-- Estado del contenedor -->
                                <div class="flex items-center space-x-2">
                                    <i :data-lucide="contenedor.status_icon" class="w-4 h-4"
                                       :class="{
                                           'text-green-500': contenedor.color === 'green',
                                           'text-blue-500': contenedor.color === 'blue',
                                           'text-orange-500': contenedor.color === 'orange',
                                           'text-red-500': contenedor.color === 'red'
                                       }"></i>
                                </div>
                            </div>

                            <!-- Porcentaje principal -->
                            <div class="text-center mb-4">
                                <div class="text-4xl font-bold mb-2"
                                     :class="{
                                         'text-green-600': contenedor.color === 'green',
                                         'text-blue-600': contenedor.color === 'blue',
                                         'text-orange-600': contenedor.color === 'orange',
                                         'text-red-600': contenedor.color === 'red'
                                     }">
                                    <span x-text="contenedor.Porcentaje"></span>%
                                </div>
                                <p class="text-sm font-medium text-gray-600" x-text="contenedor.estado_texto"></p>
                            </div>

                            <!-- Barra de progreso -->
                            <div class="relative">
                                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                    <div class="progress-bar h-full rounded-full transition-all duration-500"
                                         :class="{
                                             'bg-green-500': contenedor.color === 'green',
                                             'bg-blue-500': contenedor.color === 'blue',
                                             'bg-orange-500': contenedor.color === 'orange',
                                             'bg-red-500': contenedor.color === 'red'
                                         }"
                                         :style="`width: ${contenedor.Porcentaje}%`"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer del contenedor -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center space-x-2 text-gray-500">
                                    <i data-lucide="wifi" class="w-4 h-4"></i>
                                    <span x-text="contenedor.Estado ? 'Activo' : 'Inactivo'"></span>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <!-- Botón de detalles -->
                                    <button @click="verDetalles(contenedor)" 
                                            class="px-3 py-1 text-xs font-medium rounded-lg transition-colors"
                                            :class="{
                                                'bg-green-100 text-green-700 hover:bg-green-200': contenedor.color === 'green',
                                                'bg-blue-100 text-blue-700 hover:bg-blue-200': contenedor.color === 'blue',
                                                'bg-orange-100 text-orange-700 hover:bg-orange-200': contenedor.color === 'orange',
                                                'bg-red-100 text-red-700 hover:bg-red-200': contenedor.color === 'red'
                                            }">
                                        Ver detalles
                                    </button>
                                    
                                    <!-- Indicador de alerta -->
                                    <div x-show="contenedor.Porcentaje >= 90" class="pulse-ring w-3 h-3 bg-red-500 rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Loading state -->
            <div x-show="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600">Cargando contenedores desde Firebase...</p>
            </div>

            <!-- Error state -->
            <div x-show="error" class="text-center py-12">
                <div class="max-w-md mx-auto">
                    <div class="p-4 bg-red-100 rounded-full w-16 h-16 mx-auto mb-4">
                        <i data-lucide="wifi-off" class="w-8 h-8 text-red-500"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Error de conexión</h3>
                    <p class="text-gray-500 mb-4" x-text="errorMessage"></p>
                    <button @click="loadData()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        Reintentar
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div x-show="!loading && !error && Object.keys(contenedores || {}).length === 0" class="text-center py-12">
                <div class="max-w-md mx-auto">
                    <div class="p-4 bg-gray-100 rounded-full w-16 h-16 mx-auto mb-4">
                        <i data-lucide="trash-2" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay contenedores disponibles</h3>
                    <p class="text-gray-500">No se han encontrado contenedores en Firebase.</p>
                </div>
            </div>
        </div>

        <!-- Modal de detalles -->
        <div x-show="showModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4" @click.away="showModal = false">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Detalles del Contenedor</h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                
                <div x-show="modalContenedor" class="space-y-4">
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-2" x-text="(modalContenedor?.Porcentaje || 0) + '%'"></div>
                        <p class="text-gray-600" x-text="modalContenedor?.estado_texto || 'Sin datos'"></p>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Nombre:</span>
                            <span class="font-medium" x-text="modalContenedor?.Nombre || 'N/A'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">ID:</span>
                            <span class="font-medium" x-text="modalContenedor?.Id || 'N/A'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Estado:</span>
                            <span class="font-medium" x-text="modalContenedor?.Estado ? 'Activo' : 'Inactivo'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Porcentaje:</span>
                            <span class="font-medium" x-text="(modalContenedor?.Porcentaje || 0) + '%'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function smartBinsApp() {
            console.log("✅ smartBinsApp ejecutado");
            
            return {
                contenedores: {},
                estadisticas: {
                    total: 0,
                    llenos: 0,
                    medios: 0,
                    vacios: 0,
                    promedio_llenado: 0,
                    necesitan_atencion: 0,
                    estado_general: 'Normal'
                },
                isConnected: false,
                lastUpdate: '',
                loading: true,
                error: false,
                errorMessage: '',
                showModal: false,
                modalContenedor: null,

                init() {
                    console.log("🚀 Inicializando Smart Bins App");
                    
                    // Esperar a que Firebase esté disponible
                    this.waitForFirebase();
                },

                waitForFirebase() {
                    if (window.firebaseDB && window.firebaseRef && window.firebaseOnValue) {
                        console.log("🔥 Firebase disponible, cargando datos...");
                        this.loadData();
                    } else {
                        console.log("⏳ Esperando Firebase...");
                        setTimeout(() => this.waitForFirebase(), 500);
                    }
                },

                async loadData() {
                    try {
                        this.loading = true;
                        this.error = false;
                        console.log("📡 Conectando a Firebase...");
                        
                        // Referencia a los contenedores en Firebase
                        const contenedoresRef = window.firebaseRef(window.firebaseDB, 'Contenedores');
                        
                        // Escuchar cambios en tiempo real
                        window.firebaseOnValue(contenedoresRef, (snapshot) => {
                            console.log("📊 Datos recibidos de Firebase");
                            const data = snapshot.val();
                            
                            if (data) {
                                this.contenedores = this.processFirebaseData(data);
                                this.calcularEstadisticas();
                                this.isConnected = true;
                                this.lastUpdate = new Date().toLocaleTimeString();
                                console.log("✅ Datos procesados:", this.contenedores);
                            } else {
                                console.log("⚠️ No hay datos en Firebase");
                                this.contenedores = {};
                            }
                            
                            this.loading = false;
                            
                            // Re-inicializar iconos después de actualización
                            setTimeout(() => {
                                if (typeof lucide !== 'undefined') {
                                    lucide.createIcons();
                                }
                            }, 100);
                        }, (error) => {
                            console.error("❌ Error de Firebase:", error);
                            this.error = true;
                            this.errorMessage = "Error al conectar con Firebase: " + error.message;
                            this.isConnected = false;
                            this.loading = false;
                        });
                        
                    } catch (error) {
                        console.error("❌ Error cargando datos:", error);
                        this.error = true;
                        this.errorMessage = "Error de conexión: " + error.message;
                        this.isConnected = false;
                        this.loading = false;
                    }
                },

                processFirebaseData(firebaseData) {
                    const contenedores = {};
                    
                    Object.entries(firebaseData).forEach(([key, contenedor]) => {
                        // Procesar cada contenedor de Firebase
                        const porcentaje = contenedor.Porcentaje || 0;
                        
                        contenedores[key] = {
                            ...contenedor,
                            // Agregar propiedades calculadas para la UI
                            color: this.getColorByPercentage(porcentaje),
                            icon: this.getIconByName(contenedor.Nombre),
                            status_icon: this.getStatusIcon(porcentaje),
                            estado_texto: this.getEstadoTexto(porcentaje)
                        };
                    });
                    
                    return contenedores;
                },

                getColorByPercentage(porcentaje) {
                    if (porcentaje >= 90) return 'red';
                    if (porcentaje >= 70) return 'orange';
                    if (porcentaje >= 40) return 'blue';
                    return 'green';
                },

                getIconByName(nombre) {
                    const nombreLower = (nombre || '').toLowerCase();
                    if (nombreLower.includes('orgánico') || nombreLower.includes('organico')) return 'leaf';
                    if (nombreLower.includes('plástico') || nombreLower.includes('plastico')) return 'bottle';
                    if (nombreLower.includes('papel')) return 'file-text';
                    if (nombreLower.includes('vidrio')) return 'wine';
                    if (nombreLower.includes('electrón') || nombreLower.includes('electron')) return 'smartphone';
                    return 'trash-2';
                },

                getStatusIcon(porcentaje) {
                    if (porcentaje >= 90) return 'alert-circle';
                    if (porcentaje >= 70) return 'alert-triangle';
                    if (porcentaje >= 40) return 'info';
                    return 'check';
                },

                getEstadoTexto(porcentaje) {
                    if (porcentaje >= 95) return 'Requiere vaciado urgente';
                    if (porcentaje >= 90) return 'Lleno';
                    if (porcentaje >= 70) return 'Casi lleno';
                    if (porcentaje >= 40) return 'Medio';
                    if (porcentaje >= 20) return 'Poco lleno';
                    return 'Vacío';
                },

                calcularEstadisticas() {
                    const contenedoresArray = Object.values(this.contenedores);
                    
                    if (contenedoresArray.length === 0) {
                        this.estadisticas = {
                            total: 0,
                            llenos: 0,
                            medios: 0,
                            vacios: 0,
                            promedio_llenado: 0,
                            necesitan_atencion: 0,
                            estado_general: 'Normal'
                        };
                        return;
                    }
                    
                    this.estadisticas = {
                        total: contenedoresArray.length,
                        llenos: contenedoresArray.filter(c => c.Porcentaje >= 90).length,
                        medios: contenedoresArray.filter(c => c.Porcentaje >= 40 && c.Porcentaje < 90).length,
                        vacios: contenedoresArray.filter(c => c.Porcentaje < 40).length,
                        promedio_llenado: Math.round(
                            contenedoresArray.reduce((sum, c) => sum + (c.Porcentaje || 0), 0) / contenedoresArray.length
                        ),
                        necesitan_atencion: contenedoresArray.filter(c => c.Porcentaje >= 85).length,
                        estado_general: this.calcularEstadoGeneral(contenedoresArray)
                    };
                },

                calcularEstadoGeneral(contenedores) {
                    const criticos = contenedores.filter(c => c.Porcentaje >= 95).length;
                    const atencion = contenedores.filter(c => c.Porcentaje >= 85).length;
                    
                    if (criticos > 0) return 'Crítico';
                    if (atencion > 2) return 'Atención';
                    return 'Normal';
                },

                verDetalles(contenedor) {
                    this.modalContenedor = contenedor;
                    this.showModal = true;
                    
                    // Re-inicializar iconos en el modal
                    setTimeout(() => {
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }, 50);
                }
            };
        }
    </script>
</body>
</html>