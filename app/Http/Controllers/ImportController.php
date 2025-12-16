<?php

namespace App\Http\Controllers;

use App\Imports\UsuariosImport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ImportController extends Controller
{
    /**
     * Importar usuarios desde Excel
     */
    public function importarUsuarios(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'archivo' => 'required|file|mimes:xlsx,xls,csv|max:2048'
            ]);

            $archivo = $request->file('archivo');

            // Crear instancia del importador
            $import = new UsuariosImport();

            // Ejecutar importación
            Excel::import($import, $archivo);

            $importados = $import->getImportados();
            $errores = $import->getErrores();

            return response()->json([
                'message' => "Importación completada",
                'importados' => $importados,
                'errores' => $errores,
                'total_errores' => count($errores)
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al importar usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar plantilla de Excel
     */
    public function descargarPlantilla(): JsonResponse
    {
        try {
            $plantilla = [
                ['nombre', 'apellido', 'correo', 'ci', 'telefono', 'sexo', 'direccion', 'especialidad', 'grado_academico', 'fecha_contratacion'],
                ['Juan', 'Pérez', 'juan.perez@example.com', '12345678', '70123456', 'M', 'Av. Ejemplo #123', 'Matemáticas', 'Licenciatura', '2024-01-15'],
                ['María', 'González', 'maria.gonzalez@example.com', '87654321', '71234567', 'F', 'Calle Principal #456', 'Física', 'Maestría', '2024-02-20'],
            ];

            return response()->json([
                'message' => 'Plantilla generada',
                'plantilla' => $plantilla,
                'instrucciones' => [
                    'Campos obligatorios: nombre, apellido, correo, ci',
                    'Campos opcionales: telefono, sexo, direccion, especialidad, grado_academico, fecha_contratacion',
                    'El CI debe ser único',
                    'El correo debe ser único y válido',
                    'El password será automáticamente el número de CI',
                    'El rol asignado será automáticamente "Docente"',
                    'Sexo: M o F (Masculino o Femenino)',
                    'Fecha de contratación: YYYY-MM-DD (ej: 2024-01-15). Si no se proporciona o tiene formato inválido, se usará la fecha actual'
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al generar plantilla',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
