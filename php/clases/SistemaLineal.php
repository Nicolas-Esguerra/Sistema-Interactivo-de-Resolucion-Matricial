<?php
declare(strict_types=1);

/** Módulo 1: resuelve A·x = b con Gauss-Jordan sobre la matriz aumentada [A | b]. */
final class SistemaLineal extends MetodoMatricial
{
    public function nombre(): string
    {
        return 'Sistema de ecuaciones lineales';
    }

    public function ejecutar(): array
    {
        $inicial = self::texto($this->matriz);
        $N = count($this->matriz[0]) - 1;
        [$M, $piv] = $this->gaussJordan($this->matriz, $N);

        foreach ($M as $i => $fila) {
            $sinCoef = true;
            for ($j = 0; $j < $N; $j++) {
                $sinCoef = $sinCoef && $fila[$j]->esCero();
            }
            if ($sinCoef && !$fila[$N]->esCero()) {
                return ['inicial' => $inicial, 'estado' => 'inconsistente',
                    'titulo' => 'Sistema inconsistente (sin solución)',
                    'lineas' => ['La fila F' . ($i + 1) . ' equivale a 0 = ' . $fila[$N] . ', lo cual es imposible.']];
            }
        }

        if (count($piv) === $N) {
            $lineas = [];
            foreach ($piv as $i => $c) {
                $lineas[] = 'x' . ($c + 1) . ' = ' . $M[$i][$N] . self::aprox($M[$i][$N]);
            }
            return ['inicial' => $inicial, 'estado' => 'unica', 'titulo' => 'Solución única', 'lineas' => $lineas];
        }

        $libres = array_values(array_diff(range(0, $N - 1), $piv));
        $lineas = [];
        foreach ($libres as $k => $j) {
            $lineas[] = 'x' . ($j + 1) . ' = t' . ($k + 1) . '  (variable libre)';
        }
        foreach ($piv as $i => $c) {
            $e = (string)$M[$i][$N];
            foreach ($libres as $k => $j) {
                $a = $M[$i][$j];
                if ($a->esCero()) {
                    continue;
                }
                $e .= $a->esNegativa() ? ' + ' . $a->negar() . '·t' . ($k + 1) : ' − ' . $a . '·t' . ($k + 1);
            }
            $lineas[] = 'x' . ($c + 1) . ' = ' . $e;
        }
        return ['inicial' => $inicial, 'estado' => 'infinita',
            'titulo' => 'Infinitas soluciones (rango ' . count($piv) . ' < ' . $N . ' incógnitas)', 'lineas' => $lineas];
    }
}
