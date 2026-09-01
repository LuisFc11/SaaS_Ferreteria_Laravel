@extends('layouts.app')

@section('titulo', 'Punto de Venta')

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

@if (session('venta_id'))
    <div class="mb-4 flex flex-wrap items-center gap-3 bg-teal-brand/10 border border-teal-brand/30 rounded-lg px-4 py-3">
        <p class="text-sm font-semibold text-teal-dark">¿Deseas imprimir el comprobante de la venta?</p>
        <a href="{{ route('pos.recibo', session('venta_id')) }}" target="_blank"
           class="inline-flex items-center gap-2 bg-teal-brand hover:bg-teal-dark text-white text-xs font-bold px-4 py-2 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" /></svg>
            Imprimir ticket
        </a>
    </div>
@endif

<!-- FORMULARIO DE VENTA (oculto) -->
<form id="formVenta" action="{{ route('pos.store') }}" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="items" id="inputItems">
    <input type="hidden" name="cliente_id" id="selectCliente">
    <input type="hidden" name="tipo_comprobante" id="selectComprobante" value="ticket">
    <input type="hidden" name="metodo_pago" id="selMetodoPago" value="efectivo">
    <input type="hidden" name="descuento" id="inputDescuento" value="0">
    <input type="hidden" name="fecha_vencimiento" id="inputVencimiento">
    <input type="hidden" name="con_cuanto_paga" id="inputConCuantoPaga">
</form>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 items-start">

    <!-- ===== Catálogo ===== -->
    <div class="xl:col-span-2">
        <div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    <input type="text" id="buscarPos" placeholder="Buscar o escanear código de barras... (F2)" autofocus autocomplete="off"
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
                </div>
                <select id="filtroCategoria" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="gridProductos" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 max-h-[65vh] overflow-y-auto pr-1"></div>
        <p id="sinResultados" class="hidden text-center text-slate-400 text-sm py-10">No se encontraron productos.</p>
    </div>

    <!-- ===== CARRITO ===== -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm xl:sticky xl:top-20">
        <div class="px-5 pt-5 pb-3 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-navy">Carrito <span id="numItems" class="text-xs font-semibold text-slate-400"></span></h2>
            <button onclick="vaciarCarrito()" class="text-xs font-semibold text-red-400 hover:text-red-600 transition">Vaciar</button>
        </div>

        <div id="carritoItems" class="divide-y divide-slate-100 max-h-72 overflow-y-auto"></div>
        <p id="carritoVacio" class="text-center text-slate-400 text-sm py-10">Agrega productos del catálogo</p>

        <!-- Totales del carrito -->
        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
            <div class="text-sm space-y-1">
                <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span id="lblSubtotal" class="font-semibold">{{ $moneda }} 0.00</span></div>
                <div class="flex justify-between"><span class="text-slate-500">IGV ({{ auth()->user()->empresa->impuesto + 0 }}%)</span><span id="lblImpuesto" class="font-semibold">{{ $moneda }} 0.00</span></div>
                <div class="flex justify-between" id="filaDescuento" style="display:none"><span class="text-slate-500">Descuento</span><span id="lblDescuento" class="text-red-500 font-semibold"></span></div>
                <div class="flex justify-between font-bold text-navy text-lg pt-1 border-t border-slate-200">
                    <span>TOTAL</span>
                    <span id="lblTotal" class="text-teal-600">{{ $moneda }} 0.00</span>
                </div>
            </div>
        </div>

        <button type="button" id="btnCobrar" disabled onclick="abrirModalPago()"
            class="w-full bg-teal-brand hover:bg-teal-dark disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold py-3 rounded-lg shadow-lg shadow-teal-brand/30 transition">
            Cobrar (F9)
        </button>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL DE PAGO -->
