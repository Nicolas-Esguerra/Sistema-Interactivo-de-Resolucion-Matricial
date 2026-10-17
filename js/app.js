'use strict';

/* ---------- Almacenamiento (ENCAPSULAMIENTO: campos privados con #) ---------- */
class Almacen {
  #prefijo;
  constructor(prefijo) { this.#prefijo = prefijo; }
  obtener(clave, defecto) {
    try {
      const v = localStorage.getItem(this.#prefijo + clave);
      return v === null ? defecto : JSON.parse(v);
    } catch { return defecto; }
  }
  guardar(clave, valor) {
    try { localStorage.setItem(this.#prefijo + clave, JSON.stringify(valor)); } catch { /* sin espacio o bloqueado */ }
  }
}

class Historial {
  #almacen; #max = 15;
  constructor(almacen) { this.#almacen = almacen; }
  listar() { const l = this.#almacen.obtener('historial', []); return Array.isArray(l) ? l : []; }
  agregar(item) {
  const otros = this.listar().filter(x => !(x.modulo === item.modulo && JSON.stringify(x.datos) === JSON.stringify(item.datos)));
  this.#almacen.guardar('historial', [item, ...otros].slice(0, this.#max));
  }
  borrar() { this.#almacen.guardar('historial', []); }
}

/* ---------- Comunicación con PHP ---------- */
class ClientePhp {
  #url;
  constructor(url) { this.#url = url; }
  async calcular(payload) {
    let resp;
    try {
      resp = await fetch(this.#url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    } catch {
      throw new Error('No se pudo contactar el servidor PHP. Abre una terminal en la carpeta del proyecto y ejecuta: php -S localhost:8000 (o php -S 127.0.0.1:8000)');
    }
    let datos;
    try { datos = await resp.json(); } catch {
      throw new Error('El servidor no devolvió datos válidos. Abre la página desde http://localhost:8000 o http://127.0.0.1:8000/ (no como archivo).');
    }
    if (!datos.ok) throw new Error(datos.error);
    return datos;
  }
}

/* ---------- Dibujo de tablas ---------- */
class Vista {
  static tabla(M, corte, r) {
    const filas = M.map((fila, i) => '<tr>' + fila.map((x, j) => {
      const c = [];
      if (corte && j === corte) c.push('sep');
      if (r && r.filas && r.filas.includes(i)) c.push('chg');
      if (r && r.pivote && r.pivote[0] === i && r.pivote[1] === j) c.push('piv');
      return `<td class="${c.join(' ')}">${x}</td>`;
    }).join('') + '</tr>').join('');
    return `<div class="scroll"><table class="mx">${filas}</table></div>`;
  }
  static pasos(inicial, pasos, corte) {
    let h = '<h2>Procedimiento</h2><p class="legend"><span class="sw" style="background:var(--hi)"></span>pivote &nbsp; <span class="sw" style="background:var(--chg)"></span>fila modificada</p><ol class="steps">';
    h += `<li class="ini">${Vista.tabla(inicial, corte)}</li>`;
    for (const p of pasos) h += `<li><p class="op">${p.texto}</p>${Vista.tabla(p.matriz, corte, p.resalte)}</li>`;
    if (!pasos.length) h += '<li><p class="op">No fue necesaria ninguna operación: la matriz ya está reducida.</p></li>';
    return h + '</ol>';
  }
}

/* ---------- Módulos:  ---------- */
class Modulo {
  constructor(id, api, almacen, historial) {
    this.id = id; this.api = api; this.almacen = almacen; this.historial = historial;
    this.grilla = document.querySelector('#g-' + id);
    this.salida = document.querySelector('#o-' + id);
    this.error = document.querySelector('#e-' + id);
    document.querySelector('#crear-' + id).onclick = () => this.crear();
    document.querySelector('#go-' + id).onclick = () => this.resolver();
  }
  // Métodos
  dimensiones() { throw new Error('Debe implementarse en la subclase'); }
  ponerDimensiones() { throw new Error('Debe implementarse en la subclase'); }
  esAumentada() { return false; }
  mostrarResultado() { throw new Error('Debe implementarse en la subclase'); }
  resumen() { throw new Error('Debe implementarse en la subclase'); }

  etiqueta(j, C) { return 'col ' + (j + 1); }

  crear(valores) {
    this.error.textContent = ''; this.salida.innerHTML = '';
    try {
      const [R, C] = this.dimensiones();
      this.grilla.style.setProperty('--c', C);
      this.grilla.innerHTML = '';
      for (let j = 0; j < C; j++) {
        const h = document.createElement('div'); h.className = 'h'; h.textContent = this.etiqueta(j, C); this.grilla.appendChild(h);
      }
      for (let i = 0; i < R; i++) for (let j = 0; j < C; j++) {
        const x = document.createElement('input');
        x.setAttribute('aria-label', `fila ${i + 1}, columna ${j + 1}`);
        if (this.esAumentada() && j === C - 1) x.className = 'aug';
        if (valores) x.value = valores[i][j];
        x.oninput = () => this.guardarBorrador();
        this.grilla.appendChild(x);
      }
      this.guardarBorrador();
    } catch (e) { this.error.textContent = e.message; }
  }
  leerDatos() {
    const [R, C] = this.dimensiones();
    const celdas = [...this.grilla.querySelectorAll('input')];
    return Array.from({ length: R }, (_, i) => celdas.slice(i * C, (i + 1) * C).map(c => c.value));
  }
  guardarBorrador() {
    try { const [R, C] = this.dimensiones(); this.almacen.guardar('borrador-' + this.id, { R, C, datos: this.leerDatos() }); } catch { /* dimensiones inválidas */ }
  }
  restaurar() {
    const b = this.almacen.obtener('borrador-' + this.id, null);
    if (b && Array.isArray(b.datos) && b.datos.length) { this.ponerDimensiones(b.R, b.C); this.crear(b.datos); }
    else { this.crear(); }
  }
  async resolver(guardar = true) {
    this.error.textContent = ''; this.salida.innerHTML = '';
    try {
      const [R, C] = this.dimensiones();
      const datos = this.leerDatos();
      const res = await this.api.calcular({ modulo: this.id, filas: R, columnas: C, datos });
      this.salida.innerHTML = this.mostrarResultado(res);
      if (guardar) {
        this.historial.agregar({ modulo: this.id, filas: R, columnas: C, datos, fecha: new Date().toLocaleString('es-CO'), resumen: this.resumen(res) });
        pintarHistorial();
      }
    } catch (e) { this.error.textContent = e.message; }
  }
}

class ModuloSistema extends Modulo {
  constructor(...a) { super('sistema', ...a); }
  dimensiones() {
    const m = leerEntero('#filas-sistema'), n = leerEntero('#cols-sistema');
    return [m, n + 1];
  }
  ponerDimensiones(R, C) { document.querySelector('#filas-sistema').value = R; document.querySelector('#cols-sistema').value = C - 1; }
  esAumentada() { return true; }
  etiqueta(j, C) { return j === C - 1 ? 'términos indep.' : 'x' + (j + 1); }
  mostrarResultado(res) {
    const r = res.resultado, malo = r.estado === 'inconsistente';
    return Vista.pasos(r.inicial, res.pasos, r.inicial[0].length - 1) +
      `<div class="res${malo ? ' bad' : ''}"><b>${r.titulo}</b>${r.lineas.map(l => `<p>${l}</p>`).join('')}</div>`;
  }
  resumen(res) { return res.resultado.titulo; }
}

class ModuloInversa extends Modulo {
  constructor(...a) { super('inversa', ...a); }
  dimensiones() { const n = leerEntero('#filas-inversa'); return [n, n]; }
  ponerDimensiones(R) { document.querySelector('#filas-inversa').value = R; }
  mostrarResultado(res) {
    const r = res.resultado, n = r.inicial.length;
    let h = `<h2>¿Tiene inversa?</h2><p>El determinante se calcula triangularizando A (producto de pivotes, con cambio de signo por cada intercambio de filas: ${r.intercambios}).</p>`;
    h += `<div class="res${r.invertible ? '' : ' bad'}"><p>det(A) = ${r.det}</p><p><b>${r.invertible ? 'det(A) ≠ 0: la matriz es invertible.' : 'det(A) = 0: la matriz es singular y no tiene inversa.'}</b></p></div>`;
    if (!r.invertible) return h;
    h += Vista.pasos(r.inicial, res.pasos, n).replace('<h2>Procedimiento</h2>', '<h2>Gauss-Jordan sobre [A | I]</h2>');
    h += `<div class="res"><b>Matriz inversa A⁻¹</b>${Vista.tabla(r.inversa, 0)}</div>`;
    h += `<h2>Verificación</h2><p>A · A⁻¹ debe dar la identidad:</p>${Vista.tabla(r.verificacion, 0)}`;
    return h;
  }
  resumen(res) { return res.resultado.invertible ? 'det = ' + res.resultado.det : 'No invertible (det = 0)'; }
}

/* ---------- Arranque ---------- */
function leerEntero(sel) {
  const v = Number(document.querySelector(sel).value);
  if (!Number.isInteger(v) || v < 1 || v > 8) throw new Error('Las dimensiones deben ser enteros entre 1 y 8.');
  return v;
}

const almacen = new Almacen('matrices:');
const historial = new Historial(almacen);
const api = new ClientePhp('php/api.php');
const modulos = {
  sistema: new ModuloSistema(api, almacen, historial),
  inversa: new ModuloInversa(api, almacen, historial)
};

function mostrarTab(id) {
  for (const k of Object.keys(modulos)) {
    document.querySelector('#t-' + k).setAttribute('aria-selected', String(k === id));
    document.querySelector('#p-' + k).hidden = k !== id;
  }
  almacen.guardar('tab', id);
}
for (const k of Object.keys(modulos)) document.querySelector('#t-' + k).onclick = () => mostrarTab(k);

function pintarHistorial() {
  const cont = document.querySelector('#historial');
  const lista = historial.listar();
  cont.innerHTML = '';
  if (!lista.length) { cont.innerHTML = '<p class="vacio">Aún no hay cálculos guardados.</p>'; return; }
  const ul = document.createElement('ul');
  for (const it of lista) {
    const li = document.createElement('li'), b = document.createElement('button');
    b.textContent = `${it.fecha} · ${it.modulo === 'sistema' ? 'Sistema' : 'Inversa'} ${it.filas}×${it.columnas} · ${it.resumen}`;
    b.onclick = () => {
      const m = modulos[it.modulo];
      if (!m) return;
      mostrarTab(it.modulo); m.ponerDimensiones(it.filas, it.columnas); m.crear(it.datos); m.resolver(false);
    };
    li.appendChild(b); ul.appendChild(li);
  }
  cont.appendChild(ul);
}
document.querySelector('#borrar-hist').onclick = () => { historial.borrar(); pintarHistorial(); };

modulos.sistema.restaurar();
modulos.inversa.restaurar();
mostrarTab(almacen.obtener('tab', 'sistema') in modulos ? almacen.obtener('tab', 'sistema') : 'sistema');
pintarHistorial();

const btnTema = document.querySelector('#btn-tema');

btnTema.onclick = () => {
  const nuevo = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
  document.documentElement.dataset.theme = nuevo;
  try { localStorage.setItem('matrices-tema', nuevo); } catch { /* sin almacenamiento */ }
};