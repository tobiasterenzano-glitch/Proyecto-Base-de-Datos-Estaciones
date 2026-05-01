<?php
// --- BLOQUE 1: LÓGICA DE DATOS (PHP) ---
$host = 'localhost'; $db = 'estacion'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $sqlMapa = "SELECT e.nombre, e.latitud as lat, e.longitud as lng, m.valor FROM estacion e 
                JOIN sensor s ON e.id = s.estacion_id JOIN medicion m ON s.id = m.sensor_id
                WHERE s.tipo = 'Potencia' AND m.id IN (SELECT MAX(id) FROM medicion GROUP BY sensor_id)";
    $estaciones = $pdo->query($sqlMapa)->fetchAll();
    $sqlSemanal = "SELECT m.fecha, SUM(m.valor) as total FROM medicion m JOIN sensor s ON m.sensor_id = s.id 
                   WHERE s.tipo = 'Potencia' GROUP BY m.fecha ORDER BY m.fecha ASC LIMIT 7";
    $resSemanal = $pdo->query($sqlSemanal)->fetchAll();
    $fechasJS = []; $valoresJS = [];
    foreach($resSemanal as $r) { 
        $fechasJS[] = date('d/m', strtotime($r['fecha'])); 
        $valoresJS[] = $r['total']; 
    }
    $qV = "FROM medicion m JOIN sensor s ON m.sensor_id = s.id JOIN estacion e ON s.estacion_id = e.id WHERE e.nombre LIKE '%Villa Ballester%'";
    $promCorriente = $pdo->query("SELECT AVG(valor) as v $qV AND s.tipo = 'Corriente' AND m.fecha = CURDATE()")->fetch()['v'] ?? 0;
    $maxPotencia = $pdo->query("SELECT MAX(valor) as v $qV AND s.tipo = 'Potencia' AND m.fecha = CURDATE()")->fetch()['v'] ?? 0;
    $minTension = $pdo->query("SELECT MIN(valor) as v $qV AND s.tipo = 'Tensión' AND m.fecha BETWEEN '2026-04-01' AND '2026-04-30'")->fetch()['v'] ?? 0;
} catch (PDOException $e) { die(); }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Técnico | AHK GIO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- --- BLOQUE 2: ESTILOS (CSS) --- -->
    <style>
        body { background-color: #0b1120; color: #f8fafc; font-family: 'Inter', sans-serif; }
        .kpi-card { background: #111827; border: 1px solid #1f2937; border-radius: 12px; padding: 20px; display: flex; align-items: center; }
        .kpi-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .kpi-label { font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
        .chart-box { background: #111827; border: 1px solid #1f2937; border-radius: 12px; padding: 20px; }
        #map { height: 540px; border-radius: 12px; border: 1px solid #1f2937; }
        .plant-row { background: #1e293b; border-radius: 6px; margin-bottom: 8px; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; }
        .plant-val { background: rgba(59, 130, 246, 0.2); color: #60a5fa; font-size: 0.75rem; padding: 2px 10px; border-radius: 4px; border: 1px solid rgba(59, 130, 246, 0.3); }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>

<body class="p-4">

    <!-- --- BLOQUE 3: ESTRUCTURA (HTML) --- -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-white"><i class="bi bi-lightning-charge-fill text-warning"></i> BASE DE DATOS ESTACIONES AHK GIO</h3>
            <small class="text-info opacity-75">Panel de Monitoreo de Media Tensión</small>
        </div>
        <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal"><i class="bi bi-file-earmark-pdf me-1"></i> Exportar Reporte</button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="kpi-card shadow-sm"><div class="kpi-icon"><i class="bi bi-activity fs-4"></i></div><div><div class="kpi-label">Corriente V. Ballester (Prom)</div><h3 class="mb-0 fw-bold"><?= number_format($promCorriente, 2) ?> A</h3></div></div></div>
        <div class="col-md-4"><div class="kpi-card shadow-sm"><div class="kpi-icon text-success" style="background:rgba(16,185,129,0.1)"><i class="bi bi-lightning-charge fs-4"></i></div><div><div class="kpi-label">Potencia Máx. Hoy V. Ballester</div><h3 class="mb-0 fw-bold"><?= number_format($maxPotencia, 1) ?> MW</h3></div></div></div>
        <div class="col-md-4"><div class="kpi-card shadow-sm"><div class="kpi-icon text-danger" style="background:rgba(239,68,68,0.1)"><i class="bi bi-thermometer-half fs-4"></i></div><div><div class="kpi-label">Tensión Mínima Ballester (Abril)</div><h3 class="mb-0 fw-bold"><?= number_format($minTension, 1) ?> kV</h3></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8"><div id="map"></div></div>
        <div class="col-lg-4">
            <div class="chart-box mb-3"><h6 class="kpi-label mb-3">Generación por Central</h6><div style="height: 200px;"><canvas id="donaEstaciones"></canvas></div></div>
            <div class="chart-box">
                <h6 class="kpi-label mb-3">LISTADO DE GENERACION ACTUAL</h6>
                <div id="tablaGeneracion">
                    <?php foreach($estaciones as $e): ?><div class="plant-row"><span class="small fw-semibold text-white"><?= $e['nombre'] ?></span><span class="plant-val"><?= number_format($e['valor'], 0) ?> MW</span></div><?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-12 mt-3"><div class="chart-box"><h6 class="kpi-label mb-4">Evolución Semanal de Demanda</h6><div style="height: 280px;"><canvas id="lineaSemanal"></canvas></div></div></div>
    </div>

    <!-- MODAL -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark border-secondary shadow-lg">
                <div class="modal-header border-secondary text-white"><h5 class="modal-title">Generar Reporte</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-white">
                    <p class="small text-muted">Se generará un PDF técnico que incluye los gráficos de dona, evolución de demanda y tabla de datos.</p>
                    <button onclick="exportarAPDF()" class="btn btn-info w-100 fw-bold text-dark">DESCARGAR REPORTE COMPLETO</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS EXTERNOS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- --- BLOQUE 4: COMPORTAMIENTO (JAVASCRIPT) --- -->
    <script>
        const estaciones = <?= json_encode($estaciones) ?>;

        function exportarAPDF() {
            const imgDona = document.getElementById('donaEstaciones').toDataURL('image/png');
            const imgLinea = document.getElementById('lineaSemanal').toDataURL('image/png');
            const v = window.open('', '', 'height=800,width=1000');
            let filas = estaciones.map(e => `<tr><td style="padding: 8px; border-bottom: 1px solid #eee;">${e.nombre}</td><td style="padding: 8px; border-bottom: 1px solid #eee; text-align: right;">${Number(e.valor).toLocaleString()} MW</td></tr>`).join('');
            
            v.document.write(`
                <html><head><title>Reporte Técnico</title><style>
                body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 30px; color: #222; }
                .header { border-bottom: 3px solid #3b82f6; padding-bottom: 10px; margin-bottom: 30px; }
                .grid { display: flex; gap: 20px; margin-bottom: 30px; }
                .col { flex: 1; border: 1px solid #eee; padding: 15px; border-radius: 8px; }
                table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
                th { background: #f8fafc; text-align: left; padding: 10px; border-bottom: 2px solid #3b82f6; }
                img { max-width: 100%; height: auto; display: block; margin: 0 auto; }
                h4 { margin-top: 0; color: #3b82f6; border-bottom: 1px solid #eee; padding-bottom: 5px; }
                </style></head>
                <body>
                    <div class="header"><h1>Reporte técnico | Reporte técnico de Generación</h1><p>Fecha de emisión: ${new Date().toLocaleDateString()} - ${new Date().toLocaleTimeString()}</p></div>
                    <div class="grid"><div class="col"><h4>Distribución por Central</h4><img src="${imgDona}"></div><div class="col"><h4>Datos de Generación Actual</h4><table><thead><tr><th>Planta</th><th style="text-align:right;">Potencia</th></tr></thead><tbody>${filas}</tbody></table></div></div>
                    <div style="border: 1px solid #eee; padding: 15px; border-radius: 8px;"><h4>Evolución Semanal de Demanda</h4><img src="${imgLinea}" style="max-height: 300px;"></div>
                </body></html>
            `);
            v.document.close();
            setTimeout(() => { v.print(); }, 500);
        }

        new Chart(document.getElementById('donaEstaciones'), {
            type: 'doughnut',
            data: {
                labels: estaciones.map(e => e.nombre),
                datasets: [{ data: estaciones.map(e => e.valor), backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'], borderWidth: 0, cutout: '75%' }]
            },
            options: { animation: false, plugins: { legend: { position: 'right', labels: { color: '#94a3b8', font: { size: 10 }, usePointStyle: true } } }, maintainAspectRatio: false }
        });

        new Chart(document.getElementById('lineaSemanal'), {
            type: 'line',
            data: {
                labels: <?= json_encode($fechasJS) ?>,
                datasets: [{ data: <?= json_encode($valoresJS) ?>, borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', fill: true, tension: 0.4, pointRadius: 5 }]
            },
            options: { animation: false, plugins: { legend: { display: false } }, scales: { y: { grid: { color: '#1f2937' }, ticks: { color: '#64748b' } }, x: { grid: { display: false }, ticks: { color: '#64748b' } } }, maintainAspectRatio: false }
        });

        const map = L.map('map').setView([-34.6, -58.5], 11);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(map);
        estaciones.forEach(e => L.marker([e.lat, e.lng]).addTo(map).bindPopup(`<b>${e.nombre}</b><br>${e.valor} MW`));
    </script>
</body>
</html>