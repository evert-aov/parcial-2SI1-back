<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\AdministradorController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\DiaController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\AulaController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\ReportesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas de autenticación (sin middleware)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/validate-token', [AuthController::class, 'validateToken']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('jwt');
});

// Rutas públicas para crear docentes y administradores
Route::post('/docentes', [DocenteController::class, 'store']);
Route::post('/administradores', [AdministradorController::class, 'store']);

// Rutas protegidas con JWT
Route::middleware('jwt')->group(function () {

    // Ruta de prueba para verificar autenticación
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->get('auth_user')
        ]);
    });

    // Rutas para gestión de docentes
    Route::prefix('docentes')->group(function () {
        Route::get('/', [DocenteController::class, 'index']);
        Route::get('/mi-horario', [DocenteController::class, 'miCargaHoraria']);
        Route::get('/{id}', [DocenteController::class, 'show']);
        Route::post('/', [DocenteController::class, 'store']);
        Route::put('/{id}', [DocenteController::class, 'update']);
        Route::delete('/{id}', [DocenteController::class, 'destroy']);
    });

    // Rutas para gestión de administradores
    Route::prefix('administradores')->group(function () {
        Route::get('/', [AdministradorController::class, 'index']);
        Route::get('/{id}', [AdministradorController::class, 'show']);
        Route::put('/{id}', [AdministradorController::class, 'update']);
        Route::delete('/{id}', [AdministradorController::class, 'destroy']);
    });

    // Rutas para gestión de roles
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::post('/', [RoleController::class, 'store']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);

        // Gestión de permisos del rol
        Route::get('/{id}/permisos', [RoleController::class, 'getPermisos']);
        Route::post('/{id}/permisos', [RoleController::class, 'asignarPermiso']);
        Route::put('/{id}/permisos', [RoleController::class, 'sincronizarPermisos']);
        Route::delete('/{id}/permisos/{idPermiso}', [RoleController::class, 'removerPermiso']);
    });

    // Rutas para gestión de permisos
    Route::prefix('permisos')->group(function () {
        Route::get('/', [PermisoController::class, 'index']);
        Route::get('/{id}', [PermisoController::class, 'show']);
        Route::post('/', [PermisoController::class, 'store']);
        Route::put('/{id}', [PermisoController::class, 'update']);
        Route::delete('/{id}', [PermisoController::class, 'destroy']);
    });

    // Rutas para gestión de carreras
    Route::prefix('carreras')->group(function () {
        Route::get('/', [CarreraController::class, 'index']);
        Route::get('/{id}', [CarreraController::class, 'show']);
        Route::post('/', [CarreraController::class, 'store']);
        Route::put('/{id}', [CarreraController::class, 'update']);
        Route::delete('/{id}', [CarreraController::class, 'destroy']);
    });

    // Rutas para gestión de materias
    Route::prefix('materias')->group(function () {
        Route::get('/', [MateriaController::class, 'index']);
        Route::get('/{id}', [MateriaController::class, 'show']);
        Route::post('/', [MateriaController::class, 'store']);
        Route::put('/{id}', [MateriaController::class, 'update']);
        Route::delete('/{id}', [MateriaController::class, 'destroy']);
    });

    // Rutas para gestión de grupos
    Route::prefix('grupos')->group(function () {
        Route::get('/', [GrupoController::class, 'index']);
        Route::get('/{id}', [GrupoController::class, 'show']);
        Route::post('/', [GrupoController::class, 'store']);
        Route::put('/{id}', [GrupoController::class, 'update']);
        Route::delete('/{id}', [GrupoController::class, 'destroy']);
    });

    // Rutas para gestión de gestiones (periodos académicos)
    Route::prefix('gestiones')->group(function () {
        Route::get('/', [GestionController::class, 'index']);
        Route::get('/{id}', [GestionController::class, 'show']);
        Route::post('/', [GestionController::class, 'store']);
        Route::put('/{id}', [GestionController::class, 'update']);
        Route::delete('/{id}', [GestionController::class, 'destroy']);
    });

    // Rutas para gestión de días
    Route::prefix('dias')->group(function () {
        Route::get('/', [DiaController::class, 'index']);
        Route::get('/{id}', [DiaController::class, 'show']);
        Route::post('/', [DiaController::class, 'store']);
        Route::put('/{id}', [DiaController::class, 'update']);
        Route::delete('/{id}', [DiaController::class, 'destroy']);
    });

    // Rutas para gestión de horarios
    Route::prefix('horarios')->group(function () {
        Route::get('/', [HorarioController::class, 'index']);
        Route::get('/{id}', [HorarioController::class, 'show']);
        Route::post('/', [HorarioController::class, 'store']);
        Route::put('/{id}', [HorarioController::class, 'update']);
        Route::delete('/{id}', [HorarioController::class, 'destroy']);
    });

    // Rutas para gestión de aulas
    Route::prefix('aulas')->group(function () {
        Route::get('/', [AulaController::class, 'index']);
        Route::get('/{id}', [AulaController::class, 'show']);
        Route::post('/', [AulaController::class, 'store']);
        Route::put('/{id}', [AulaController::class, 'update']);
        Route::delete('/{id}', [AulaController::class, 'destroy']);
        Route::post('/check-availability', [AulaController::class, 'checkAvailability']);
    });

    // Asignaciones: asignar docente a materia/grupo/gestión con horarios
    Route::prefix('asignaciones')->group(function () {
        Route::get('/', [AsignacionController::class, 'index']);
        Route::get('/{id}', [AsignacionController::class, 'show']);
        Route::post('/', [AsignacionController::class, 'store']);
        Route::put('/{id}', [AsignacionController::class, 'update']);
        Route::delete('/{id}', [AsignacionController::class, 'destroy']);
    });

    // Reservas de aulas
    Route::prefix('reservas')->group(function () {
        Route::get('/', [ReservaController::class, 'index']);
        Route::get('/{id}', [ReservaController::class, 'show']);
        Route::post('/', [ReservaController::class, 'store']);
        Route::put('/{id}', [ReservaController::class, 'update']);
        Route::delete('/{id}', [ReservaController::class, 'destroy']);
        Route::post('/{id}/aprobar', [ReservaController::class, 'aprobar']);
        Route::post('/{id}/rechazar', [ReservaController::class, 'rechazar']);
        Route::post('/{id}/cancelar', [ReservaController::class, 'cancelar']);
    });

    // Rutas para reportes
    Route::prefix('reportes')->group(function () {
        Route::get('/dashboard-stats', [ReporteController::class, 'dashboardStats']);
        Route::get('/horarios-semanales', [ReporteController::class, 'horariosSemanales']);
        Route::get('/horarios-excel', [ReporteController::class, 'exportarHorariosExcel']);
        Route::get('/aulas-disponibles', [ReporteController::class, 'aulasDisponibles']);
        Route::get('/aulas-disponibles-excel', [ReporteController::class, 'exportarAulasDisponiblesExcel']);

        // Reportes dinámicos
        Route::get('/tablas-disponibles', [ReporteController::class, 'tablasDisponibles']);
        Route::post('/generar-dinamico', [ReporteController::class, 'generarReporteDinamico']);
        Route::post('/exportar-dinamico-excel', [ReporteController::class, 'exportarReporteDinamicoExcel']);
    });

    // Importación de usuarios
    Route::prefix('import')->group(function () {
        Route::post('/usuarios', [ImportController::class, 'importarUsuarios']);
        Route::get('/plantilla-usuarios', [ImportController::class, 'descargarPlantilla']);
    });

    // Sistema de asistencias
    Route::prefix('asistencias')->group(function () {
        Route::post('/generar-qr', [AsistenciaController::class, 'generarQR']);
        Route::post('/marcar', [AsistenciaController::class, 'marcarAsistencia']);
        Route::get('/mis-asistencias', [AsistenciaController::class, 'misAsistencias']);
        Route::get('/mis-qrs-hoy', [AsistenciaController::class, 'misQRsHoy']);
        Route::get('/reporte', [AsistenciaController::class, 'reporte']);

        // Ejecutar registro automático de faltas (manual)
        Route::post('/registrar-faltas-automaticas', function () {
            \Illuminate\Support\Facades\Artisan::call('asistencias:registrar-faltas');
            $output = \Illuminate\Support\Facades\Artisan::output();
            return response()->json([
                'message' => 'Comando ejecutado exitosamente',
                'output' => $output
            ]);
        });
    });

    // Reportes administrativos
    Route::prefix('reportes')->group(function () {
        Route::get('/horarios-semanales', [ReportesController::class, 'horariosSemanales']);
        Route::get('/asistencias-docente', [ReportesController::class, 'asistenciasPorDocente']);
        Route::get('/asistencias-grupo', [ReportesController::class, 'asistenciasPorGrupo']);
        Route::get('/aulas-disponibles', [ReportesController::class, 'aulasDisponibles']);
    });
});
