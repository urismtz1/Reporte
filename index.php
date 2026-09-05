<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$dataProcessed = false;
$resumen = [
    "entregas_totales" => 0,
    "por_cliente_entregas" => [],
    "por_flota" => ["Bisonte" => ["cargadas" => 0, "entregas" => 0, "fuera_operacion" => 0], "Manzanillo" => ["cargadas" => 0, "entregas" => 0, "fuera_operacion" => 0]],
    "por_hoja" => [],
    "fuera_operacion_detalle" => []
];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $filePath = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = IOFactory::load($filePath);
    
    foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
        $sheetTitle = $worksheet->getTitle();
        $resumen['por_hoja'][$sheetTitle] = ["cargadas_mov" => 0, "cargadas_resg" => 0, "entregas_C" => 0, "fuera_op" => 0];
        
        // Empezamos a leer desde la fila 8 (donde empiezan los datos en tu formato)
        foreach ($worksheet->getRowIterator(8) as $row) {
            $rowIndex = $row->getRowIndex();
            $unidad = $worksheet->getCell('B' . $rowIndex)->getValue();
            $clienteOrigen = $worksheet->getCell('D' . $rowIndex)->getValue(); // Columna de Cliente Origen
            $observaciones = $worksheet->getCell('AS' . $rowIndex)->getValue(); // Asumiendo AS como observaciones finales
            
            if (empty($unidad)) continue;

            // 1. Verificar si hubo inicio de viaje buscando la "I" en las columnas de rastreo (L a AR aprox)
            $viajeIniciado = false;
            for ($col = 'L'; $col !== 'AS'; $col++) {
                $celdaRastreo = $worksheet->getCell($col . $rowIndex)->getValue();
                if (trim((string)$celdaRastreo) === 'I') {
                    $viajeIniciado = true;
                    break;
                }
            }

            // 2. Sumar al resumen si se inició viaje
            if ($viajeIniciado && !empty($clienteOrigen)) {
                $resumen['entregas_totales']++;
                $resumen['por_hoja'][$sheetTitle]['entregas_C']++; // Asumiendo que inició y entregó en el mes
                
                if (!isset($resumen['por_cliente_entregas'][$clienteOrigen])) {
                    $resumen['por_cliente_entregas'][$clienteOrigen] = 0;
                }
                $resumen['por_cliente_entregas'][$clienteOrigen]++;
            }

            // (Aquí puedes agregar la lógica para detectar si están fuera de operación leyendo la celda de estatus, ej: "X")
        }
    }
    
    // Ordenar los clientes de mayor a menor para el top
    arsort($resumen['por_cliente_entregas']);
    $dataProcessed = true;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TRACKER 3 IVG — Resumen Mensual</title>
    <!-- AQUÍ PEGAS EXACTAMENTE TODO EL BLOQUE <style> QUE ME COMPARTISTE -->
    <style>
        /* ... Tu CSS original va aquí ... */
        /* Estilos extra para el panel de control */
        .control-panel { background: var(--asphalt-800); padding: 20px; border-bottom: 1px solid var(--line-grey); text-align: center; }
        .btn { background: var(--signal-green); color: #000; padding: 10px 20px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-family: var(--mono); margin: 0 10px; }
        .btn-pdf { background: var(--signal-red); color: #fff; }
        .btn-xml { background: var(--amber); color: #000; }
        .checkbox-group { margin-top: 15px; font-size: 13px; }
        .checkbox-group label { margin-right: 15px; cursor: pointer; }
    </style>
</head>
<body>

<?php if (!$dataProcessed): ?>
    <div class="hero">
        <div class="hero-inner">
            <h1>Cargar Archivo Excel de Monitoreo</h1>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="file" name="excel_file" accept=".xlsx, .xls" required style="margin-bottom: 20px; color: white;">
                <br>
                <button type="submit" class="btn">Procesar Resumen</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Panel de Exportación que pides -->
    <div class="control-panel">
        <form action="export.php" method="post" target="_blank">
            <input type="hidden" name="json_data" value='<?php echo json_encode($resumen); ?>'>
            
            <div class="checkbox-group">
                <strong>¿Qué incluir en la exportación?:</strong>
                <label><input type="checkbox" name="inc_resumen" checked> Resumen General</label>
                <label><input type="checkbox" name="inc_top" checked> Top Clientes</label>
                <label><input type="checkbox" name="inc_turnos" checked> Detalles por Turno</label>
            </div>
            <br>
            <button type="submit" name="format" value="pdf" class="btn btn-pdf">Exportar PDF</button>
            <button type="submit" name="format" value="xml" class="btn btn-xml">Exportar XML</button>
        </form>
    </div>

    <!-- AQUÍ PEGAS EXACTAMENTE TU HTML (div.hero, main, section, footer) QUE ME COMPARTISTE -->
    <!-- ... Tu HTML original va aquí ... -->

    <script>
        // Insertamos los datos dinámicos generados por PHP
        const DATA = <?php echo json_encode($resumen); ?>;

        // AQUÍ PEGAS EXACTAMENTE TU SCRIPT DE ANIMACIÓN Y DIBUJADO DE DOM
        // ... Tu JS original va aquí ...
    </script>
<?php endif; ?>
</body>
</html>
