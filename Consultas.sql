-- 1. Listado de Sensores Ordenados Alfabéticamente
SELECT s.tipo, u.nombre AS unidad_medida, e.nombre AS estacion 
FROM sensor s 
JOIN estacion e ON s.estacion_id = e.id 
JOIN unidad u ON s.unidad_id = u.id 
ORDER BY s.tipo ASC;

-- 2. Cantidad de Sensores Por Central
SELECT e.nombre, COUNT(s.id) AS cantidad_sensores
FROM estacion e
LEFT JOIN sensor s ON e.id = s.estacion_id
GROUP BY e.nombre;

-- 3. Listado de Sensores que corresponden a la Estacion ubicada en Lat: 34.54...
SELECT s.tipo, u.nombre AS unidad_medida
FROM sensor s
JOIN estacion e ON s.estacion_id = e.id
JOIN unidad u ON s.unidad_id = u.id
WHERE e.latitud = 34.54003770 AND e.longitud = -58.55884130;

-- 4. Listado de Sensores que corresponden a las Estaciones en Localidad con 'V%'
SELECT s.tipo, e.nombre, l.nombre AS localidad
FROM sensor s
JOIN estacion e ON s.estacion_id = e.id
JOIN localidad l ON e.localidad_id = l.id
WHERE l.nombre LIKE 'V%';

-- 5. Promedio de Consumo Corriente en el Día de Hoy para la Estación de Villa Ballester
SELECT AVG(m.valor) AS promedio_corriente_hoy
FROM medicion m
JOIN sensor s ON m.sensor_id = s.id
JOIN estacion e ON s.estacion_id = e.id
WHERE e.nombre LIKE '%Villa Ballester%' 
AND s.tipo = 'Corriente' 
AND m.fecha = CURDATE();

-- 6. Promedio de corriente hoy
SELECT AVG(m.valor) AS promedio_corriente_hoy
FROM medicion m
JOIN sensor s ON m.sensor_id = s.id
JOIN estacion e ON s.estacion_id = e.id
WHERE e.nombre LIKE '%Villa Ballester%' 
AND s.tipo = 'Corriente' 
AND m.fecha = CURDATE();

-- 7. Valor Mínimo de Tensión en el mes de Abril para la Estación de Villa Ballester.
SELECT MIN(m.valor) AS min_tension_abril
FROM medicion m
JOIN sensor s ON m.sensor_id = s.id
JOIN estacion e ON s.estacion_id = e.id
WHERE e.nombre LIKE '%Villa Ballester%' 
AND s.tipo = 'Tensión' 
AND m.fecha BETWEEN '2026-04-01' AND '2026-04-30';