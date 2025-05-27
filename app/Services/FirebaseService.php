<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private $database;
    private $factory;

    public function __construct()
    {
        $credentials = config('firebase.credentials');
        $factory = new Factory();

        if (str_starts_with($credentials, 'http')) {
            $json = Http::get($credentials)->body();  // descarga desde S3
            $factory = $factory->withServiceAccount($json);
        } else {
            $factory = $factory->withServiceAccount(base_path($credentials));
        }

        $this->database = $factory
            ->withDatabaseUri(config('firebase.database_url'))
            ->createDatabase();
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * Obtener todos los contenedores
     */
    public function getContenedores()
    {
        try {
            $reference = $this->database->getReference('Contenedores');
            $snapshot = $reference->getSnapshot();

            if ($snapshot->exists()) {
                $value = $snapshot->getValue();
                Log::info('Contenedores obtenidos:', $value); // <--- añade esta línea para depurar
                return $value;
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Error getting contenedores: ' . $e->getMessage());
            return [];
        }
    }


    /**
     * Obtener un contenedor específico
     */
    public function getContenedor($contenedorId)
    {
        try {
            $reference = $this->database->getReference('Contenedores/' . $contenedorId);
            $snapshot = $reference->getSnapshot();
            
            if ($snapshot->exists()) {
                $data = $snapshot->getValue();
                return $this->processContenedorData($contenedorId, $data);
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error getting contenedor ' . $contenedorId . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los contenedores procesados
     */
    public function getAllContenedoresProcesados()
    {
        try {
            $contenedores = $this->getContenedores();
            $procesados = [];

            foreach ($contenedores as $id => $data) {
                if (is_array($data) && isset($data['Nombre'])) {
                    $procesados[$id] = $this->processContenedorData($id, $data);
                } else {
                    Log::warning("Contenedor ignorado: $id no es válido o no tiene nombre.");
                }
            }

            return $procesados;
        } catch (\Exception $e) {
            Log::error('Error processing contenedores: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Procesar datos del contenedor
     */
    private function processContenedorData($id, $data)
    {
        $nombre = $data['Nombre'] ?? $id;
        $porcentaje = intval($data['Porcentaje'] ?? 0);
        $estado = $data['Estado'] ?? false;
        $idContenedor = intval($data['Id'] ?? 0);
        
        // Determinar ubicación
        $ubicacion = $this->determinarUbicacion($idContenedor, $id);
        
        // Calcular estado y color
        $estadoInfo = $this->calcularEstadoYColor($porcentaje);
        
        return [
            'id' => $id,
            'nombre' => $nombre,
            'porcentaje' => $porcentaje,
            'estado_raw' => $estado,
            'idContenedor' => $idContenedor,
            'ubicacion' => $ubicacion,
            'estado' => $estadoInfo['estado'],
            'color' => $estadoInfo['color'],
            'color_hex' => $estadoInfo['color_hex'],
            'icon' => $estadoInfo['icon'],
            'status_icon' => $estadoInfo['status_icon'],
            'bg_gradient' => $estadoInfo['bg_gradient'],
            'updated_at' => now()->format('H:i:s')
        ];
    }

    /**
     * Determinar ubicación basada en ID
     */
    private function determinarUbicacion($idContenedor, $contenedorId)
    {
        switch ($idContenedor) {
            case 1:
                return 'Zona A';
            case 2:
                return 'Zona B';
            case 3:
                return 'Zona C';
            case 4:
                return 'Zona D';
            default:
                if (stripos($contenedorId, 'A') !== false) {
                    return 'Zona A';
                } elseif (stripos($contenedorId, 'B') !== false) {
                    return 'Zona B';
                } elseif (stripos($contenedorId, 'C') !== false) {
                    return 'Zona C';
                } elseif (stripos($contenedorId, 'D') !== false) {
                    return 'Zona D';
                } else {
                    return 'Zona General';
                }
        }
    }

    /**
     * Calcular estado y color basado en porcentaje
     */
    private function calcularEstadoYColor($porcentaje)
    {
        if ($porcentaje < 30) {
            return [
                'estado' => 'Tacho vacío',
                'color' => 'green',
                'color_hex' => '#4CAF50',
                'icon' => 'trash',
                'status_icon' => 'check-circle',
                'bg_gradient' => ['#E8F5E8', '#FFFFFF']
            ];
        } elseif ($porcentaje < 70) {
            return [
                'estado' => 'Tacho a medio llenar',
                'color' => 'blue',
                'color_hex' => '#2196F3',
                'icon' => 'trash2',
                'status_icon' => 'info',
                'bg_gradient' => ['#E3F2FD', '#FFFFFF']
            ];
        } elseif ($porcentaje < 95) {
            return [
                'estado' => 'Tacho casi lleno',
                'color' => 'orange',
                'color_hex' => '#FF9800',
                'icon' => 'trash2',
                'status_icon' => 'alert-triangle',
                'bg_gradient' => ['#FFF8E1', '#FFFFFF']
            ];
        } else {
            return [
                'estado' => 'Tacho lleno',
                'color' => 'red',
                'color_hex' => '#F44336',
                'icon' => 'trash2',
                'status_icon' => 'alert-circle',
                'bg_gradient' => ['#FFEBEE', '#FFFFFF']
            ];
        }
    }

    /**
     * Escuchar cambios en tiempo real (para WebSockets si se implementa)
     */
    public function listenToChanges($callback)
    {
        try {
            $reference = $this->database->getReference('Contenedores');
            // Implementar listener si es necesario
        } catch (\Exception $e) {
            Log::error('Error setting up listener: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas generales
     */
    public function getEstadisticas()
    {
        try {
            $contenedores = $this->getAllContenedoresProcesados();
            
            $total = count($contenedores);
            $llenos = 0;
            $medios = 0;
            $vacios = 0;
            $promedioLlenado = 0;

            foreach ($contenedores as $contenedor) {
                $porcentaje = $contenedor['porcentaje'];
                $promedioLlenado += $porcentaje;

                if ($porcentaje >= 95) {
                    $llenos++;
                } elseif ($porcentaje >= 30) {
                    $medios++;
                } else {
                    $vacios++;
                }
            }

            $promedioLlenado = $total > 0 ? round($promedioLlenado / $total, 2) : 0;

            return [
                'total' => $total,
                'llenos' => $llenos,
                'medios' => $medios,
                'vacios' => $vacios,
                'promedio_llenado' => $promedioLlenado,
                'necesitan_atencion' => $llenos,
                'estado_general' => $this->getEstadoGeneral($llenos, $total)
            ];
        } catch (\Exception $e) {
            Log::error('Error getting estadisticas: ' . $e->getMessage());
            return [
                'total' => 0,
                'llenos' => 0,
                'medios' => 0,
                'vacios' => 0,
                'promedio_llenado' => 0,
                'necesitan_atencion' => 0,
                'estado_general' => 'Error'
            ];
        }
    }

    /**
     * Determinar estado general del sistema
     */
    private function getEstadoGeneral($llenos, $total)
    {
        if ($total === 0) return 'Sin datos';
        
        $porcentajeLlenos = ($llenos / $total) * 100;
        
        if ($porcentajeLlenos >= 50) {
            return 'Crítico';
        } elseif ($porcentajeLlenos >= 25) {
            return 'Atención';
        } else {
            return 'Normal';
        }
    }
}