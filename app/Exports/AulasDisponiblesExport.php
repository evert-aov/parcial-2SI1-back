<?php

namespace App\Exports;

use App\Models\Aula;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AulasDisponiblesExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $fecha = $this->filters['fecha'] ?? now()->format('Y-m-d');
        $horarioId = $this->filters['horario_id'] ?? null;

        if (!$horarioId) {
            return collect([]);
        }

        $diaSemana = date('N', strtotime($fecha));

        // Aulas ocupadas
        $aulasOcupadas = DB::table('asignacion_horarios')
            ->join('dias', 'asignacion_horarios.dia_id', '=', 'dias.id')
            ->where('dias.orden', $diaSemana)
            ->where('asignacion_horarios.horario_id', $horarioId)
            ->pluck('asignacion_horarios.aula_id');

        // Aulas disponibles
        return Aula::whereNotIn('id', $aulasOcupadas)
            ->where('activo', true)
            ->select('codigo', 'nombre', 'capacidad', 'tipo', 'edificio')
            ->orderBy('codigo')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Código',
            'Nombre',
            'Capacidad',
            'Tipo',
            'Edificio'
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
        return 'Aulas Disponibles';
    }
}
