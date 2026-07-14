<?php
// PHP helper functions for processing shifts and liquidaciones

/**
 * Calculates shifts from raw novelty/check-in rows
 * Each row must have: fecha, hora, tipo, vigilador_id, nombre, apellido, observaciones
 */
function calcularLiquidacion(array $rows): array {
    $by_vigilador = [];
    foreach ($rows as $r) {
        $vid = (string)$r['vigilador_id'];
        if (!isset($by_vigilador[$vid])) {
            $by_vigilador[$vid] = [];
        }
        $by_vigilador[$vid][] = $r;
    }

    $report = [];
    foreach ($by_vigilador as $vid => $v_rows) {
        // Sort chronologically
        usort($v_rows, function($a, $b) {
            $tA = $a['fecha'] . ' ' . $a['hora'];
            $tB = $b['fecha'] . ' ' . $b['hora'];
            return strcmp($tA, $tB);
        });

        $nombre = $v_rows[0]['nombre'] ?? '';
        $apellido = $v_rows[0]['apellido'] ?? '';
        $name = trim("$nombre $apellido");
        if (empty($name)) {
            $name = "Vigilador ID $vid";
        }

        $vigilador_report = [
            'vid' => $vid,
            'name' => $name,
            'shifts' => [],
            'anomalies' => []
        ];

        $active_entry = null;

        foreach ($v_rows as $r) {
            $tipo = (string)$r['tipo'];
            $dt_str = $r['fecha'] . ' ' . $r['hora'];
            
            // Try different datetime formats if seconds are missing or weird
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dt_str);
            if (!$dt) {
                $dt = DateTime::createFromFormat('Y-m-d H:i', $r['fecha'] . ' ' . substr($r['hora'], 0, 5));
            }
            if (!$dt) {
                // If it still fails, skip this row
                continue;
            }
            
            $obs = $r['observaciones'] ?? '';

            if ($tipo === '1') { // Entrada
                if ($active_entry !== null) {
                    $diff = $dt->getTimestamp() - $active_entry['dt']->getTimestamp();
                    if ($diff < 300) {
                        // Ignore duplicates within 5 minutes
                        continue;
                    } else {
                        // Unpaired entry
                        $vigilador_report['anomalies'][] = [
                            'type' => 'Entrada sin salida',
                            'dt' => $active_entry['dt']->format('Y-m-d H:i:s'),
                            'obs' => $active_entry['obs']
                        ];
                    }
                }
                $active_entry = ['dt' => $dt, 'obs' => $obs, 'row' => $r];
            } else if ($tipo === '2') { // Salida
                if ($active_entry !== null) {
                    $diff_seconds = $dt->getTimestamp() - $active_entry['dt']->getTimestamp();
                    $diff_hours = $diff_seconds / 3600.0;

                    if ($diff_hours > 18.0) {
                        // Exceeds 18 hours, count as separate anomalies
                        $vigilador_report['anomalies'][] = [
                            'type' => 'Entrada sin salida (excede 18hs)',
                            'dt' => $active_entry['dt']->format('Y-m-d H:i:s'),
                            'obs' => $active_entry['obs']
                        ];
                        $vigilador_report['anomalies'][] = [
                            'type' => 'Salida sin entrada (excede 18hs)',
                            'dt' => $dt->format('Y-m-d H:i:s'),
                            'obs' => $obs
                        ];
                    } else {
                        // Valid pair
                        $vigilador_report['shifts'][] = [
                            'entry' => $active_entry['dt']->format('Y-m-d H:i:s'),
                            'exit' => $dt->format('Y-m-d H:i:s'),
                            'hours' => $diff_hours,
                            'obs' => trim("Entrada: " . $active_entry['obs'] . " | Salida: " . $obs, " | ")
                        ];
                    }
                    $active_entry = null;
                } else {
                    // Exit without entry
                    $vigilador_report['anomalies'][] = [
                        'type' => 'Salida sin entrada',
                        'dt' => $dt->format('Y-m-d H:i:s'),
                        'obs' => $obs
                    ];
                }
            }
        }

        if ($active_entry !== null) {
            $vigilador_report['anomalies'][] = [
                'type' => 'Entrada sin salida (fin de registros)',
                'dt' => $active_entry['dt']->format('Y-m-d H:i:s'),
                'obs' => $active_entry['obs']
            ];
        }

        $report[] = $vigilador_report;
    }

    // Sort report alphabetically by vigilador name
    usort($report, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });

    return $report;
}

/**
 * Format decimal hours to HH:MM format
 */
function formatDecimalHours($hours_decimal) {
    $total_minutes = (int)round($hours_decimal * 60);
    $h = floor($total_minutes / 60);
    $m = $total_minutes % 60;
    return sprintf("%02d:%02d", $h, $m);
}
