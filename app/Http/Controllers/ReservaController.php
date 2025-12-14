<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Aula;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class ReservaController extends Controller
{
    /**
     * Listar todas las reservas
     */
    public function index(): JsonResponse
    {
        try {
            $reservas = Reserva::with(['aula', 'solicitante'])
                ->ordenadas()
                ->get()
                ->map(function ($reserva) {
                    return [
                        'idreserva' => $reserva->id,
                        'titulo' => $reserva->titulo,
                        'aula_id' => $reserva->aula_id,
                        'aula_codigo' => $reserva->aula->codigo,
                        'aula_nombre' => $reserva->aula->nombre,
                        'tipo' => $reserva->tipo,
                        'fecha' => $reserva->fecha->format('Y-m-d'),
                        'hora_inicio' => $reserva->hora_inicio->format('H:i'),
                        'hora_fin' => $reserva->hora_fin->format('H:i'),
                        'asistentes_estimados' => $reserva->asistentes_estimados,
                        'descripcion' => $reserva->descripcion,
                        'estado' => $reserva->estado,
                        'solicitante_id' => $reserva->solicitante_id,
                        'solicitante' => $reserva->solicitante ? $reserva->solicitante->nombre . ' ' . $reserva->solicitante->apellido : null,
                    ];
                });

            return response()->json($reservas);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener reservas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nueva reserva
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'titulo' => 'required|string',
                'aula_id' => 'required|exists:aulas,id',
                'tipo' => 'required|string',
                'fecha' => 'required|date',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                'asistentes_estimados' => 'nullable|integer|min:1',
                'descripcion' => 'nullable|string',
                'solicitante_id' => 'nullable|exists:usuarios,id',
            ]);

            // Verificar si el aula ya está reservada en ese horario
            $conflicto = Reserva::where('aula_id', $request->aula_id)
                ->where('fecha', $request->fecha)
                ->where('estado', '!=', 'cancelada')
                ->where('estado', '!=', 'rechazada')
                ->where(function ($query) use ($request) {
                    $query->whereBetween('hora_inicio', [$request->hora_inicio, $request->hora_fin])
                        ->orWhereBetween('hora_fin', [$request->hora_inicio, $request->hora_fin])
                        ->orWhere(function ($q) use ($request) {
                            $q->where('hora_inicio', '<=', $request->hora_inicio)
                                ->where('hora_fin', '>=', $request->hora_fin);
                        });
                })
                ->exists();

            if ($conflicto) {
                return response()->json([
                    'message' => 'El aula ya está reservada en ese horario'
                ], 422);
            }

            $reserva = Reserva::create([
                'titulo' => $request->titulo,
                'aula_id' => $request->aula_id,
                'tipo' => $request->tipo,
                'fecha' => $request->fecha,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'asistentes_estimados' => $request->asistentes_estimados,
                'descripcion' => $request->descripcion,
                'estado' => 'pendiente',
                'solicitante_id' => $request->solicitante_id,
            ]);

            return response()->json([
                'idreserva' => $reserva->id,
                'message' => 'Reserva creada correctamente',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al crear reserva: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener reserva específica
     */
    public function show($id): JsonResponse
    {
        try {
            $reserva = Reserva::with(['aula', 'solicitante'])->findOrFail($id);

            return response()->json([
                'idreserva' => $reserva->id,
                'titulo' => $reserva->titulo,
                'aula_id' => $reserva->aula_id,
                'aula_codigo' => $reserva->aula->codigo,
                'aula_nombre' => $reserva->aula->nombre,
                'tipo' => $reserva->tipo,
                'fecha' => $reserva->fecha->format('Y-m-d'),
                'hora_inicio' => $reserva->hora_inicio->format('H:i'),
                'hora_fin' => $reserva->hora_fin->format('H:i'),
                'asistentes_estimados' => $reserva->asistentes_estimados,
                'descripcion' => $reserva->descripcion,
                'estado' => $reserva->estado,
                'solicitante_id' => $reserva->solicitante_id,
                'solicitante' => $reserva->solicitante ? $reserva->solicitante->nombre . ' ' . $reserva->solicitante->apellido : null,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Reserva no encontrada'], 404);
        }
    }

    /**
     * Actualizar reserva
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $reserva = Reserva::findOrFail($id);

            $request->validate([
                'titulo' => 'sometimes|string',
                'aula_id' => 'sometimes|exists:aulas,id',
                'tipo' => 'sometimes|string',
                'fecha' => 'sometimes|date',
                'hora_inicio' => 'sometimes|date_format:H:i',
                'hora_fin' => 'sometimes|date_format:H:i|after:hora_inicio',
                'asistentes_estimados' => 'nullable|integer|min:1',
                'descripcion' => 'nullable|string',
                'estado' => 'sometimes|in:pendiente,aprobada,rechazada,cancelada',
            ]);

            // Si se actualizan aula, fecha u horarios, verificar conflictos
            if ($request->has('aula_id') || $request->has('fecha') || $request->has('hora_inicio') || $request->has('hora_fin')) {
                $aulaId = $request->aula_id ?? $reserva->aula_id;
                $fecha = $request->fecha ?? $reserva->fecha->format('Y-m-d');
                $horaInicio = $request->hora_inicio ?? $reserva->hora_inicio->format('H:i');
                $horaFin = $request->hora_fin ?? $reserva->hora_fin->format('H:i');

                $conflicto = Reserva::where('aula_id', $aulaId)
                    ->where('fecha', $fecha)
                    ->where('id', '!=', $reserva->id)
                    ->where('estado', '!=', 'cancelada')
                    ->where('estado', '!=', 'rechazada')
                    ->where(function ($query) use ($horaInicio, $horaFin) {
                        $query->whereBetween('hora_inicio', [$horaInicio, $horaFin])
                            ->orWhereBetween('hora_fin', [$horaInicio, $horaFin])
                            ->orWhere(function ($q) use ($horaInicio, $horaFin) {
                                $q->where('hora_inicio', '<=', $horaInicio)
                                    ->where('hora_fin', '>=', $horaFin);
                            });
                    })
                    ->exists();

                if ($conflicto) {
                    return response()->json([
                        'message' => 'El aula ya está reservada en ese horario'
                    ], 422);
                }
            }

            $reserva->update($request->only([
                'titulo',
                'aula_id',
                'tipo',
                'fecha',
                'hora_inicio',
                'hora_fin',
                'asistentes_estimados',
                'descripcion',
                'estado'
            ]));

            return response()->json(['message' => 'Reserva actualizada correctamente']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al actualizar reserva: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar reserva
     */
    public function destroy($id): JsonResponse
    {
        try {
            $reserva = Reserva::findOrFail($id);
            $reserva->delete();

            return response()->json(['message' => 'Reserva eliminada correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar reserva: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Aprobar reserva
     */
    public function aprobar($id): JsonResponse
    {
        try {
            $reserva = Reserva::findOrFail($id);
            $reserva->update(['estado' => 'aprobada']);

            return response()->json(['message' => 'Reserva aprobada correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al aprobar reserva: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Rechazar reserva
     */
    public function rechazar($id): JsonResponse
    {
        try {
            $reserva = Reserva::findOrFail($id);
            $reserva->update(['estado' => 'rechazada']);

            return response()->json(['message' => 'Reserva rechazada correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al rechazar reserva: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancelar reserva
     */
    public function cancelar($id): JsonResponse
    {
        try {
            $reserva = Reserva::findOrFail($id);
            $reserva->update(['estado' => 'cancelada']);

            return response()->json(['message' => 'Reserva cancelada correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al cancelar reserva: ' . $e->getMessage()], 500);
        }
    }
}