<!-- ============================================================ -->
<div id="modalPago" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black/60" onclick="cerrarModalPago()"></div>

        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <!-- Cabecera -->
            <div class="bg-gradient-to-r from-teal-500/10 to-teal-500/5 px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-2xl font-bold text-navy">💰 Confirmar Pago</h3>
                <button onclick="cerrarModalPago()" class="text-slate-400 hover:text-slate-600 text-2xl">✕</button>
            </div>

            <div class="bg-white px-6 pt-6 pb-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <!-- COLUMNA IZQUIERDA -->
                    <div class="space-y-4">
                        
                        <!-- Cliente -->
                        <div class="bg-slate-50 rounded-xl p-4 border">
                            <label class="block text-xs font-bold text-slate-500 mb-2">👤 Cliente</label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input type="text" id="buscarCliente" placeholder="Buscar por DNI o nombre..."
                                           class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                    <div id="resultadosClientes" class="absolute z-10 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-40 overflow-y-auto hidden"></div>
                                </div>
                                <button type="button" onclick="abrirNuevoCliente()"
                                        class="bg-teal-500 hover:bg-teal-600 text-white px-3 py-2 rounded-lg text-sm font-semibold whitespace-nowrap">
                                    + Nuevo
                                </button>
                            </div>
                            <input type="hidden" id="clienteId" value="">
                            <div id="clienteInfo" class="mt-2 hidden">
                                <div class="bg-teal-50 border border-teal-200 rounded-lg p-2 flex justify-between items-center">
                                    <span id="clienteNombre" class="font-semibold"></span>
                                    <span id="clienteDoc" class="text-xs text-slate-500"></span>
                                    <button onclick="limpiarCliente()" class="text-red-400 hover:text-red-600">✕</button>
                                </div>
                            </div>
                        </div>

                        <!-- Comprobante -->
                        <div class="bg-slate-50 rounded-xl p-4 border">
                            <label class="block text-xs font-bold text-slate-500 mb-2">📄 Comprobante</label>
                            <select id="tipoComprobante" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                <option value="ticket">Ticket</option>
                                <option value="boleta">Boleta</option>
                                <option value="factura">Factura</option>
                            </select>
                        </div>

                        <!-- Método de pago -->
                        <div class="bg-slate-50 rounded-xl p-4 border">
                            <label class="block text-xs font-bold text-slate-500 mb-2">💳 Método de pago</label>
                            <select id="metodoPago" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="yape">Yape</option>
                                <option value="plin">Plin</option>
                                <option value="credito">Crédito</option>
                            </select>
                        </div>

                        <!-- Descuento -->
                        <div class="bg-slate-50 rounded-xl p-4 border">
                            <label class="block text-xs font-bold text-slate-500 mb-2">🏷️ Descuento ({{ $moneda }})</label>
                            <input type="number" id="descuentoModal" step="0.01" min="0" value="0"
                                   class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        </div>

                        <!-- Vencimiento (crédito) -->
                        <div id="bloqueVencimientoModal" class="hidden bg-amber-50 rounded-xl p-4 border border-amber-200">
                            <label class="block text-xs font-bold text-amber-600 mb-2">📅 Vence el</label>
                            <input type="date" id="fechaVencimiento" value="{{ now()->addDays(30)->format('Y-m-d') }}"
                                   class="w-full bg-white border border-amber-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                            <p class="text-xs text-amber-600 mt-1">⚠️ Requiere seleccionar un cliente</p>
                        </div>

                        <!-- Con cuánto paga -->
                        <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-200">
                            <label class="block text-xs font-bold text-emerald-600 mb-2">💵 Con cuánto paga</label>
                            <input type="number" id="conCuantoPaga" step="0.01" min="0" placeholder="0.00"
                                   class="w-full bg-white border border-emerald-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                            <div id="vueltoInfo" class="mt-2 hidden">
                                <div class="bg-emerald-100 border border-emerald-300 rounded-lg p-2 flex justify-between">
                                    <span class="font-semibold text-emerald-700">Vuelto:</span>
                                    <span id="vueltoCalculado" class="font-bold text-emerald-700">{{ $moneda }} 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA -->
                    <div class="space-y-4">
                        
                        <!-- Totales -->
                        <div class="bg-slate-50 rounded-xl p-5 border">
                            <h4 class="text-xs font-bold text-slate-500 uppercase mb-3">📊 Resumen</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between"><span class="text-slate-600">Subtotal</span><span id="modalSubtotal" class="font-semibold">{{ $moneda }} 0.00</span></div>
                                <div class="flex justify-between"><span class="text-slate-600">IGV ({{ auth()->user()->empresa->impuesto + 0 }}%)</span><span id="modalImpuesto" class="font-semibold">{{ $moneda }} 0.00</span></div>
                                <div class="flex justify-between" id="modalFilaDescuento" style="display:none"><span class="text-slate-600">Descuento</span><span id="modalDescuento" class="text-red-500 font-semibold"></span></div>
                                <div class="flex justify-between font-extrabold text-navy text-xl pt-2 border-t-2 border-teal-500/30">
                                    <span>TOTAL</span>
                                    <span id="modalTotal" class="text-teal-600">{{ $moneda }} 0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Detalle de productos -->
                        <div class="border rounded-xl overflow-hidden">
                            <div class="bg-slate-50 px-4 py-2 border-b">
                                <span class="text-xs font-bold text-slate-500">📦 Detalle de la venta</span>
                                <span id="cantidadItems" class="text-xs text-slate-400 ml-2"></span>
                            </div>
                            <div id="detalleVenta" class="max-h-60 overflow-y-auto divide-y divide-slate-100"></div>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-end border-t pt-6">
                    <button onclick="cerrarModalPago()" class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-semibold rounded-lg hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button onclick="confirmarPago()" id="btnConfirmar" class="px-6 py-2.5 bg-teal-500 hover:bg-teal-600 text-white font-bold rounded-lg shadow-lg shadow-teal-500/30 flex items-center gap-2">
                        ✅ Confirmar Pago
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL NUEVO CLIENTE -->
<!-- ============================================================ -->
<div id="modalNuevoCliente" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/60" onclick="cerrarNuevoCliente()"></div>

        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-gradient-to-r from-teal-500/10 to-teal-500/5 px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-xl font-bold text-navy">👤 Nuevo Cliente</h3>
                <button onclick="cerrarNuevoCliente()" class="text-slate-400 hover:text-slate-600 text-2xl">✕</button>
            </div>

            <div class="px-6 pt-6 pb-8">
                <form id="formCliente" onsubmit="guardarCliente(event)" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Tipo de documento</label>
                        <select name="tipo_documento" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                            <option value="DNI">DNI</option>
                            <option value="RUC">RUC</option>
                            <option value="CE">Carnet de Extranjería</option>
                            <option value="PASAPORTE">Pasaporte</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Número de documento</label>
                        <input type="text" name="numero_documento" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Nombre completo</label>
                        <input type="text" name="nombre" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Email</label>
                        <input type="email" name="email" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Teléfono</label>
                        <input type="text" name="telefono" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Dirección</label>
                        <input type="text" name="direccion" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>

                    <div class="flex gap-3 justify-end mt-4 pt-4 border-t">
                        <button type="button" onclick="cerrarNuevoCliente()" class="px-4 py-2 border-2 border-slate-300 text-slate-600 font-semibold rounded-lg hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-teal-500 hover:bg-teal-600 text-white font-bold rounded-lg shadow-lg shadow-teal-500/30">
                            Guardar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ============================================================
    // CONFIGURACIÓN
    // ============================================================
    const MONEDA = @json($moneda);
    const IMPUESTO = {{ (float) (auth()->user()->empresa->impuesto ?? 18) }};
    const PRODUCTOS = @json($productos);
    const CLIENTES = @json($clientes);
    let carrito = {};

    const grid = document.getElementById('gridProductos');
    const buscar = document.getElementById('buscarPos');
    const filtroCat = document.getElementById('filtroCategoria');

    // ============================================================
    // NOTIFICACIONES
    // ============================================================
    function mostrarNotificacion(mensaje, tipo = 'info') {
        const colores = {
            success: 'bg-emerald-500',
            error: 'bg-red-500',
            warning: 'bg-amber-500',
            info: 'bg-blue-500'
        };
        const iconos = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        const div = document.createElement('div');
        div.className = `fixed top-4 right-4 z-50 ${colores[tipo] || 'bg-blue-500'} text-white px-6 py-4 rounded-xl shadow-2xl transition-all duration-300 flex items-center gap-3 text-sm font-semibold`;
        div.style.transform = 'translateX(100%)';
        div.innerHTML = `<span>${iconos[tipo] || 'ℹ️'}</span><span>${mensaje}</span>`;
        document.body.appendChild(div);

        setTimeout(() => { div.style.transform = 'translateX(0)'; }, 10);
        setTimeout(() => {
            div.style.transform = 'translateX(100%)';
            setTimeout(() => div.remove(), 300);
        }, 3500);
    }

    // ============================================================
    // FUNCIONES DEL CARRITO
    // ============================================================
    function fmt(n) { return MONEDA + ' ' + n.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function disponible(p) { return p.stock - (carrito[p.id]?.cantidad || 0); }

    function renderGrid() {
        const q = buscar.value.trim().toLowerCase();
        const cat = filtroCat.value;
        const lista = PRODUCTOS.filter(p =>
            (!cat || p.categoria_id == cat) &&
            (!q || p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q) || (p.codigo_barras || '').toLowerCase().includes(q))
        );
        document.getElementById('sinResultados').classList.toggle('hidden', lista.length > 0);
        grid.innerHTML = lista.map(p => {
            const disp = disponible(p);
            const sinStock = disp <= 0;
            return `<button type="button" onclick="agregar(${p.id})" ${sinStock ? 'disabled' : ''}
                class="text-left bg-white rounded-xl border border-slate-200 p-3 hover:border-teal-brand hover:shadow-md transition ${sinStock ? 'opacity-50 cursor-not-allowed' : ''}">
                <div class="flex items-start justify-between gap-1">
                    <p class="text-[10px] font-mono text-slate-400">${p.codigo}</p>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full ${disp <= 5 ? 'bg-red-100 text-red-500' : 'bg-lime-brand/20 text-lime-700'}">${disp}</span>
                </div>
                <p class="text-xs font-semibold text-slate-700 leading-snug mt-1 line-clamp-2" style="min-height:2rem">${p.nombre}</p>
                <p class="text-sm font-extrabold text-navy mt-1.5">${fmt(p.precio)} <span class="text-[10px] text-slate-400 font-semibold">/ ${p.unidad}</span></p>
            </button>`;
        }).join('');
    }

    function agregar(id) {
        const p = PRODUCTOS.find(x => x.id === id);
        if (!p || disponible(p) <= 0) return;
        if (carrito[id]) carrito[id].cantidad++;
        else carrito[id] = { id: p.id, nombre: p.nombre, codigo: p.codigo, precio: p.precio, stock: p.stock, unidad: p.unidad, cantidad: 1 };
        renderTodo();
    }

    function cambiarCantidad(id, delta) {
        const item = carrito[id];
        if (!item) return;
        item.cantidad += delta;
        if (item.cantidad <= 0) delete carrito[id];
        else if (item.cantidad > item.stock) item.cantidad = item.stock;
        renderTodo();
    }

    function fijarCantidad(id, valor) {
        const item = carrito[id];
        if (!item) return;
        let v = parseFloat(valor) || 0;
        if (v <= 0) delete carrito[id];
        else item.cantidad = Math.min(v, item.stock);
        renderTodo();
    }

    function quitar(id) { delete carrito[id]; renderTodo(); }

    function vaciarCarrito() {
        if (Object.keys(carrito).length && !confirm('¿Vaciar el carrito?')) return;
        carrito = {};
        renderTodo();
    }

    function renderCarrito() {
        const cont = document.getElementById('carritoItems');
        const items = Object.values(carrito);
        document.getElementById('carritoVacio').style.display = items.length ? 'none' : '';
        document.getElementById('numItems').textContent = items.length ? `(${items.length} ítems)` : '';
        cont.innerHTML = items.map(i => `
            <div class="px-5 py-3 flex items-center gap-2">
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-slate-700 truncate">${i.nombre}</p>
                    <p class="text-[10px] text-slate-400">${fmt(i.precio)} × ${i.cantidad} = <b class="text-navy">${fmt(i.precio * i.cantidad)}</b></p>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button type="button" onclick="cambiarCantidad(${i.id}, -1)" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-sm">−</button>
                    <input type="number" value="${i.cantidad}" min="0" max="${i.stock}" step="1"
                           onchange="fijarCantidad(${i.id}, this.value)"
                           class="w-12 text-center text-sm font-bold border border-slate-200 rounded-lg py-1 outline-none focus:border-teal-brand">
                    <button type="button" onclick="cambiarCantidad(${i.id}, 1)" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-sm">+</button>
                    <button type="button" onclick="quitar(${i.id})" class="w-7 h-7 rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 text-sm">✕</button>
                </div>
            </div>`).join('');
    }

    function renderTotales() {
        const items = Object.values(carrito);
        const bruto = items.reduce((s, i) => s + i.precio * i.cantidad, 0);
        let desc = parseFloat(document.getElementById('inputDescuento').value) || 0;
        desc = Math.min(desc, bruto);
        const total = bruto - desc;
        const sub = total / (1 + IMPUESTO / 100);
        document.getElementById('lblSubtotal').textContent = fmt(sub);
        document.getElementById('lblImpuesto').textContent = fmt(total - sub);
        document.getElementById('lblTotal').textContent = fmt(total);
        document.getElementById('filaDescuento').style.display = desc > 0 ? '' : 'none';
        document.getElementById('lblDescuento').textContent = '− ' + fmt(desc);
        document.getElementById('btnCobrar').disabled = items.length === 0;
    }

    function renderTodo() { renderGrid(); renderCarrito(); renderTotales(); }

    // ============================================================
    // FUNCIONES DEL MODAL DE PAGO
    // ============================================================
    function abrirModalPago() {
        if (Object.keys(carrito).length === 0) return;

        document.getElementById('modalPago').classList.remove('hidden');
        document.getElementById('conCuantoPaga').value = '';
        document.getElementById('vueltoInfo').classList.add('hidden');
        document.getElementById('descuentoModal').value = '0';
        document.getElementById('tipoComprobante').value = 'ticket';
        document.getElementById('metodoPago').value = 'efectivo';
        document.getElementById('buscarCliente').value = '';
        document.getElementById('resultadosClientes').classList.add('hidden');
        limpiarCliente();

        toggleVencimientoModal();
        actualizarTotalesModal();
        renderDetalleVenta();

        setTimeout(() => document.getElementById('buscarCliente').focus(), 100);
    }

    function cerrarModalPago() {
        document.getElementById('modalPago').classList.add('hidden');
    }

    function toggleVencimientoModal() {
        const metodo = document.getElementById('metodoPago').value;
        document.getElementById('bloqueVencimientoModal').classList.toggle('hidden', metodo !== 'credito');
    }

    document.getElementById('metodoPago').addEventListener('change', toggleVencimientoModal);

    function actualizarTotalesModal() {
        const items = Object.values(carrito);
        const bruto = items.reduce((s, i) => s + i.precio * i.cantidad, 0);
        let desc = parseFloat(document.getElementById('descuentoModal').value) || 0;
        desc = Math.min(desc, bruto);
        const total = bruto - desc;
        const sub = total / (1 + IMPUESTO / 100);

        document.getElementById('modalSubtotal').textContent = fmt(sub);
        document.getElementById('modalImpuesto').textContent = fmt(total - sub);
        document.getElementById('modalTotal').textContent = fmt(total);

        const filaDesc = document.getElementById('modalFilaDescuento');
        if (desc > 0) {
            filaDesc.style.display = '';
            document.getElementById('modalDescuento').textContent = '− ' + fmt(desc);
        } else {
            filaDesc.style.display = 'none';
        }

        const conCuanto = parseFloat(document.getElementById('conCuantoPaga').value) || 0;
        const vuelto = conCuanto - total;
        const vueltoInfo = document.getElementById('vueltoInfo');
        if (conCuanto > 0 && vuelto >= 0) {
            vueltoInfo.classList.remove('hidden');
            document.getElementById('vueltoCalculado').textContent = fmt(vuelto);
        } else {
            vueltoInfo.classList.add('hidden');
        }
    }

    document.getElementById('descuentoModal').addEventListener('input', actualizarTotalesModal);
    document.getElementById('conCuantoPaga').addEventListener('input', actualizarTotalesModal);

    function renderDetalleVenta() {
        const items = Object.values(carrito);
        const cont = document.getElementById('detalleVenta');
        document.getElementById('cantidadItems').textContent = `(${items.length} productos)`;
        
        if (items.length === 0) {
            cont.innerHTML = '<div class="px-4 py-3 text-sm text-slate-400 text-center">No hay productos</div>';
            return;
        }
        
        cont.innerHTML = items.map(i => `
            <div class="px-4 py-2 flex justify-between text-sm">
                <span class="font-semibold">${i.nombre} <span class="text-slate-400">× ${i.cantidad}</span></span>
                <span class="font-bold">${fmt(i.precio * i.cantidad)}</span>
            </div>
        `).join('');
    }

    // ============================================================
    // BUSCADOR DE CLIENTES
    // ============================================================
    document.getElementById('buscarCliente').addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        const cont = document.getElementById('resultadosClientes');

        if (q.length < 1) {
            cont.classList.add('hidden');
            return;
        }

        const resultados = CLIENTES.filter(c =>
            c.nombre.toLowerCase().includes(q) ||
            (c.numero_documento || '').toLowerCase().includes(q)
        );

        if (resultados.length === 0) {
            cont.innerHTML = '<div class="px-3 py-2 text-sm text-slate-400">No se encontraron clientes</div>';
        } else {
            cont.innerHTML = resultados.slice(0, 10).map(c => `
                <div onclick="seleccionarCliente(${c.id})" class="px-3 py-2 hover:bg-teal-50 cursor-pointer text-sm border-b last:border-0">
                    <span class="font-semibold">${c.nombre}</span>
                    <span class="text-slate-400 text-xs ml-2">${c.numero_documento || ''}</span>
                </div>
            `).join('');
        }
        cont.classList.remove('hidden');
    });

    function seleccionarCliente(id) {
        const cliente = CLIENTES.find(c => c.id === id);
        if (!cliente) return;

        document.getElementById('clienteId').value = id;
        document.getElementById('buscarCliente').value = cliente.nombre;
        document.getElementById('resultadosClientes').classList.add('hidden');

        const info = document.getElementById('clienteInfo');
        info.classList.remove('hidden');
        document.getElementById('clienteNombre').textContent = cliente.nombre;
        document.getElementById('clienteDoc').textContent = cliente.numero_documento || 'Sin documento';
        
        // Actualizar el select del formulario principal
        document.getElementById('selectCliente').value = id;
    }

    function limpiarCliente() {
        document.getElementById('clienteId').value = '';
        document.getElementById('buscarCliente').value = '';
        document.getElementById('clienteInfo').classList.add('hidden');
        document.getElementById('resultadosClientes').classList.add('hidden');
        document.getElementById('selectCliente').value = '';
    }

    document.addEventListener('click', function(e) {
        const cont = document.getElementById('resultadosClientes');
        const input = document.getElementById('buscarCliente');
        if (cont && !cont.contains(e.target) && e.target !== input) {
            cont.classList.add('hidden');
        }
    });

    // ============================================================
    // NUEVO CLIENTE
    // ============================================================
    function abrirNuevoCliente() {
        document.getElementById('modalNuevoCliente').classList.remove('hidden');
        document.getElementById('formCliente').reset();
    }

    function cerrarNuevoCliente() {
        document.getElementById('modalNuevoCliente').classList.add('hidden');
    }

    function guardarCliente(e) {
        e.preventDefault();
        const form = document.getElementById('formCliente');
        const data = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.innerHTML = '⏳ Guardando...';

        fetch('{{ route("clientes.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                CLIENTES.push(data.cliente);
                seleccionarCliente(data.cliente.id);
                cerrarNuevoCliente();
                mostrarNotificacion('✅ Cliente creado exitosamente', 'success');
            } else {
                mostrarNotificacion('❌ ' + data.message, 'error');
            }
        })
        .catch(error => {
            mostrarNotificacion('❌ Error: ' + error.message, 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }

    // ============================================================
    // CONFIRMAR PAGO
    // ============================================================
    function confirmarPago() {
        const items = Object.values(carrito);
        if (items.length === 0) return;

        const clienteId = document.getElementById('clienteId').value || null;
        const metodoPago = document.getElementById('metodoPago').value;

        if (metodoPago === 'credito' && !clienteId) {
            mostrarNotificacion('⚠️ Para ventas a crédito, selecciona un cliente', 'warning');
            return;
        }

        // Actualizar el formulario con los datos del modal
        document.getElementById('inputItems').value = JSON.stringify(items.map(i => ({ 
            id: i.id, 
            cantidad: i.cantidad, 
            precio: i.precio 
        })));
        
        // Actualizar el cliente
        document.getElementById('selectCliente').value = clienteId || '';
        
        // Actualizar el comprobante
        document.getElementById('selectComprobante').value = document.getElementById('tipoComprobante').value;
        
        // Actualizar el método de pago
        document.getElementById('selMetodoPago').value = metodoPago;
        
        // Actualizar el descuento
        document.getElementById('inputDescuento').value = document.getElementById('descuentoModal').value;
        
        // Actualizar fecha de vencimiento
        document.getElementById('inputVencimiento').value = document.getElementById('fechaVencimiento').value;
        
        // Actualizar con cuanto paga
        document.getElementById('inputConCuantoPaga').value = document.getElementById('conCuantoPaga').value || '0';

        // Cerrar modal
        cerrarModalPago();
        
        // Enviar el formulario
        document.getElementById('formVenta').submit();
    }

    // ============================================================
    // EVENTOS Y ATEJOS
    // ============================================================
    buscar.addEventListener('input', renderGrid);
    filtroCat.addEventListener('change', renderGrid);
    document.getElementById('inputDescuento').addEventListener('input', renderTotales);

    buscar.addEventListener('keydown', e => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const q = buscar.value.trim().toLowerCase();
        if (!q) return;
        const p = PRODUCTOS.find(x => (x.codigo_barras || '').toLowerCase() === q || x.codigo.toLowerCase() === q);
        if (p) { agregar(p.id); buscar.value = ''; renderGrid(); }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'F2') { 
            e.preventDefault(); 
            buscar.focus(); 
            buscar.select(); 
        }
        if (e.key === 'F9') { 
            e.preventDefault(); 
            const btn = document.getElementById('btnCobrar'); 
            if (!btn.disabled) abrirModalPago(); 
        }
        if (e.key === 'Escape') {
            cerrarModalPago();
            cerrarNuevoCliente();
        }
    });

    // ============================================================
    // INICIALIZAR
    // ============================================================
    renderTodo();
</script>
@endpush