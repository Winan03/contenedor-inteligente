<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Smart Bins</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(2.4); opacity: 0; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-green-50 min-h-screen">
    
    <div x-data="estadisticasApp()" x-init="init()" x-cloak class="min-h-screen">
        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl">
                                <i data-lucide="bar-chart-3" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-gray-900">Estadísticas Smart Bins</h1>
                                <p class="text-sm text-gray-500">Análisis y Métricas del Sistema</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Selector de período -->
                        <select x-model="periodoSeleccionado" @change="cargarDatos()" 
                                class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="hoy">Hoy</option>
                            <option value="semana">Esta Semana</option>
                            <option value="mes">Este Mes</option>
                            <option value="trimestre">Trimestre</option>
                        </select>
                        
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
                        
                        <!-- Botón de refresh -->
                        <button @click="cargarDatos()" 
                                class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <i data-lucide="refresh-cw" class="w-5 h-5" :class="{ 'animate-spin': loading }"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Estadísticas principales -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- KPIs principales -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total de contenedores -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Contenedores</p>
                            <p class="text-3xl font-bold text-gray-900" x-text="stats.total_contenedores"></p>
                            <p class="text-sm text-green-600 font-medium">
                                <i data-lucide="trending-up" class="w-4 h-4 inline mr-1"></i>
                                <span x-text="stats.crecimiento_contenedores"></span>% vs período anterior
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-xl floating">
                            <i data-lucide="trash-2" class="w-8 h-8 text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Promedio de llenado -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Promedio de Llenado</p>
                            <p class="text-3xl font-bold text-orange-600">
                                <span x-text="stats.promedio_llenado"></span>%
                            </p>
                            <p class="text-sm font-medium"
                               :class="stats.tendencia_llenado >= 0 ? 'text-red-600' : 'text-green-600'">
                                <i :data-lucide="stats.tendencia_llenado >= 0 ? 'trending-up' : 'trending-down'" 
                                   class="w-4 h-4 inline mr-1"></i>
                                <span x-text="Math.abs(stats.tendencia_llenado)"></span>% vs período anterior
                            </p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-xl floating">
                            <i data-lucide="bar-chart-2" class="w-8 h-8 text-orange-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Contenedores críticos -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Contenedores Críticos</p>
                            <p class="text-3xl font-bold text-red-600" x-text="stats.contenedores_criticos"></p>
                            <p class="text-sm text-gray-500">
                                ≥ 95% de capacidad
                            </p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-xl">
                            <i data-lucide="alert-triangle" class="w-8 h-8 text-red-600"></i>
                            <div x-show="stats.contenedores_criticos > 0" class="pulse-ring w-2 h-2 bg-red-500 rounded-full absolute"></div>
                        </div>
                    </div>
                </div>

                <!-- Eficiencia del sistema -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Eficiencia del Sistema</p>
                            <p class="text-3xl font-bold text-green-600">
                                <span x-text="stats.eficiencia_sistema"></span>%
                            </p>
                            <p class="text-sm text-green-600 font-medium">
                                <i data-lucide="check-circle" class="w-4 h-4 inline mr-1"></i>
                                Estado óptimo
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-xl floating">
                            <i data-lucide="activity" class="w-8 h-8 text-green-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos principales -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                
                <!-- Gráfico de tendencia de llenado -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Tendencia de Llenado</h3>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">Promedio de llenado</span>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="tendenciaChart"></canvas>
                    </div>
                </div>

                <!-- Distribución por estados -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Distribución por Estados</h3>
                        <i data-lucide="pie-chart" class="w-5 h-5 text-gray-400"></i>
                    </div>
                    <div class="chart-container">
                        <canvas id="distribucionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Gráfico de actividad por zonas -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Actividad por Zonas</h3>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">Zona A</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">Zona B</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">Zona C</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">Zona D</span>
                        </div>
                    </div>
                </div>
                <div style="height: 400px;">
                    <canvas id="zonasChart"></canvas>
                </div>
            </div>

            <!-- Tabla de resumen por contenedor -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Resumen por Contenedor</h3>
                        <div class="flex items-center space-x-2">
                            <input type="text" 
                                   x-model="filtroContenedor" 
                                   placeholder="Buscar contenedor..."
                                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <i data-lucide="search" class="w-5 h-5 text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contenedor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zona</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Llenado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Última Act.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Promedio Diario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tendencia</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="contenedor in contenedoresFiltrados" :key="contenedor.id">
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8">
                                                <div class="h-8 w-8 rounded-lg flex items-center justify-center"
                                                     :class="{
                                                         'bg-green-100': contenedor.estado_color === 'green',
                                                         'bg-blue-100': contenedor.estado_color === 'blue',
                                                         'bg-orange-100': contenedor.estado_color === 'orange',
                                                         'bg-red-100': contenedor.estado_color === 'red'
                                                     }">
                                                    <i :data-lucide="contenedor.icon" class="w-4 h-4"
                                                       :class="{
                                                           'text-green-600': contenedor.estado_color === 'green',
                                                           'text-blue-600': contenedor.estado_color === 'blue',
                                                           'text-orange-600': contenedor.estado_color === 'orange',
                                                           'text-red-600': contenedor.estado_color === 'red'
                                                       }"></i>
                                                </div>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900" x-text="contenedor.nombre"></div>
                                                <div class="text-sm text-gray-500" x-text="contenedor.id"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                              x-text="contenedor.zona"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                              :class="{
                                                  'bg-green-100 text-green-800': contenedor.estado_color === 'green',
                                                  'bg-blue-100 text-blue-800': contenedor.estado_color === 'blue',
                                                  'bg-orange-100 text-orange-800': contenedor.estado_color === 'orange',
                                                  'bg-red-100 text-red-800': contenedor.estado_color === 'red'
                                              }"
                                              x-text="contenedor.estado"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-grow bg-gray-200 rounded-full h-2 mr-3">
                                                <div class="h-2 rounded-full transition-all duration-300"
                                                     :class="{
                                                         'bg-green-500': contenedor.estado_color === 'green',
                                                         'bg-blue-500': contenedor.estado_color === 'blue',
                                                         'bg-orange-500': contenedor.estado_color === 'orange',
                                                         'bg-red-500': contenedor.estado_color === 'red'
                                                     }"
                                                     :style="`width: ${contenedor.porcentaje}%`"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900" x-text="contenedor.porcentaje + '%'"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="contenedor.ultima_actualizacion"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="contenedor.promedio_diario + '%'"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i :data-lucide="contenedor.tendencia >= 0 ? 'trending-up' : 'trending-down'" 
                                               class="w-4 h-4 mr-1"
                                               :class="contenedor.tendencia >= 0 ? 'text-red-500' : 'text-green-500'"></i>
                                            <span class="text-sm font-medium"
                                                  :class="contenedor.tendencia >= 0 ? 'text-red-600' : 'text-green-600'"
                                                  x-text="Math.abs(contenedor.tendencia) + '%'"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Loading overlay -->
        <div x-show="loading" class="fixed inset-0 bg-black bg-opacity-25 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-purple-600"></div>
                <span class="text-gray-700">Cargando estadísticas...</span>
            </div>
        </div>
    </div>

    <script>
        function estadisticasApp() {
            return {
                stats: {
                    total_contenedores: 0,
                    promedio_llenado: 0,
                    contenedores_criticos: 0,
                    eficiencia_sistema: 0,
                    crecimiento_contenedores: 0,
                    tendencia_llenado: 0
                },
                contenedores: [],
                filtroContenedor: '',
                periodoSeleccionado: 'hoy',
                isConnected: false,
                lastUpdate: '',
                loading: false,
                tendenciaChart: null,
                distribucionChart: null,
                zonasChart: null,

                init() {
                    this.cargarDatos();
                    this.inicializarGraficos();
                    this.iniciarActualizacionAutomatica();
                    lucide.createIcons();
                },

                get contenedoresFiltrados() {
                    if (!this.filtroContenedor) return this.contenedores;
                    return this.contenedores.filter(contenedor => 
                        contenedor.nombre.toLowerCase().includes(this.filtroContenedor.toLowerCase()) ||
                        contenedor.id.toLowerCase().includes(this.filtroContenedor.toLowerCase()) ||
                        contenedor.zona.toLowerCase().includes(this.filtroContenedor.toLowerCase())
                    );
                },

                async cargarDatos() {
                    try {
                        this.loading = true;
                        
                        // Simular datos para demo - en producción conectar con tu API
                        await new Promise(resolve => setTimeout(resolve, 1000));
                        
                        this.stats = {
                            total_contenedores: 24,
                            promedio_llenado: 67,
                            contenedores_criticos: 3,
                            eficiencia_sistema: 94,
                            crecimiento_contenedores: 8.5,
                            tendencia_llenado: -2.3
                        };

                        this.contenedores = [
                            {
                                id: 'CNT-001',
                                nombre: 'G-20',
                                zona: 'Zona A',
                                estado: 'Tacho vacío',
                                estado_color: 'green',
                                porcentaje: 25,
                                icon: 'trash-2',
                                ultima_actualizacion: '10:30 AM',
                                promedio_diario: 45,
                                tendencia: -5
                            },
                            {
                                id: 'CNT-002',
                                nombre: 'B-15',
                                zona: 'Zona B',
                                estado: 'Tacho lleno',
                                estado_color: 'red',
                                porcentaje: 98,
                                icon: 'trash-2',
                                ultima_actualizacion: '10:28 AM',
                                promedio_diario: 85,
                                tendencia: 12
                            },
                            {
                                id: 'CNT-003',
                                nombre: 'C-08',
                                zona: 'Zona C',
                                estado: 'Tacho medio',
                                estado_color: 'blue',
                                porcentaje: 55,
                                icon: 'trash-2',
                                ultima_actualizacion: '10:25 AM',
                                promedio_diario: 62,
                                tendencia: -8
                            },
                            {
                                id: 'CNT-004',
                                nombre: 'D-12',
                                zona: 'Zona D',
                                estado: 'Tacho casi lleno',
                                estado_color: 'orange',
                                porcentaje: 85,
                                icon: 'trash-2',
                                ultima_actualizacion: '10:22 AM',
                                promedio_diario: 78,
                                tendencia: 5
                            }
                        ];

                        this.isConnected = true;
                        this.lastUpdate = new Date().toLocaleTimeString();
                        
                        this.actualizarGraficos();
                        
                    } catch (error) {
                        console.error('Error cargando datos:', error);
                        this.isConnected = false;
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => {
                            lucide.createIcons();
                        });
                    }
                },

                inicializarGraficos() {
                    // Gráfico de tendencia
                    const tendenciaCtx = document.getElementById('tendenciaChart');
                    if (tendenciaCtx) {
                        this.tendenciaChart = new Chart(tendenciaCtx, {
                            type: 'line',
                            data: {
                                labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '24:00'],
                                datasets: [{
                                    label: 'Promedio de Llenado',
                                    data: [45, 52, 48, 65, 72, 68, 67],
                                    borderColor: '#3B82F6',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 100,
                                        ticks: {
                                            callback: function(value) {
                                                return value + '%';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Gráfico de distribución
                    const distribucionCtx = document.getElementById('distribucionChart');
                    if (distribucionCtx) {
                        this.distribucionChart = new Chart(distribucionCtx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Vacío (<30%)', 'Medio (30-70%)', 'Casi Lleno (70-95%)', 'Lleno (>95%)'],
                                datasets: [{
                                    data: [8, 10, 3, 3],
                                    backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#EF4444'],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }
                        });
                    }

                    // Gráfico de zonas
                    const zonasCtx = document.getElementById('zonasChart');
                    if (zonasCtx) {
                        this.zonasChart = new Chart(zonasCtx, {
                            type: 'bar',
                            data: {
                                labels: ['Zona A', 'Zona B', 'Zona C', 'Zona D'],
                                datasets: [{
                                    label: 'Promedio de Llenado',
                                    data: [45, 78, 62, 71],
                                    backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#8B5CF6'],
                                    borderRadius: 8
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 100,
                                        ticks: {
                                            callback: function(value) {
                                                return value + '%';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                },

                actualizarGraficos() {
                    // Actualizar con datos reales cuando estén disponibles
                    if (this.tendenciaChart) {
                        this.tendenciaChart.update();
                    }
                    if (this.distribucionChart) {
                        this.distribucionChart.update();
                    }
                    if (this.zonasChart) {
                        this.zonasChart.update();
                    }
                },

                iniciarActualizacionAutomatica() {
                    // Actualizar cada 60 segundos
                    setInterval(() => {
                        this.cargarDatos();
                    }, 60000);
                }
            }
        }
    </script>