//1. Listado de Sensores Ordenados Alfabéticamente
SELECT s.tipo, s.unidad_medida, e.nombre AS estacion
FROM sensor s
JOIN estacion e ON s.estacion_id = e.id
ORDER BY s.tipo ASC;

//2. Cantidad de Sensores Por Central
SELECT e.nombre, COUNT(s.id) AS cantidad_sensores
FROM estacion e
LEFT JOIN sensor s ON e.id = s.estacion_id
GROUP BY e.nombre;

//3. Listado de Sensores que corresponden a la Estación ubicada en Lat: 34.5400377 Long:
-58.5588413
SELECT s.tipo, s.unidad_medida
FROM sensor s
JOIN estacion e ON s.estacion_id = e.id
WHERE e.latitud = 34.54003770 AND e.longitud = -58.5588413;

//4. Listado de Sensores que corresponden a las Estaciones ubicadas en las Localidades que comienzan con “V”
SELECT s.tipo, e.nombre, e.localidad
FROM sensor s
JOIN estacion e ON s.estacion_id = e.id
WHERE e.localidad LIKE 'V%';

//5. Promedio de Consumo Corriente en el Día de Hoy para la Estación de Villa Ballester
SELECT AVG(m.valor) AS promedio_corriente_hoy
FROM medicion m
JOIN sensor s ON m.sensor_id = s.id
JOIN estacion e ON s.estacion_id = e.id
WHERE e.nombre LIKE '%Villa Ballester%' 
AND s.tipo = 'Corriente' 
AND m.fecha = CURDATE();

//6. SELECT AVG(m.valor) AS promedio_corriente_hoy
FROM medicion m
JOIN sensor s ON m.sensor_id = s.id
JOIN estacion e ON s.estacion_id = e.id
WHERE e.nombre LIKE '%Villa Ballester%' 
AND s.tipo = 'Corriente' 
AND m.fecha = CURDATE();

//7. Valor Mínimo de Tensión en el mes de Abril para la Estación de Villa Ballester.
SELECT MIN(m.valor) AS min_tension_abril
FROM medicion m
JOIN sensor s ON m.sensor_id = s.id
JOIN estacion e ON s.estacion_id = e.id
WHERE e.nombre LIKE '%Villa Ballester%' 
AND s.tipo = 'Tensión' 
AND m.fecha BETWEEN '2026-04-01' AND '2026-04-30';