<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Bins - Contenedor #{{ $contenedor->id ?? 'N/A' }}</title>
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
        .pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(2.4); opacity: 0; }
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .progress-bar {
            transition: width 0.5s ease-in-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-green-50 min-h-screen">
    
    <div x-data="contenedorApp()" x-init="init()" x-cloak class="min-h-screen">
        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 text-gray-600 hover:text-gray-900 transition-colors">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            <span>Volver al Dashboard</span>
                        </a>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gradient-to-r from-blue-500 to-green-500 rounded-xl">
                                <i data-lucide="recycle" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-gray-900">Smart Bins</h1>
                                <p class="text-sm text-gray-500">Detalle del Contenedor</p>
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
                    </div>
                </div>
            </div>
        </header>

        <!-- Contenido principal -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            
            <!-- Información principal del contenedor -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 mb-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Información básica -->
                    <div class="lg:col-span-1">
                        <div class="text-center">
                            <div class="mx-auto w-32 h-32 rounded-3xl mb-6 flex items-center justify-center"
                                 :class="{
                                     'bg-green-100': contenedor.color === 'green',
                                     'bg-blue-100': contenedor.color === 'blue',
                                     'bg-orange-100': contenedor.color === 'orange',
                                     'bg-red-100': contenedor.color === 'red'
                                 }">
                                <i :data-lucide="contenedor.icon" class="w-16 h-16"
                                   :class="{
                                       'text-green-600': contenedor.color === 'green',
                                       'text-blue-600': contenedor.color === 'blue',
                                       'text-orange-600': contenedor.color === 'orange',
                                       'text-red-600': contenedor.color === 'red'
                                   }"></i>
                            </div>
                            
                            <h2 class="text-3xl font-bold text-gray-900 mb-2" x-text="contenedor.nombre"></h2>
                            <p class="text-lg text-gray-600 mb-4" x-text="contenedor.ubicacion"></p>
                            <p class="text-sm text-gray-500 mb-6">ID: <span x-text="contenedor.id"></span></p>
                            
                            <!-- Estado actual -->
                            <div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium"
                                 :class="{
                                     'bg-green-100 text-green-700': contenedor.color === 'green',
                                     'bg-blue-100 text-blue-700': contenedor.color === 'blue',
                                     'bg-orange-100 text-orange-700': contenedor.color === 'orange',
                                     'bg-red-100 text-red-700': contenedor.color === 'red'
                                 }">
                                <i :data-lucide="contenedor.status_icon" class="w-4 h-4 mr-2"></i>
                                <span x-text="contenedor.estado"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Medidor principal -->
                    <div class="lg:col-span-1 flex items-center justify-center">
                        <div class="relative w-48 h-48">
                            <!-- Círculo de progreso -->
                            <svg class="w-48 h-48 transform -rotate-90" viewBox="0 0 100 100">
                                <!-- Fondo del círculo -->
                                <circle cx="50" cy="50" r="40" stroke="#e5e7eb" stroke-width="8" fill="none"></circle>
                                <!-- Progreso -->
                                <circle cx="50" cy="50" r="40" stroke="currentColor" stroke-width="8" fill="none"
                                        stroke-linecap="round"
                                        :stroke-dasharray="`${contenedor.porcentaje * 2.51} 251`"
                                        :class="{
                                            'text-green-500': contenedor.color === 'green',
                                            'text-blue-500': contenedor.color === 'blue',
                                            'text-orange-500': contenedor.color === 'orange',
                                            'text-red-500': contenedor.color === 'red'
                                        }"
                                        class="transition-all duration-500"></circle>
                            </svg>
                            
                            <!-- Porcentaje en el centro -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <div class="text-4xl font-bold"
                                         :class="{
                                             'text-green-600': contenedor.color === 'green',
                                             'text-blue-600': contenedor.color === 'blue',
                                             'text-orange-600': contenedor.color === 'orange',
                                             'text-red-600': contenedor.color === 'red'
                                         }">
                                        <span x-text="contenedor.porcentaje"></span>%
                                    </div>
                                    <div class="text-sm text-gray-500">Llenado</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información adicional -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="map-pin" class="w-5 h-5 text-gray-500"></i>
                                    <span class="text-sm font-medium text-gray-700">Ubicación</span>
                                </div>
                                <span class="text-sm text-gray-900" x-text="contenedor.ubicacion"></span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="clock" class="w-5 h-5 text-gray-500"></i>
                                    <span class="text-sm font-medium text-gray-700">Última actualización</span>
                                </div>
                                <span class="text-sm text-gray-900" x-text="contenedor.updated_at"></span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="thermometer" class="w-5 h-5 text-gray-500"></i>
                                    <span class="text-sm font-medium text-gray-700">Temperatura</span>
                                </div>
                                <span class="text-sm text-gray-900" x-text="contenedor.temperatura || '22°C'"></span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="battery" class="w-5 h-5 text-gray-500"></i>
                                    <span class="text-sm font-medium text-gray-700">Batería</span>
                                </div>
                                <span class="text-sm text-gray-900" x-text="contenedor.bateria || '85%'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico histórico -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-gray-900">Historial de Llenado</h3>
                    <div class="flex space-x-2">
                        <button @click="changeTimeRange('24h')" 
                                :class="timeRange === '24h' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'"
                                class="px-3 py-1 rounded-lg text-sm font-medium transition-colors">
                            24h
                        </button>
                        <button @click="changeTimeRange('7d')" 
                                :class="timeRange === '7d' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'"
                                class="px-3 py-1 rounded-lg text-sm font-medium transition-colors">
                            7d
                        </button>
                        <button @click="changeTimeRange('30d')" 
                                :class="timeRange === '30d' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'"
                                class="px-3 py-1 rounded-lg text-sm font-medium transition-colors">
                            30d
                        </button>
                    </div>
                </div>
                
                <div class="h-64">
                    <canvas id="historialChart"></canvas>
                </div>
            </div>

            <!-- Alertas y notificaciones -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Alertas recientes -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Alertas Recientes</h3>
                    <div class="space-y-3">
                        <template x-for="alerta in alertas" :key="alerta.id">
                            <div class="flex items-start space-x-3 p-3 rounded-lg"
                                 :class="{
                                     'bg-red-50': alerta.tipo === 'critico',
                                     'bg-yellow-50': alerta.tipo === 'advertencia',
                                     'bg-blue-50': alerta.tipo === 'info'
                                 }">
                                <i :data-lucide="alerta.icon" class="w-5 h-5 mt-0.5"
                                   :class="{
                                       'text-red-500': alerta.tipo === 'critico',
                                       'text-yellow-500': alerta.tipo === 'advertencia',
                                       'text-blue-500': alerta.tipo === 'info'
                                   }"></i>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900" x-text="alerta.mensaje"></p>
                                    <p class="text-xs text-gray-500" x-text="alerta.fecha"></p>
                                </div>
                            </div>
                        </template>
                        
                        <div x-show="alertas.length === 0" class="text-center py-4 text-gray-500">
                            <i data-lucide="check-circle" class="w-8 h-8 mx-auto mb-2 text-green-500"></i>
                            <p>No hay alertas recientes</p>
                        </div>
                    </div>
                </div>

                <!-- Acciones rápidas -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Acciones Rápidas</h3>
                    <div class="space-y-3">
                        <button class="w-full flex items-center justify-between p-3 bg-red-50 hover:bg-red-100 rounded-lg transition-colors group">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="truck" class="w-5 h-5 text-red-600"></i>
                                <span class="text-sm font-medium text-red-700">Solicitar Recolección</span>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-red-600 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                        
                        <button class="w-full flex items-center justify-between p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors group">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="settings" class="w-5 h-5 text-blue-600"></i>
                                <span class="text-sm font-medium text-blue-700">Configurar Alertas</span>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-blue-600 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                        
                        <button class="w-full flex items-center justify-between p-3 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors group">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="wrench" class="w-5 h-5 text-yellow-600"></i>
                                <span class="text-sm font-medium text-yellow-700">Reportar Problema</span>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-yellow-600 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                        
                        <button class="w-full flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors group">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="download" class="w-5 h-5 text-green-600"></i>
                                <span class="text-sm font-medium text-green-700">Exportar Datos</span>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-green-600 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function contenedorApp() {
            return {
                contenedor: {
                    id: '{{ $contenedor->id ?? "001" }}',
                    nombre: 'Contenedor #{{ $contenedor->id ?? "001" }}',
                    ubicacion: '{{ $contenedor->ubicacion ?? "Ubicación por defecto" }}',
                    porcentaje: {{ $contenedor->porcentaje ?? 75 }},
                    estado: '{{ $contenedor->estado ?? "Medio lleno" }}',
                    color: '{{ $contenedor->color ?? "orange" }}',
                    icon: '{{ $contenedor->icon ?? "trash-2" }}',
                    status_icon: '{{ $contenedor->status_icon ?? "alert-circle" }}',
                    updated_at: '{{ $contenedor->updated_at ?? now()->format("H:i") }}',
                    temperatura: '22°C',
                    bateria: '85%'
                },
                alertas: [
                    {
                        id: 1,
                        tipo: 'advertencia',
                        icon: 'alert-triangle',
                        mensaje: 'Nivel de llenado alto (75%)',
                        fecha: 'Hace 15 minutos'
                    },
                    {
                        id: 2,
                        tipo: 'info',
                        icon: 'info',
                        mensaje: 'Mantenimiento programado',
                        fecha: 'Hace 2 horas'
                    }
                ],
                isConnected: false,
                lastUpdate: '',
                timeRange: '24h',
                chart: null,

                init() {
                    this.loadData();
                    this.initChart();
                    this.startRealTimeUpdates();
                    lucide.createIcons();
                },

                async loadData() {
                    try {
                        // Simular carga de datos del contenedor específico
                        const response = await fetch(`/api/contenedores/{{ $contenedor->id ?? "1" }}`);
                        if (response.ok) {
                            const data = await response.json();
                            if (data.success) {
                                this.contenedor = { ...this.contenedor, ...data.data };
                            }
                        }

                        this.isConnected = true;
                        this.lastUpdate = new Date().toLocaleTimeString();
                        
                    } catch (error) {
                        console.error('Error loading data:', error);
                        this.isConnected = false;
                    }
                },

                initChart() {
                    const ctx = document.getElementById('historialChart').getContext('2d');
                    
                    // Datos de ejemplo para el historial
                    const data = {
                        labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '24:00'],
                        datasets: [{
                            label: 'Nivel de llenado (%)',
                            data: [30, 35, 45, 60, 70, 75, 75],
                            borderColor: this.getChartColor(),
                            backgroundColor: this.getChartColor() + '20',
                            fill: true,
                            tension: 0.4
                        }]
                    };

                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: data,
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
                },

                getChartColor() {
                    const colors = {
                        green: '#10b981',
                        blue: '#3b82f6',
                        orange: '#f59e0b',
                        red: '#ef4444'
                    };
                    return colors[this.contenedor.color] || colors.blue;
                },

                changeTimeRange(range) {
                    this.timeRange = range;
                    // Aquí cargarías los datos según el rango seleccionado
                    this.updateChart();
                },

                updateChart() {
                    // Actualizar datos del gráfico según el rango de tiempo
                    // Esta función se implementaría con datos reales de la API
                },

                startRealTimeUpdates() {
                    // Actualizar cada 30 segundos
                    setInterval(() => {
                        this.loadData();
                    }, 30000);
                }
            }
        }
    </script>
</body>
</html>