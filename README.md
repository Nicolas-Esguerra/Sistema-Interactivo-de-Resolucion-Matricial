# Sistema Interactivo de Resolución Matricial

*Trabajo de `Estructuras de información` — Nicolás Esguerra, Esteban Gómez, Emily Solano y Juan Quinche, `Técnico Profesional en Programación Web`, **UniEspinal**.*

Es una aplicación web que resuelve sistemas de ecuaciones lineales con el método de Gauss-Jordan y calcula la matriz inversa de una matriz cuadrada. Lo importante es que muestra cada operación de fila, para poder comparar el procedimiento con el que se hace a mano.

## Cómo ejecutarla

Necesita PHP 8.0 o superior. Desde una terminal, dentro de la carpeta del proyecto:

    php -S localhost:8000

Si el navegador no conecta, prueba con `php -S 127.0.0.1:8000`. Después abre la dirección correspondiente (`http://localhost:8000` o `http://127.0.0.1:8000`).

También se puede usar con XAMPP o WAMP: se copia la carpeta a `htdocs` (o `www`) y se abre `http://localhost/sirm/`.

## Cómo está organizado

- `index.html` y `css/styles.css`: la interfaz.
- `js/app.js`: maneja las tablas, el historial y el `localStorage`.
- `php/api.php`: recibe la matriz en JSON, elige qué clase usar y devuelve los pasos y el resultado.
- `php/clases/`: las clases que hacen el cálculo.

## Programación orientada a objetos

- **Herencia:** `SistemaLineal` y `MatrizInversa` heredan de `MetodoMatricial`, donde está el algoritmo de Gauss-Jordan y el registro de pasos. En JavaScript, `ModuloSistema` y `ModuloInversa` heredan de `Modulo`.
- **Polimorfismo:** `api.php` llama a `ejecutar()` sin saber qué clase tiene delante; cada una calcula a su manera. En JavaScript pasa algo parecido con `mostrarResultado()`.
- **Encapsulamiento:** `Fraccion` guarda su numerador y denominador como privados, y `MetodoMatricial` guarda los pasos en una lista privada. En JavaScript, `Almacen`, `Historial` y `ClientePhp` usan campos privados con `#`.

## Qué se guarda en localStorage

El borrador de cada tabla, la pestaña que estaba abierta y los últimos 15 cálculos, que se pueden volver a abrir con un clic.

## Limitaciones

- El tamaño máximo es de 8 filas o columnas.
- Los cálculos usan fracciones exactas con enteros de PHP. Si los números son demasiado grandes, la app avisa en lugar de redondear.