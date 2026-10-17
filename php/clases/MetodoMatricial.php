<?php
declare(strict_types=1);

/** Contrato que cumplen todos los módulos de cálculo. */
interface Ejecutable
{
    public function ejecutar(): array;
    public function nombre(): string;
}

/**
 * Clase base abstracta.
 * HERENCIA: SistemaLineal y MatrizInversa heredan el algoritmo de Gauss-Jordan y el registro de pasos.
 * ENCAPSULAMIENTO: $pasos es privado (solo se escribe con registrar()); $matriz es protegida.
 * POLIMORFISMO: ejecutar() y nombre() son abstractos; cada subclase los implementa a su manera.
 */
abstract class MetodoMatricial implements Ejecutable
{
    private array $pasos = [];

    /** @param Fraccion[][] $matriz */
    public function __construct(protected array $matriz)
    {
    }

    abstract public function ejecutar(): array;
    abstract public function nombre(): string;

    public function pasos(): array
    {
        return $this->pasos;
    }

    protected function registrar(string $texto, array $M, array $resalte): void
    {
        $this->pasos[] = ['texto' => $texto, 'matriz' => self::texto($M), 'resalte' => $resalte];
    }

    protected static function texto(array $M): array
    {
        return array_map(fn($fila) => array_map('strval', $fila), $M);
    }

    /** Gauss-Jordan sobre las primeras $ncoef columnas. Devuelve [matriz reducida, columnas pivote]. */
    protected function gaussJordan(array $M, int $ncoef): array
    {
        $R = count($M);
        $pivotes = [];
        $r = 0;
        for ($c = 0; $c < $ncoef && $r < $R; $c++) {
            $p = -1;
            for ($i = $r; $i < $R; $i++) {
                if (!$M[$i][$c]->esCero()) { $p = $i; break; }
            }
            if ($p < 0) {
                continue;
            }
            if ($p !== $r) {
                [$M[$p], $M[$r]] = [$M[$r], $M[$p]];
                $this->registrar('Intercambio: F' . ($r + 1) . ' ↔ F' . ($p + 1) . '  (la columna ' . ($c + 1) . ' tenía un cero en F' . ($r + 1) . ')',
                    $M, ['filas' => [$r, $p], 'pivote' => [$r, $c]]);
            }
            $pv = $M[$r][$c];
            if (!$pv->esUno()) {
                $k = (new Fraccion(1))->dividir($pv);
                $M[$r] = array_map(fn($x) => $x->multiplicar($k), $M[$r]);
                $this->registrar('Escalar: F' . ($r + 1) . ' → (' . $k . ')·F' . ($r + 1) . '  (el pivote pasa a valer 1)',
                    $M, ['filas' => [$r], 'pivote' => [$r, $c]]);
            }
            for ($i = 0; $i < $R; $i++) {
                if ($i === $r || $M[$i][$c]->esCero()) {
                    continue;
                }
                $k = $M[$i][$c];
                $M[$i] = array_map(fn($x, $y) => $x->restar($k->multiplicar($y)), $M[$i], $M[$r]);
                $this->registrar('Eliminar: F' . ($i + 1) . ' → F' . ($i + 1) . ' − (' . $k . ')·F' . ($r + 1),
                    $M, ['filas' => [$i], 'pivote' => [$r, $c]]);
            }
            $pivotes[] = $c;
            $r++;
        }
        return [$M, $pivotes];
    }

    protected static function aprox(Fraccion $f): string
    {
        return $f->esEntera() ? '' : ' ≈ ' . number_format($f->aFloat(), 4, '.', '');
    }
}
