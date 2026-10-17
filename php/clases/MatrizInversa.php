<?php
declare(strict_types=1);

/** Módulo 2: valida det(A) ≠ 0 y calcula A⁻¹ con Gauss-Jordan sobre [A | I]. */
final class MatrizInversa extends MetodoMatricial
{
    public function nombre(): string
    {
        return 'Matriz inversa';
    }

    /** Determinante por triangularización. Devuelve [det, número de intercambios]. */
    private function determinante(): array
    {
        $D = $this->matriz;
        $n = count($D);
        $det = new Fraccion(1);
        $intercambios = 0;
        for ($c = 0; $c < $n; $c++) {
            $p = -1;
            for ($i = $c; $i < $n; $i++) {
                if (!$D[$i][$c]->esCero()) { $p = $i; break; }
            }
            if ($p < 0) {
                return [new Fraccion(0), $intercambios];
            }
            if ($p !== $c) {
                [$D[$p], $D[$c]] = [$D[$c], $D[$p]];
                $intercambios++;
            }
            $det = $det->multiplicar($D[$c][$c]);
            for ($i = $c + 1; $i < $n; $i++) {
                $k = $D[$i][$c]->dividir($D[$c][$c]);
                $D[$i] = array_map(fn($x, $y) => $x->restar($k->multiplicar($y)), $D[$i], $D[$c]);
            }
        }
        if ($intercambios % 2 === 1) {
            $det = (new Fraccion(0))->restar($det);
        }
        return [$det, $intercambios];
    }

    public function ejecutar(): array
    {
        $n = count($this->matriz);
        [$det, $sw] = $this->determinante();
        if ($det->esCero()) {
            return ['inicial' => self::texto($this->matriz), 'invertible' => false, 'det' => '0', 'intercambios' => $sw];
        }

        $aumentada = [];
        foreach ($this->matriz as $i => $fila) {
            $id = [];
            for ($j = 0; $j < $n; $j++) {
                $id[] = new Fraccion($i === $j ? 1 : 0);
            }
            $aumentada[] = array_merge($fila, $id);
        }
        [$M] = $this->gaussJordan($aumentada, $n);
        $inversa = array_map(fn($f) => array_slice($f, $n), $M);

        $producto = [];
        foreach ($this->matriz as $fila) {
            $nueva = [];
            for ($j = 0; $j < $n; $j++) {
                $s = new Fraccion(0);
                foreach ($fila as $k => $x) {
                    $s = $s->sumar($x->multiplicar($inversa[$k][$j]));
                }
                $nueva[] = $s;
            }
            $producto[] = $nueva;
        }

        return ['inicial' => self::texto($aumentada), 'invertible' => true, 'det' => (string)$det, 'intercambios' => $sw,
            'inversa' => self::texto($inversa), 'verificacion' => self::texto($producto)];
    }
}
