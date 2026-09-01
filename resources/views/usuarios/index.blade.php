@extends('layouts.app')

@section('titulo', 'Usuarios y Roles')

@section('contenido')

@include('partials.limite_plan', ['recurso' => 'usuarios'])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">

    <div>
        <h1 class="text-2xl font-extrabold text-navy">
            Usuarios y Roles
        </h1>

        <p class="text-sm text-slate-500">
            {{ $usuarios->count() }}
            {{ $usuarios->count() == 1 ? 'usuario' : 'usuarios' }}
            en {{ auth()->user()->empresa->nombre }}
        </p>
    </div>


    {{-- BOTÓN NUEVO USUARIO --}}
    <button
        type="button"
        onclick="abrirModal()"
        class="inline-flex items-center justify-center gap-2 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition w-full sm:w-fit"
    >

        <svg
            class="w-4 h-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2.5"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 4.5v15m7.5-7.5h-15"
            />
        </svg>

        Nuevo Usuario

    </button>

</div>



{{-- ========================================================= --}}
{{-- LISTADO DE USUARIOS --}}
{{-- ========================================================= --}}

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">

    @foreach ($usuarios as $u)

        @php

            /*
            |--------------------------------------------------------------------------
            | DATOS DEL USUARIO PARA EL MODAL
            |--------------------------------------------------------------------------
            | Separamos el array del onclick para evitar errores de sintaxis
            | con @json() dentro del atributo HTML.
            */

            $datosUsuario = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'rol' => $u->rol,
            ];

        @endphp


        <div
            class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 {{ !$u->activo ? 'opacity-60' : '' }}"
        >

            {{-- INFORMACIÓN --}}
            <div class="flex items-start gap-3">

                {{-- AVATAR --}}
                <div
                    class="w-11 h-11 rounded-full bg-lime-brand text-navy font-extrabold flex items-center justify-center text-lg shrink-0"
                >
                    {{ strtoupper(substr($u->name, 0, 1)) }}
                </div>


                {{-- DATOS --}}
                <div class="flex-1 min-w-0">

                    <p class="font-bold text-slate-700 truncate">

                        {{ $u->name }}

                        @if ($u->id === auth()->id())

                            <span class="text-[10px] text-teal-brand font-bold">
                                (tú)
                            </span>

                        @endif

                    </p>


                    <p class="text-xs text-slate-400 truncate">
                        {{ $u->email }}
                    </p>


                    {{-- ROL Y ESTADO --}}
                    <div class="flex items-center gap-2 mt-2">

                        {{-- ROL --}}
                        <span
                            class="text-[11px] font-bold px-2 py-0.5 rounded-full capitalize
                            @if($u->rol === 'admin')
                                bg-navy text-white
                            @elseif($u->rol === 'vendedor')
                                bg-teal-brand/10 text-teal-dark
                            @elseif($u->rol === 'almacenero')
                                bg-amber-100 text-amber-700
                            @else
                                bg-slate-100 text-slate-600
                            @endif"
                        >
                            {{ $u->rol }}
                        </span>


                        {{-- ESTADO --}}
                        <span
                            class="text-[11px] font-bold px-2 py-0.5 rounded-full
                            {{ $u->activo
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-red-100 text-red-600' }}"
                        >
                            {{ $u->activo ? 'Activo' : 'Inactivo' }}
                        </span>

                    </div>

                </div>

            </div>



            {{-- ================================================= --}}
            {{-- BOTONES --}}
            {{-- ================================================= --}}

            <div class="flex gap-2 mt-4 pt-4 border-t border-slate-100">


                {{-- EDITAR --}}
                <button
                    type="button"
                    onclick='abrirModal(@json($datosUsuario))'
                    class="flex-1 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 px-3 py-2 rounded-lg transition"
                >
                    Editar
                </button>



                {{-- ACTIVAR / DESACTIVAR --}}
                @if ($u->id !== auth()->id())

                    <form
                        method="POST"
                        action="{{ route('usuarios.alternar', $u) }}"
                        class="flex-1"
                        onsubmit="return confirm('¿{{ $u->activo ? 'Desactivar' : 'Activar' }} a {{ $u->name }}?')"
                    >

                        @csrf

                        @method('PATCH')


                        <button
                            type="submit"
                            class="w-full text-xs font-bold px-3 py-2 rounded-lg transition
                            {{ $u->activo
                                ? 'text-red-600 bg-red-50 hover:bg-red-100'
                                : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}"
                        >

                            {{ $u->activo ? 'Desactivar' : 'Activar' }}

                        </button>

                    </form>

                @endif

            </div>

        </div>

    @endforeach

</div>



{{-- ========================================================= --}}
{{-- ROLES INFORMATIVOS --}}
{{-- ========================================================= --}}

<div class="mt-6 bg-white rounded-xl border border-slate-200 p-5">

    <h3 class="font-bold text-navy text-sm mb-3">
        Permisos por rol
    </h3>


    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs text-slate-500">

        <div class="bg-slate-50 rounded-lg p-3">

            <b class="text-navy block mb-1">
                Admin
            </b>

            Acceso total: usuarios, configuración, reportes y todos los módulos.

        </div>


        <div class="bg-slate-50 rounded-lg p-3">

            <b class="text-teal-dark block mb-1">
                Vendedor
            </b>

            POS, ventas, cotizaciones, clientes y consulta de productos.

        </div>


        <div class="bg-slate-50 rounded-lg p-3">

            <b class="text-amber-600 block mb-1">
                Almacenero
            </b>

            Productos, inventario, kardex, compras y proveedores.

        </div>


        <div class="bg-slate-50 rounded-lg p-3">

            <b class="text-slate-600 block mb-1">
                Cajero
            </b>

            POS, caja (apertura/cierre) y consulta de ventas.

        </div>

    </div>

</div>



{{-- ========================================================= --}}
{{-- MODAL --}}
{{-- ========================================================= --}}

<div
    id="modalUsuario"
    class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
>

    {{-- FONDO --}}
    <div
        class="absolute inset-0 bg-black/50"
        onclick="cerrarModal()"
    ></div>


    {{-- CONTENIDO --}}
    <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6"
    >

        <h2
            id="tituloModal"
            class="text-lg font-extrabold text-navy mb-4"
        >
            Nuevo Usuario
        </h2>


        {{-- FORMULARIO --}}
        <form
            method="POST"
            id="formUsuario"
            action="{{ route('usuarios.store') }}"
            class="space-y-4"
        >

            @csrf

            <input
                type="hidden"
                name="_method"
                id="metodoForm"
                value="POST"
            >


            {{-- NOMBRE --}}
            <div>

                <label
                    class="block text-sm font-semibold text-slate-700 mb-1.5"
                >
                    Nombre completo
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="name"
                    id="f_name"
                    value="{{ old('name') }}"
                    required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none"
                >

            </div>


            {{-- EMAIL --}}
            <div>

                <label
                    class="block text-sm font-semibold text-slate-700 mb-1.5"
                >
                    Correo electrónico
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="email"
                    name="email"
                    id="f_email"
                    value="{{ old('email') }}"
                    required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none"
                >

            </div>


            {{-- ROL + CONTRASEÑA --}}
            <div class="grid grid-cols-2 gap-3">

                {{-- ROL --}}
                <div>

                    <label
                        class="block text-sm font-semibold text-slate-700 mb-1.5"
                    >
                        Rol
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        name="rol"
                        id="f_rol"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none"
                    >

                        <option value="admin">
                            Administrador
                        </option>

                        <option value="vendedor">
                            Vendedor
                        </option>

                        <option value="almacenero">
                            Almacenero
                        </option>

                        <option value="cajero">
                            Cajero
                        </option>

                    </select>

                </div>


                {{-- CONTRASEÑA --}}
                <div>

                    <label
                        class="block text-sm font-semibold text-slate-700 mb-1.5"
                    >
                        Contraseña

                        <span
                            id="passReq"
                            class="text-red-500"
                        >
                            *
                        </span>

                    </label>


                    <input
                        type="password"
                        name="password"
                        id="f_password"
                        minlength="6"
                        placeholder="Mín. 6 caracteres"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none"
                    >


                    <p
                        id="passHint"
                        class="hidden text-[10px] text-slate-400 mt-1"
                    >
                        Déjala vacía para no cambiarla.
                    </p>

                </div>

            </div>


            {{-- ERRORES --}}
            @if ($errors->any())

                <div class="bg-red-50 border border-red-200 rounded-lg p-3">

                    <p class="text-xs font-bold text-red-700 mb-1">
                        Revisa los siguientes errores:
                    </p>

                    <ul class="text-xs text-red-600 list-disc list-inside">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- BOTONES --}}
            <div class="flex gap-3 pt-2">

                <button
                    type="button"
                    onclick="cerrarModal()"
                    class="flex-1 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-600 text-sm font-bold hover:bg-slate-50 transition"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="flex-1 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2.5 rounded-lg transition"
                >
                    Guardar
                </button>

            </div>

        </form>

    </div>

</div>



{{-- ========================================================= --}}
{{-- JAVASCRIPT --}}
{{-- ========================================================= --}}

@push('scripts')

<script>

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */

    const URL_STORE = @json(route('usuarios.store'));

    const URL_UPDATE = @json(url('/usuarios')) + '/';



    /*
    |--------------------------------------------------------------------------
    | ABRIR MODAL
    |--------------------------------------------------------------------------
    */

    function abrirModal(u = null) {

        const titulo = document.getElementById('tituloModal');

        const metodo = document.getElementById('metodoForm');

        const form = document.getElementById('formUsuario');

        const nombre = document.getElementById('f_name');

        const email = document.getElementById('f_email');

        const rol = document.getElementById('f_rol');

        const password = document.getElementById('f_password');

        const passReq = document.getElementById('passReq');

        const passHint = document.getElementById('passHint');

        const modal = document.getElementById('modalUsuario');



        /*
        |--------------------------------------------------------------------------
        | TÍTULO
        |--------------------------------------------------------------------------
        */

        titulo.textContent = u
            ? 'Editar Usuario'
            : 'Nuevo Usuario';



        /*
        |--------------------------------------------------------------------------
        | MÉTODO
        |--------------------------------------------------------------------------
        */

        metodo.value = u
            ? 'PUT'
            : 'POST';



        /*
        |--------------------------------------------------------------------------
        | ACTION
        |--------------------------------------------------------------------------
        */

        form.action = u
            ? URL_UPDATE + u.id
            : URL_STORE;



        /*
        |--------------------------------------------------------------------------
        | DATOS
        |--------------------------------------------------------------------------
        */

        nombre.value = u
            ? (u.name ?? '')
            : '';

        email.value = u
            ? (u.email ?? '')
            : '';

        rol.value = u
            ? (u.rol ?? 'vendedor')
            : 'vendedor';



        /*
        |--------------------------------------------------------------------------
        | CONTRASEÑA
        |--------------------------------------------------------------------------
        */

        password.value = '';

        password.required = !u;


        if (u) {

            passReq.style.display = 'none';

            passHint.classList.remove('hidden');

        } else {

            passReq.style.display = '';

            passHint.classList.add('hidden');

        }



        /*
        |--------------------------------------------------------------------------
        | MOSTRAR MODAL
        |--------------------------------------------------------------------------
        */

        modal.classList.remove('hidden');



        /*
        |--------------------------------------------------------------------------
        | FOCUS
        |--------------------------------------------------------------------------
        */

        setTimeout(function () {

            nombre.focus();

        }, 100);

    }



    /*
    |--------------------------------------------------------------------------
    | CERRAR MODAL
    |--------------------------------------------------------------------------
    */

    function cerrarModal() {

        const modal = document.getElementById('modalUsuario');

        if (modal) {

            modal.classList.add('hidden');

        }

    }



    /*
    |--------------------------------------------------------------------------
    | ESC PARA CERRAR
    |--------------------------------------------------------------------------
    */

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {

            cerrarModal();

        }

    });



    /*
    |--------------------------------------------------------------------------
    | SI HAY ERRORES DE VALIDACIÓN
    |--------------------------------------------------------------------------
    */

    @if ($errors->any())

        abrirModal();

    @endif

</script>

@endpush

@endsection