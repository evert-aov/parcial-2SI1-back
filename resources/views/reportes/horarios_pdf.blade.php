<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horarios Semanales</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #4a5568;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Horarios Semanales</h1>
        <p>Sistema de Gestión Académica</p>
        <p>Generado el: {{ $fecha }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Día</th>
                <th>Horario</th>
                <th>Materia</th>
                <th>Docente</th>
                <th>Grupo</th>
                <th>Aula</th>
                <th>Carrera</th>
            </tr>
        </thead>
        <tbody>
            @foreach($horarios as $horario)
            <tr>
                <td>{{ $horario->dia }}</td>
                <td>{{ $horario->hora_inicio }} - {{ $horario->hora_fin }}</td>
                <td>{{ $horario->sigla }} - {{ $horario->materia }}</td>
                <td>{{ $horario->docente }}</td>
                <td>{{ $horario->grupo }}</td>
                <td>{{ $horario->aula }}</td>
                <td>{{ $horario->carrera }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Total de registros: {{ count($horarios) }}</p>
    </div>
</body>
</html>
