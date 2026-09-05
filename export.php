<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode($_POST['json_data'], true);
    $format = $_POST['format'];
    
    // Filtros de qué incluir
    $inc_resumen = isset($_POST['inc_resumen']);
    $inc_top = isset($_POST['inc_top']);
    $inc_turnos = isset($_POST['inc_turnos']);

    if ($format == 'xml') {
        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="reporte_mensual.xml"');
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ReporteMensual/>');
        
        if ($inc_resumen) {
            $xml->addChild('EntregasTotales', $data['entregas_totales']);
        }
        
        if ($inc_top) {
            $clientes = $xml->addChild('TopClientes');
            foreach ($data['por_cliente_entregas'] as $nombre => $viajes) {
                $cliente = $clientes->addChild('Cliente');
                $cliente->addChild('Nombre', htmlspecialchars($nombre));
                $cliente->addChild('Viajes', $viajes);
            }
        }
        
        if ($inc_turnos) {
            $turnos = $xml->addChild('Turnos');
            foreach ($data['por_hoja'] as $hoja => $stats) {
                $turno = $turnos->addChild('Turno');
                $turno->addChild('Nombre', htmlspecialchars($hoja));
                $turno->addChild('Entregas', $stats['entregas_C']);
            }
        }
        
        echo $xml->asXML();
        exit;
        
    } elseif ($format == 'pdf') {
        // Configuramos Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // Permite cargar CSS externos si los tuvieras
        $dompdf = new Dompdf($options);
        
        // Construimos un HTML sencillo basado en lo que el usuario eligió exportar
        $html = '<h1 style="font-family: sans-serif;">Reporte Mensual de Monitoreo</h1>';
        
        if ($inc_resumen) {
            $html .= '<h2 style="font-family: sans-serif;">Entregas Totales: ' . $data['entregas_totales'] . '</h2>';
        }
        
        if ($inc_top) {
            $html .= '<h3 style="font-family: sans-serif;">Top Clientes</h3><ul style="font-family: sans-serif;">';
            $limit = 0;
            foreach ($data['por_cliente_entregas'] as $nombre => $viajes) {
                if($limit >= 5) break; // Solo mostrar top 5 en el PDF para limpieza
                $html .= "<li><strong>$nombre:</strong> $viajes viajes</li>";
                $limit++;
            }
            $html .= '</ul>';
        }

        // Cargamos el HTML en Dompdf
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Salida directa al navegador
        $dompdf->stream("Reporte_Monitoreo.pdf", array("Attachment" => false));
        exit;
    }
}
?>
