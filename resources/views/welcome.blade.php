<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Préstamo de Herramientas · TSJ</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased font-sans bg-white text-gray-900 flex flex-col min-h-screen">

    {{-- Franja institucional --}}
    <div class="bg-[#3710a0] text-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex justify-between text-xs">
            <span class="hidden sm:inline">Tecnológico Superior de Jalisco · Unidad Académica Lagos de Moreno</span>
            <span class="sm:hidden">TSJ · UA Lagos de Moreno</span>
            <span class="hidden sm:inline text-white/80">Almacén de herramientas</span>
        </div>
    </div>

    {{-- Encabezado --}}
    <header class="border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between gap-4">
            <a href="/" class="flex items-center gap-4 min-w-0">
                <img src="{{ asset('img/tsjLogo.png') }}" alt="Tecnológico Superior de Jalisco" class="h-12 sm:h-14 w-auto shrink-0">
                <span class="hidden md:block h-10 w-px bg-gray-300"></span>
                <span class="hidden md:block leading-tight">
                    <span class="block font-semibold text-gray-900">Sistema de Préstamo de Herramientas</span>
                    <span class="block text-sm text-gray-500">Control de inventario y préstamos del almacén</span>
                </span>
            </a>

            @if (Route::has('login'))
                <nav class="flex items-center gap-4 shrink-0">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-[#3710a0] px-4 py-2 text-sm font-semibold text-white hover:bg-[#2a0c7c] focus:outline-none focus:ring-2 focus:ring-[#3710a0] focus:ring-offset-2">
                            Ir al panel
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-md bg-[#3710a0] px-4 py-2 text-sm font-semibold text-white hover:bg-[#2a0c7c] focus:outline-none focus:ring-2 focus:ring-[#3710a0] focus:ring-offset-2">
                            Iniciar sesión
                        </a>
                    @endauth
                </nav>
            @endif
        </div>
    </header>

    <main class="flex-grow">

        {{-- Presentación --}}
        <section class="bg-gray-50 border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16 grid gap-10 lg:grid-cols-12 lg:items-start">
                <div class="lg:col-span-7">
                    <p class="flex items-center gap-2 text-sm font-medium text-[#3710a0]">
                        <span class="flex gap-0.5" aria-hidden="true">
                            <span class="size-2 bg-[#0090ff]"></span>
                            <span class="size-2 bg-[#ff4c64]"></span>
                            <span class="size-2 bg-[#00ca8d]"></span>
                        </span>
                        Almacén · Uso interno
                    </p>

                    <h1 class="mt-4 text-3xl sm:text-4xl font-semibold tracking-tight text-gray-900">
                        Préstamo de herramientas y equipo de medición
                    </h1>

                    <p class="mt-4 text-lg text-gray-600 max-w-2xl">
                        Plataforma del almacén para registrar los préstamos a alumnos y docentes, controlar las devoluciones
                        y mantener actualizado el inventario de la institución.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-md bg-[#3710a0] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#2a0c7c] focus:outline-none focus:ring-2 focus:ring-[#3710a0] focus:ring-offset-2">
                                Ir al panel
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-md bg-[#3710a0] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#2a0c7c] focus:outline-none focus:ring-2 focus:ring-[#3710a0] focus:ring-offset-2">
                                Iniciar sesión
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-sm font-semibold text-[#3710a0] hover:underline">
                                    Crear cuenta de almacenista
                                </a>
                            @endif
                        @endauth
                    </div>

                    <p class="mt-6 text-sm text-gray-500 max-w-xl">
                        El acceso es exclusivo para el personal del almacén. Si eres alumno o docente, acude a la ventanilla
                        del almacén para solicitar tu préstamo.
                    </p>
                </div>

                {{-- Disponibilidad actual --}}
                <aside class="lg:col-span-5 bg-white border border-gray-200 rounded-md">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="font-semibold text-gray-900">Disponibilidad actual</h2>
                        <p class="text-sm text-gray-500">Herramientas disponibles en el almacén por categoría</p>
                    </div>

                    @if ($categorias->isNotEmpty())
                        <ul class="divide-y divide-gray-100">
                            @foreach ($categorias as $categoria)
                                <li class="px-5 py-4">
                                    <div class="flex items-baseline justify-between gap-4">
                                        <span class="text-sm text-gray-700">{{ $categoria->nombre }}</span>
                                        <span class="text-sm text-gray-500 tabular-nums whitespace-nowrap">
                                            <span class="font-semibold text-gray-900">{{ (int) $categoria->disponibles }}</span> de {{ $categoria->total }}
                                        </span>
                                    </div>
                                    <div class="mt-2 h-1.5 rounded-full bg-[#e4dcf7]">
                                        <div class="h-full rounded-full bg-[#3710a0]"
                                             style="width: {{ $categoria->total > 0 ? $categoria->disponibles * 100 / $categoria->total : 0 }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="px-5 py-4 text-sm text-gray-500">Aún no hay categorías registradas.</p>
                    @endif

                    <p class="px-5 py-3 border-t border-gray-200 text-xs text-gray-500">
                        Actualizado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }} h
                    </p>
                </aside>
            </div>
        </section>

        {{-- Procedimiento --}}
        <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <h2 class="text-xl font-semibold text-gray-900">¿Cómo se solicita un préstamo?</h2>
            <p class="mt-1 text-gray-600">El trámite se realiza en la ventanilla del almacén.</p>

            <ol class="mt-8 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <li class="border-t-2 border-[#3710a0] pt-4">
                    <span class="text-sm font-semibold text-[#3710a0] tabular-nums">Paso 1</span>
                    <h3 class="mt-1 font-semibold text-gray-900">Identificación</h3>
                    <p class="mt-2 text-sm text-gray-600">Proporciona tu número de control al almacenista. El personal docente o administrativo se registra con su departamento.</p>
                </li>
                <li class="border-t-2 border-[#3710a0] pt-4">
                    <span class="text-sm font-semibold text-[#3710a0] tabular-nums">Paso 2</span>
                    <h3 class="mt-1 font-semibold text-gray-900">Registro de salida</h3>
                    <p class="mt-2 text-sm text-gray-600">Se escanea el código de cada herramienta y se anota su estado al momento de la entrega.</p>
                </li>
                <li class="border-t-2 border-[#3710a0] pt-4">
                    <span class="text-sm font-semibold text-[#3710a0] tabular-nums">Paso 3</span>
                    <h3 class="mt-1 font-semibold text-gray-900">Aceptación de términos</h3>
                    <p class="mt-2 text-sm text-gray-600">Confirmas los términos del préstamo y la fecha límite en que debes devolver el material.</p>
                </li>
                <li class="border-t-2 border-[#3710a0] pt-4">
                    <span class="text-sm font-semibold text-[#3710a0] tabular-nums">Paso 4</span>
                    <h3 class="mt-1 font-semibold text-gray-900">Devolución</h3>
                    <p class="mt-2 text-sm text-gray-600">Entrega las herramientas antes de la fecha límite. El almacenista que las recibe queda registrado en el sistema.</p>
                </li>
            </ol>
        </section>

        {{-- Funciones del sistema --}}
        <section class="border-t border-gray-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16 grid gap-8 lg:grid-cols-3">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Funciones para el personal del almacén</h2>
                    <p class="mt-2 text-gray-600">Herramientas de trabajo disponibles al iniciar sesión.</p>
                </div>

                <dl class="lg:col-span-2 grid gap-x-10 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="font-semibold text-gray-900">Inventario con código de barras</dt>
                        <dd class="mt-1 text-sm text-gray-600">Alta de herramientas por lote con códigos por categoría y etiquetas imprimibles.</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-900">Entregas y recepciones</dt>
                        <dd class="mt-1 text-sm text-gray-600">Cada préstamo registra quién entrega y quién recibe el material en el almacén.</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-900">Control de fechas límite</dt>
                        <dd class="mt-1 text-sm text-gray-600">Seguimiento de préstamos activos y aviso de los que ya están vencidos.</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-900">Reportes en PDF y Excel</dt>
                        <dd class="mt-1 text-sm text-gray-600">Reportes de préstamos por periodo y del inventario para revisiones físicas.</dd>
                    </div>
                </dl>
            </div>
        </section>
    </main>

    {{-- Pie de página --}}
    <footer class="bg-gray-50 border-t border-gray-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm text-gray-500">
            <p>Tecnológico Superior de Jalisco · Unidad Académica Lagos de Moreno</p>
            <p>Sistema de Préstamo de Herramientas {{ date('Y') }}</p>
        </div>
    </footer>

</body>
</html>
