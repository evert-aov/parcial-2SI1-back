<?php

namespace App\Imports;

use App\Models\Usuario;
use App\Models\Docente;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class UsuariosImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    protected $importados = 0;
    protected $errores = [];

    public function model(array $row)
    {
        try {
            // Convertir valores numéricos a string
            $ci = (string) $row['ci'];
            $telefono = isset($row['telefono']) ? (string) $row['telefono'] : null;

            // Buscar el rol de Docente
            $rolDocente = Role::where('nombre', 'Docente')->first();

            if (!$rolDocente) {
                $this->errores[] = "Rol 'Docente' no encontrado en el sistema";
                return null;
            }

            // Verificar si el usuario ya existe por CI o correo
            $existeCI = Usuario::where('ci', $ci)->first();
            $existeCorreo = Usuario::where('correo', $row['correo'])->first();

            if ($existeCI) {
                $this->errores[] = "Usuario con CI {$ci} ya existe";
                return null;
            }

            if ($existeCorreo) {
                $this->errores[] = "Usuario con correo {$row['correo']} ya existe";
                return null;
            }

            DB::beginTransaction();

            // Crear usuario
            $usuario = Usuario::create([
                'rol_id' => $rolDocente->id,
                'nombre' => $row['nombre'],
                'apellido' => $row['apellido'],
                'correo' => $row['correo'],
                'ci' => $ci,
                'telefono' => $telefono,
                'sexo' => $row['sexo'] ?? null,
                'direccion' => $row['direccion'] ?? null,
                'contrasena' => Hash::make($ci), // Password = CI
                'activo' => true
            ]);

            // Crear registro en tabla docentes
            // Parsear fecha de contratación con manejo de múltiples formatos
            $fechaContrato = now()->format('Y-m-d'); // Valor por defecto
            if (isset($row['fecha_contratacion']) && !empty($row['fecha_contratacion'])) {
                try {
                    // Intentar parsear la fecha con Carbon (soporta múltiples formatos)
                    $fechaContrato = \Carbon\Carbon::parse($row['fecha_contratacion'])->format('Y-m-d');
                } catch (\Exception $e) {
                    // Si falla el parseo, usar fecha actual
                    $fechaContrato = now()->format('Y-m-d');
                }
            }

            Docente::create([
                'usuario_id' => $usuario->id,
                'especialidad' => $row['especialidad'] ?? 'General',
                'grado_academico' => $row['grado_academico'] ?? 'Licenciatura',
                'fecha_contrato' => $fechaContrato,
            ]);

            DB::commit();
            $this->importados++;

            return $usuario;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errores[] = "Error al importar {$row['nombre']} {$row['apellido']}: " . $e->getMessage();
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'correo' => 'required|email|unique:usuarios,correo',
            'ci' => 'required', // Aceptar cualquier tipo
            'telefono' => 'nullable', // Aceptar cualquier tipo
            'sexo' => 'nullable|in:M,F,Masculino,Femenino',
            'direccion' => 'nullable|string',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'apellido.required' => 'El apellido es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'correo.email' => 'El correo debe ser válido',
            'correo.unique' => 'El correo ya está registrado',
            'ci.required' => 'El CI es obligatorio',
            'ci.unique' => 'El CI ya está registrado',
        ];
    }

    public function onError(Throwable $e)
    {
        $this->errores[] = $e->getMessage();
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errores[] = "Fila {$failure->row()}: " . implode(', ', $failure->errors());
        }
    }

    public function getImportados()
    {
        return $this->importados;
    }

    public function getErrores()
    {
        return $this->errores;
    }
}
