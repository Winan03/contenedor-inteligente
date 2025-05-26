<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;

class ContenedorController extends Controller
{
    public function obtenerContenedores()
    {
        try {
            $firebase = new FirebaseService();
            $contenedores = $firebase->getAllContenedoresProcesados();

            return response()->json([
                'success' => true,
                'data' => $contenedores
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los contenedores.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function obtenerContenedor($id)
    {
        try {
            $firebase = new FirebaseService();
            $contenedor = $firebase->getContenedor($id);

            return response()->json([
                'success' => true,
                'data' => $contenedor
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el contenedor.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function obtenerEstadisticas()
    {
        try {
            $firebase = new FirebaseService();
            $estadisticas = $firebase->getEstadisticas();

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
