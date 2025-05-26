<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Mostrar el dashboard principal
     */
    public function index(): View
    {
        $contenedores = $this->firebaseService->getAllContenedoresProcesados();
        $estadisticas = $this->firebaseService->getEstadisticas();
        
        return view('dashboard.index', compact('contenedores', 'estadisticas'));
    }

    /**
     * API: Obtener todos los contenedores
     */
    public function apiContenedores(): JsonResponse
    {
        try {
            $contenedores = $this->firebaseService->getAllContenedoresProcesados();
            
            return response()->json([
                'success' => true,
                'data' => $contenedores,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener contenedores',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * API: Obtener un contenedor específico
     */
    public function apiContenedor(string $id): JsonResponse
    {
        try {
            $contenedor = $this->firebaseService->getContenedor($id);
            
            if (!$contenedor) {
                return response()->json([
                    'success' => false,
                    'error' => 'Contenedor no encontrado',
                    'message' => "No se encontró el contenedor con ID: {$id}"
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $contenedor,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener contenedor',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * API: Obtener estadísticas generales
     */
    public function apiEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = $this->firebaseService->getEstadisticas();
            
            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener estadísticas',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Vista: Mostrar contenedor específico
     */
    public function show(string $id): View
    {
        $contenedor = $this->firebaseService->getContenedor($id);
        
        if (!$contenedor) {
            abort(404, 'Contenedor no encontrado');
        }
        
        // Obtener otros contenedores para navegación
        $todosContenedores = $this->firebaseService->getAllContenedoresProcesados();
        $contenedoresIds = array_keys($todosContenedores);
        $currentIndex = array_search($id, $contenedoresIds);
        
        $navegacion = [
            'current' => $currentIndex !== false ? $currentIndex : 0,
            'total' => count($contenedoresIds),
            'ids' => $contenedoresIds,
            'previous' => $currentIndex > 0 ? $contenedoresIds[$currentIndex - 1] : null,
            'next' => $currentIndex < count($contenedoresIds) - 1 ? $contenedoresIds[$currentIndex + 1] : null
        ];
        
        return view('dashboard.contenedor', compact('contenedor', 'navegacion'));
    }

    /**
     * API: Datos en tiempo real (SSE - Server-Sent Events)
     */
    public function stream(Request $request)
    {
        $response = response()->stream(function () {
            while (true) {
                try {
                    $contenedores = $this->firebaseService->getAllContenedoresProcesados();
                    $estadisticas = $this->firebaseService->getEstadisticas();
                    
                    $data = [
                        'contenedores' => $contenedores,
                        'estadisticas' => $estadisticas,
                        'timestamp' => now()->toISOString()
                    ];
                    
                    echo "data: " . json_encode($data) . "\n\n";
                    
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                    
                    sleep(5); // Actualizar cada 5 segundos
                } catch (\Exception $e) {
                    echo "event: error\n";
                    echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
                    break;
                }
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    /**
     * Vista: Página de estadísticas detalladas
     */
    public function estadisticas(): View
    {
        $estadisticas = $this->firebaseService->getEstadisticas();
        $contenedores = $this->firebaseService->getAllContenedoresProcesados();
        
        // Preparar datos para gráficos
        $datosGraficos = $this->prepararDatosGraficos($contenedores);
        
        return view('dashboard.estadisticas', compact('estadisticas', 'contenedores', 'datosGraficos'));
    }

    /**
     * Preparar datos para gráficos
     */
    private function prepararDatosGraficos(array $contenedores): array
    {
        $porcentajes = [];
        $nombres = [];
        $colores = [];
        $zonas = [];
        
        foreach ($contenedores as $contenedor) {
            $nombres[] = $contenedor['nombre'];
            $porcentajes[] = $contenedor['porcentaje'];
            $colores[] = $contenedor['color_hex'];
            
            $zona = $contenedor['ubicacion'];
            if (!isset($zonas[$zona])) {
                $zonas[$zona] = [];
            }
            $zonas[$zona][] = $contenedor;
        }
        
        return [
            'porcentajes' => $porcentajes,
            'nombres' => $nombres,
            'colores' => $colores,
            'zonas' => $zonas,
            'total_contenedores' => count($contenedores)
        ];
    }

    /**
     * API: Health check
     */
    public function health(): JsonResponse
    {
        try {
            // Intentar obtener datos de Firebase
            $contenedores = $this->firebaseService->getContenedores();
            
            return response()->json([
                'status' => 'ok',
                'firebase' => 'connected',
                'contenedores_count' => count($contenedores),
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'firebase' => 'disconnected',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }
}