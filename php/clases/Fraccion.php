<?php
declare(strict_types=1);

/**
 * Fracción exacta e inmutable.
 * ENCAPSULAMIENTO: numerador y denominador son privados; solo se accede por métodos.
 */
final class Fraccion
{
    private int $n;
    private int $d;

    public function __construct(int $n, int $d = 1)
    {
        if ($d === 0) {
            throw new InvalidArgumentException('División entre cero.');
        }
        if ($d < 0) {
            $n = -$n;
            $d = -$d;
        }
        $g = self::mcd(abs($n), $d);
        $this->n = intdiv($n, $g);
        $this->d = intdiv($d, $g);
    }

    private static function mcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }
        return $a === 0 ? 1 : $a;
    }

    /** Acepta enteros, decimales (1.5 o 1,5) y fracciones (3/4). */
    public static function desdeTexto(string $texto): self
    {
        $t = str_replace(',', '.', trim($texto));
        if ($t === '') {
            return new self(0);
        }
        $partes = explode('/', $t);
        if (count($partes) > 2) {
            throw new InvalidArgumentException("Valor no válido: \"$texto\".");
        }
        $f = self::decimal($partes[0], $texto);
        return count($partes) === 2 ? $f->dividir(self::decimal($partes[1], $texto)) : $f;
    }

    private static function decimal(string $s, string $original): self
    {
        if (!preg_match('/^([+-]?)(\d*)\.?(\d*)$/', trim($s), $m) || $m[2] . $m[3] === '') {
            throw new InvalidArgumentException("Valor no válido: \"$original\".");
        }
        $n = (int)($m[2] . $m[3]);
        if ($m[1] === '-') {
            $n = -$n;
        }
        return new self($n, 10 ** strlen($m[3]));
    }

    public function sumar(self $o): self      { return new self($this->n * $o->d + $o->n * $this->d, $this->d * $o->d); }
    public function restar(self $o): self     { return new self($this->n * $o->d - $o->n * $this->d, $this->d * $o->d); }
    public function multiplicar(self $o): self { return new self($this->n * $o->n, $this->d * $o->d); }
    public function dividir(self $o): self    { return new self($this->n * $o->d, $this->d * $o->n); }
    public function negar(): self             { return new self(-$this->n, $this->d); }
    public function esCero(): bool            { return $this->n === 0; }
    public function esUno(): bool             { return $this->n === 1 && $this->d === 1; }
    public function esEntera(): bool          { return $this->d === 1; }
    public function esNegativa(): bool        { return $this->n < 0; }
    public function aFloat(): float           { return $this->n / $this->d; }

    public function __toString(): string
    {
        return $this->d === 1 ? (string)$this->n : $this->n . '/' . $this->d;
    }
}
