<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HorariosExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = DB::table('asignacion_horarios')
            ->join('asignaciones', 'asignacion_horarios.asignacion_id', '=', 'asignaciones.id')
            ->join('dias', 'asignacion_horarios.dia_id', '=', 'dias.id')
            ->join('horarios', 'asignacion_horarios.horario_id', '=', 'horarios.id')
            ->join('aulas', 'asignacion_horarios.aula_id', '=', 'aulas.id')
            ->join('materias', 'asignaciones.materia_id', '=', 'materias.id')
            ->join('grupos', 'asignaciones.grupo_id', '=', 'grupos.id')
            ->join('docentes', 'asignaciones.docente_id', '=', 'docentes.id')
            ->join('usuarios', 'docentes.usuario_id', '=', 'usuarios.id')
            ->join('carreras', 'materias.carrera_id', '=', 'carreras.id')
            ->select(
                'dias.nombre as dia',
                'horarios.hora_inicio',
                'horarios.hora_fin',
                'materias.sigla',
                'materias.nombre as materia',
                DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) as docente"),
                'grupos.nombre as grupo',
                'aulas.codigo as aula',
                'carreras.nombre as carrera'
            )
            ->orderBy('dias.orden')
            ->orderBy('horarios.hora_inicio');

        // Aplicar filtros
        if (isset($this->filters['carrera_id'])) {
            $query->where('materias.carrera_id', $this->filters['carrera_id']);
        }
        if (isset($this->filters['grupo_id'])) {
            $query->where('asignaciones.grupo_id', $this->filters['grupo_id']);
        }
        if (isset($this->filters['docente_id'])) {
            $query->where('asignaciones.docente_id', $this->filters['docente_id']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Día',
            'Hora Inicio',
            'Hora Fin',
            'Sigla',
            'Materia',
            'Docente',
            'Grupo',
            'Aula',
            'Carrera'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        return 'Horarios Semanales';
    }
}
