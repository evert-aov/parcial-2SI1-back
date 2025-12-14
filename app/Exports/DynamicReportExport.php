<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DynamicReportExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $config;
    protected $tablasPermitidas = [
        'usuarios',
        'docentes',
        'materias',
        'asignaciones',
        'aulas',
        'reservas',
        'carreras',
        'grupos',
        'gestiones',
        'horarios',
        'dias'
    ];
    protected $operadoresPermitidos = ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 'IN'];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function collection()
    {
        $tabla = $this->config['tabla'] ?? null;
        $campos = $this->config['campos'] ?? ['*'];
        $filtros = $this->config['filtros'] ?? [];
        $orden = $this->config['orden'] ?? null;
        $limite = $this->config['limite'] ?? 1000;

        // Validar tabla
        if (!in_array($tabla, $this->tablasPermitidas)) {
            return collect([]);
        }

        // Construir query
        $query = DB::table($tabla)->select($campos);

        // Aplicar filtros
        foreach ($filtros as $filtro) {
            if (!isset($filtro['campo'], $filtro['operador'], $filtro['valor'])) {
                continue;
            }

            $campo = $filtro['campo'];
            $operador = $filtro['operador'];
            $valor = $filtro['valor'];

            if (!in_array($operador, $this->operadoresPermitidos)) {
                continue;
            }

            if ($operador === 'IN') {
                $query->whereIn($campo, is_array($valor) ? $valor : explode(',', $valor));
            } elseif ($operador === 'LIKE' || $operador === 'NOT LIKE') {
                $query->where($campo, $operador, "%{$valor}%");
            } else {
                $query->where($campo, $operador, $valor);
            }
        }

        // Aplicar ordenamiento
        if ($orden && isset($orden['campo'], $orden['direccion'])) {
            $query->orderBy($orden['campo'], $orden['direccion']);
        }

        // Aplicar límite
        $query->limit($limite);

        return $query->get();
    }

    public function headings(): array
    {
        $campos = $this->config['campos'] ?? [];
        return array_map(function ($campo) {
            return ucfirst(str_replace('_', ' ', $campo));
        }, $campos);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        $tabla = $this->config['tabla'] ?? 'Reporte';
        return ucfirst($tabla);
    }
}
