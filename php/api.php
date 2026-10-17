<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

foreach (['Fraccion', 'MetodoMatricial', 'SistemaLineal', 'MatrizInversa'] as $clase) {
    require_once __DIR__ . "/clases/$clase.php";
}

try {
    $in = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    $modulo = (string)($in['modulo'] ?? '');
    $filas = (int)($in['filas'] ?? 0);
    $cols = (int)($in['columnas'] ?? 0);
    $datos = $in['datos'] ?? [];

    if ($filas < 1 || $filas > 8 || $cols < 1 || $cols > 9) {
        throw new InvalidArgumentException('Las dimensiones permitidas son de 1 a 8.');
    }
    if ($modulo === 'inversa' && $filas !== $cols) {
        throw new InvalidArgumentException('La matriz debe ser cuadrada.');
    }
    if (!is_array($datos) || count($datos) !== $filas) {
        throw new InvalidArgumentException('Los datos no coinciden con las dimensiones.');
    }
    $matriz = [];
    foreach ($datos as $fila) {
        if (!is_array($fila) || count($fila) !== $cols) {
            throw new InvalidArgumentException('Los datos no coinciden con las dimensiones.');
        }
        $matriz[] = array_map(fn($t) => Fraccion::desdeTexto((string)$t), array_values($fila));
    }

    // Polimorfismo: ambos objetos se usan igual (Ejecutable), pero calculan distinto.
    $metodo = match ($modulo) {
        'sistema' => new SistemaLineal($matriz),
        'inversa' => new MatrizInversa($matriz),
        default => throw new InvalidArgumentException('Módulo desconocido.'),
    };
    $resultado = $metodo->ejecutar();

    echo json_encode(['ok' => true, 'metodo' => $metodo->nombre(), 'pasos' => $metodo->pasos(), 'resultado' => $resultado],
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(400);
    $msg = $e instanceof TypeError ? 'Los números son demasiado grandes para calcular con exactitud.' : $e->getMessage();
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
}
