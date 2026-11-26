<?php

namespace Tests\Feature;

use App\Livewire\AdminAlmacenistas;
use App\Livewire\AdminCategorias;
use App\Livewire\AdminHerramientas;
use App\Livewire\AdminInventario;
use App\Livewire\AdminPrestamos;
use App\Livewire\AdminUsuarios;
use App\Livewire\NuevoPrestamo;
use App\Models\Almacenista;
use App\Models\Categoria;
use App\Models\Herramienta;
use App\Models\Prestamo;
use App\Models\Prestatario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pruebas de los flujos principales del sistema de préstamo.
 * Cada prueba describe el comportamiento esperado; si falla, es un error a revisar.
 */
class SistemaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Almacenista $almacenista;
    private Categoria $categoria;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->user = User::factory()->create();
        $this->almacenista = Almacenista::create([
            'nombre' => 'Angel', 'tipo' => 'fijo', 'user_id' => $this->user->id, 'estado' => 'activo',
        ]);
        // La migración crea las categorías base (Herramienta Manual = MAN)
        $this->categoria = Categoria::where('prefijo', 'MAN')->firstOrFail();
        $this->actingAs($this->user);
    }

    private function herramienta(string $codigo, string $disp = 'disponible'): Herramienta
    {
        return Herramienta::create([
            'codigo_barras' => $codigo, 'nombre' => 'Martillo', 'marca' => 'Truper',
            'categoria_id' => $this->categoria->id, 'disponibilidad' => $disp,
        ]);
    }

    private function prestatario(string $control = '211101234', string $estado = 'activo'): Prestatario
    {
        return Prestatario::create([
            'numero_control' => $control, 'nombre_completo' => 'Ana López',
            'tipo_usuario' => 'Alumno', 'estado' => $estado,
        ]);
    }

    private function prestar(Prestatario $p, Herramienta ...$hs)
    {
        $c = Livewire::test(NuevoPrestamo::class)
            ->set('numeroControl', $p->numero_control)->call('buscarPrestatario');
        foreach ($hs as $h) {
            $c->set('codigoBarras', $h->codigo_barras)->call('agregarAlCarrito');
        }

        return $c->set('materia', 'Taller')->set('aceptoTerminos', true)
            ->set('almacenistaAutorizaId', $this->almacenista->id)
            ->call('confirmarPrestamo');
    }

    // ---------- Pantallas ----------

    public function test_todas_las_pantallas_cargan(): void
    {
        $this->herramienta('MAN-1');
        $this->prestar($this->prestatario(), Herramienta::first());

        foreach (['/', '/dashboard', '/admin/prestamos', '/admin/prestamos/nuevo',
                  '/admin/herramientas', '/admin/usuarios', '/admin/almacenistas', '/admin/inventario', '/admin/categorias'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_invitado_no_entra_al_panel_ni_a_fotos(): void
    {
        $p = $this->prestatario();
        $p->update(['foto' => 'prestatarios/x.jpg']);
        Storage::disk('local')->put('prestatarios/x.jpg', 'x');
        auth()->logout();

        $this->get('/admin/prestamos')->assertRedirect('/login');
        $this->get(route('prestatarios.foto', $p))->assertRedirect('/login');
    }

    // ---------- Préstamos ----------

    public function test_prestamo_a_usuario_existente(): void
    {
        $h = $this->herramienta('MAN-1');
        $this->prestar($this->prestatario(), $h)->assertSet('mensajeExito', '¡Préstamo guardado y firmado correctamente!');

        $this->assertSame('prestada', $h->fresh()->disponibilidad);
        $this->assertDatabaseHas('prestamo_detalles', ['herramienta_id' => $h->id, 'entregado_por_id' => $this->user->id]);
    }

    public function test_usuario_nuevo_requiere_foto(): void
    {
        $h = $this->herramienta('MAN-1');
        $c = Livewire::test(NuevoPrestamo::class)
            ->set('numeroControl', '229990001')->call('buscarPrestatario')
            ->assertSet('esNuevoRegistro', true)
            ->set('nombreCompleto', 'Nuevo Alumno')
            ->set('codigoBarras', 'MAN-1')->call('agregarAlCarrito')
            ->set('materia', 'Taller')->set('aceptoTerminos', true)
            ->call('confirmarPrestamo')
            ->assertSet('mensajeError', 'Toma la fotografía del nuevo usuario antes de continuar.');

        $c->set('foto', UploadedFile::fake()->image('foto.jpg'))->call('confirmarPrestamo')
            ->assertSet('mensajeExito', '¡Préstamo guardado y firmado correctamente!');
        Storage::disk('local')->assertExists(Prestatario::where('numero_control', '229990001')->value('foto'));
    }

    public function test_usuario_suspendido_no_puede_pedir(): void
    {
        $this->prestatario('211101234', 'suspendido');
        Livewire::test(NuevoPrestamo::class)
            ->set('numeroControl', '211101234')->call('buscarPrestatario')
            ->assertSet('prestatario', null);
    }

    public function test_numero_de_control_solo_acepta_digitos(): void
    {
        Livewire::test(NuevoPrestamo::class)
            ->set('numeroControl', 'abcdefghi')->call('buscarPrestatario')
            ->assertSet('esNuevoRegistro', false);
    }

    public function test_numero_de_control_no_acepta_prefijo_t(): void
    {
        foreach (['T-abcdefg', 't-abcdefg', 'T-12345'] as $numero) {
            Livewire::test(NuevoPrestamo::class)
                ->set('numeroControl', $numero)->call('buscarPrestatario')
                ->assertSet('esNuevoRegistro', false)
                ->assertSet('prestatario', null);
        }
    }

    public function test_trabajador_externo_recibe_numero_ext_solo_con_digitos(): void
    {
        $this->herramienta('MAN-1');
        Livewire::test(NuevoPrestamo::class)
            ->set('es_trabajador', true)->set('nombre_trabajador', 'Juan Pérez')
            ->set('codigoBarras', 'MAN-1')->call('agregarAlCarrito')
            ->set('materia', 'Mantenimiento')->set('aceptoTerminos', true)
            ->set('almacenistaAutorizaId', $this->almacenista->id)
            ->call('confirmarPrestamo')
            ->assertSet('mensajeExito', '¡Préstamo guardado y firmado correctamente!');

        $this->assertMatchesRegularExpression('/^EXT-\d+$/', Prestatario::where('nombre_completo', 'Juan Pérez')->value('numero_control'));
    }

    public function test_trabajador_externo_registrado_se_busca_con_ext(): void
    {
        Prestatario::create([
            'numero_control' => 'EXT-1777143320', 'nombre_completo' => 'Juan Pérez',
            'tipo_usuario' => 'Trabajador Externo', 'estado' => 'activo',
        ]);

        Livewire::test(NuevoPrestamo::class)
            ->set('numeroControl', 'ext-1777143320')->call('buscarPrestatario')
            ->assertSet('nombreCompleto', 'Juan Pérez')
            ->assertSet('esNuevoRegistro', false);

        // Un EXT- que no existe no crea un registro nuevo
        Livewire::test(NuevoPrestamo::class)
            ->set('numeroControl', 'EXT-999')->call('buscarPrestatario')
            ->assertSet('esNuevoRegistro', false)
            ->assertSet('prestatario', null);

        // EXT con letras no es válido
        Livewire::test(NuevoPrestamo::class)
            ->set('numeroControl', 'EXT-abc')->call('buscarPrestatario')
            ->assertSet('esNuevoRegistro', false);
    }

    public function test_herramienta_prestada_no_se_agrega(): void
    {
        $this->herramienta('MAN-1', 'prestada');
        Livewire::test(NuevoPrestamo::class)
            ->set('codigoBarras', 'MAN-1')->call('agregarAlCarrito')
            ->assertSet('carrito', []);
    }

    public function test_no_se_presta_dos_veces_la_misma_herramienta_a_la_vez(): void
    {
        // Dos almacenistas agregan la misma herramienta a su lista antes de que cualquiera confirme
        $h = $this->herramienta('MAN-1');
        $a = $this->prestatario('211100001');
        $b = $this->prestatario('211100002');

        $pantallaB = Livewire::test(NuevoPrestamo::class)
            ->set('numeroControl', $b->numero_control)->call('buscarPrestatario')
            ->set('codigoBarras', 'MAN-1')->call('agregarAlCarrito');

        $this->prestar($a, $h);

        $pantallaB->set('materia', 'Taller')->set('aceptoTerminos', true)
            ->set('almacenistaAutorizaId', $this->almacenista->id)
            ->call('confirmarPrestamo');

        $this->assertSame(1, \App\Models\PrestamoDetalles::where('herramienta_id', $h->id)->count(),
            'La misma herramienta quedó prestada en dos préstamos activos.');
    }

    public function test_almacenista_inactivo_no_autoriza(): void
    {
        $this->almacenista->update(['estado' => 'inactivo']);
        $h = $this->herramienta('MAN-1');
        $this->prestar($this->prestatario(), $h)
            ->assertSet('mensajeError', 'Selecciona al almacenista que autoriza el préstamo.');
    }

    // ---------- Devoluciones ----------

    public function test_devolucion_libera_herramientas(): void
    {
        $h = $this->herramienta('MAN-1');
        $this->prestar($this->prestatario(), $h);
        $prestamo = Prestamo::first();

        Livewire::test(AdminPrestamos::class)
            ->call('abrirModalDevolucion', $prestamo->id)
            ->set('almacenista_recibe_id', $this->almacenista->id)
            ->call('confirmarDevolucion')
            ->assertSet('mensajeExito', '¡Herramientas devueltas y firmadas exitosamente!');

        $this->assertSame('devuelto', $prestamo->fresh()->estado_prestamo);
        $this->assertSame('disponible', $h->fresh()->disponibilidad);
    }

    public function test_devolver_dos_veces_no_libera_herramienta_prestada_de_nuevo(): void
    {
        $h = $this->herramienta('MAN-1');
        $this->prestar($this->prestatario('211100001'), $h);
        $primero = Prestamo::first();

        $devolver = fn ($id) => Livewire::test(AdminPrestamos::class)
            ->call('abrirModalDevolucion', $id)
            ->set('almacenista_recibe_id', $this->almacenista->id)
            ->call('confirmarDevolucion');

        $devolver($primero->id);
        $this->prestar($this->prestatario('211100002'), $h->fresh()); // se vuelve a prestar
        $devolver($primero->id); // alguien confirma otra vez el préstamo ya devuelto

        $this->assertSame('prestada', $h->fresh()->disponibilidad,
            'Volver a devolver un préstamo ya devuelto liberó una herramienta que sigue prestada.');
    }

    private function devolver(Prestamo $prestamo, array $danadas = []): void
    {
        $c = Livewire::test(AdminPrestamos::class)
            ->call('abrirModalDevolucion', $prestamo->id)
            ->set('almacenista_recibe_id', $this->almacenista->id);
        foreach ($danadas as $detalleId => $nota) {
            $c->set("estadoHerramientas.$detalleId.danada", '1')->set("estadoHerramientas.$detalleId.nota", $nota);
        }
        $c->call('confirmarDevolucion')->assertHasNoErrors();
    }

    public function test_devolucion_con_herramienta_danada_va_a_mantenimiento(): void
    {
        $buena = $this->herramienta('MAN-1');
        $rota = $this->herramienta('MAN-2');
        $this->prestar($this->prestatario(), $buena, $rota);
        $prestamo = Prestamo::with('detalles')->first();
        $detalleRota = $prestamo->detalles->firstWhere('herramienta_id', $rota->id);

        // Marcada como dañada sin describir el daño: no se acepta
        Livewire::test(AdminPrestamos::class)
            ->call('abrirModalDevolucion', $prestamo->id)
            ->set('almacenista_recibe_id', $this->almacenista->id)
            ->assertSee('Buen estado')->assertDontSee('Describe el daño')
            ->set("estadoHerramientas.{$detalleRota->id}.danada", '1')
            ->assertSee('Describe el daño')
            ->call('confirmarDevolucion')
            ->assertHasErrors("estadoHerramientas.{$detalleRota->id}.nota");
        $this->assertSame('activo', $prestamo->fresh()->estado_prestamo);

        $this->devolver($prestamo, [$detalleRota->id => 'Mango roto']);

        $this->assertSame('disponible', $buena->fresh()->disponibilidad);
        $this->assertSame('mantenimiento', $rota->fresh()->disponibilidad);
        $this->assertSame('Dañada: Mango roto', $detalleRota->fresh()->condicion_devolucion);
        $this->assertSame('Buen estado', $prestamo->detalles->firstWhere('herramienta_id', $buena->id)->fresh()->condicion_devolucion);
    }

    public function test_reportes_muestran_herramientas_danadas(): void
    {
        $buena = $this->herramienta('MAN-1');
        $rota = $this->herramienta('MAN-2');
        $this->prestar($this->prestatario(), $buena, $rota);
        $prestamo = Prestamo::with('detalles')->first();
        $this->devolver($prestamo, [$prestamo->detalles->firstWhere('herramienta_id', $rota->id)->id => 'Mango roto']);

        // Excel: columna "Herramientas Dañadas"
        $export = new \App\Exports\PrestamosExport(null, null);
        $columnas = $export->headings();
        $fila = $export->map($export->query()->first());
        $this->assertSame('Martillo (MAN-2): Mango roto', $fila[array_search('Herramientas Dañadas', $columnas)]);

        // PDF: marca y descripción del daño
        $html = view('reportes.prestamos_pdf', ['data' => Prestamo::with('detalles.herramienta')->get()])->render();
        $this->assertStringContainsString('[DAÑADA]', $html);
        $this->assertStringContainsString('Mango roto', $html);
    }

    public function test_corregir_devolucion_manda_a_mantenimiento_sin_reabrir(): void
    {
        $buena = $this->herramienta('MAN-1');
        $rota = $this->herramienta('MAN-2');
        $this->prestar($this->prestatario(), $buena, $rota);
        $prestamo = Prestamo::with('detalles')->first();
        $this->devolver($prestamo);
        $detalleRota = $prestamo->detalles->firstWhere('herramienta_id', $rota->id);

        Livewire::test(AdminPrestamos::class)
            ->call('abrirModalCorreccion', $prestamo->id)
            ->assertSee('Corregir Devolución')->assertSee('Reabrir el préstamo')
            ->set("estadoHerramientas.{$detalleRota->id}.danada", '1')
            ->set("estadoHerramientas.{$detalleRota->id}.nota", 'No enciende')
            ->call('guardarCorreccion')
            ->assertSet('mensajeError', '');

        $this->assertSame('devuelto', $prestamo->fresh()->estado_prestamo);
        $this->assertSame('mantenimiento', $rota->fresh()->disponibilidad);
        $this->assertSame('disponible', $buena->fresh()->disponibilidad);
        $this->assertSame('Dañada: No enciende', $detalleRota->fresh()->condicion_devolucion);

        // La tabla de préstamos muestra la marca de dañada con la descripción
        Livewire::test(AdminPrestamos::class)->assertSee('Corregir devolución')->assertSee('No enciende');
    }

    public function test_corregir_devolucion_reabriendo_el_prestamo(): void
    {
        $buena = $this->herramienta('MAN-1');
        $rota = $this->herramienta('MAN-2');
        $this->prestar($this->prestatario(), $buena, $rota);
        $prestamo = Prestamo::with('detalles')->first();
        $this->devolver($prestamo);
        $detalleRota = $prestamo->detalles->firstWhere('herramienta_id', $rota->id);

        Livewire::test(AdminPrestamos::class)
            ->call('abrirModalCorreccion', $prestamo->id)
            ->set("estadoHerramientas.{$detalleRota->id}.danada", '1')
            ->set("estadoHerramientas.{$detalleRota->id}.nota", 'Disco roto')
            ->set('reabrirPrestamo', true)
            ->call('guardarCorreccion');

        $prestamo->refresh();
        $this->assertSame('activo', $prestamo->estado_prestamo);
        $this->assertNull($prestamo->fecha_devolucion_real);
        $this->assertSame('prestada', $buena->fresh()->disponibilidad);
        $this->assertSame('mantenimiento', $rota->fresh()->disponibilidad);

        // Al volver a devolverlo, la dañada sigue marcada y no regresa a disponible
        Livewire::test(AdminPrestamos::class)
            ->call('abrirModalDevolucion', $prestamo->id)
            ->assertSet("estadoHerramientas.{$detalleRota->id}.danada", '1')
            ->assertSet("estadoHerramientas.{$detalleRota->id}.nota", 'Disco roto')
            ->set('almacenista_recibe_id', $this->almacenista->id)
            ->call('confirmarDevolucion');

        $this->assertSame('mantenimiento', $rota->fresh()->disponibilidad);
        $this->assertSame('disponible', $buena->fresh()->disponibilidad);
    }

    public function test_no_se_corrige_si_la_herramienta_ya_salio_en_otro_prestamo(): void
    {
        $h = $this->herramienta('MAN-1');
        $this->prestar($this->prestatario('211100001'), $h);
        $primero = Prestamo::first();
        $this->devolver($primero);
        $this->prestar($this->prestatario('211100002'), $h->fresh());

        Livewire::test(AdminPrestamos::class)
            ->call('abrirModalCorreccion', $primero->id)
            ->set('reabrirPrestamo', true)
            ->call('guardarCorreccion');

        $this->assertSame('devuelto', $primero->fresh()->estado_prestamo);
        $this->assertSame('prestada', $h->fresh()->disponibilidad);
    }

    public function test_solo_se_corrigen_prestamos_devueltos(): void
    {
        $this->prestar($this->prestatario(), $this->herramienta('MAN-1'));
        Livewire::test(AdminPrestamos::class)
            ->call('abrirModalCorreccion', Prestamo::first()->id)
            ->assertSet('modalCorreccionAbierto', false)
            ->assertSet('mensajeError', 'Este préstamo no está marcado como devuelto.');
    }

    // ---------- Categorías ----------

    public function test_categorias_base_existen(): void
    {
        $this->assertEqualsCanonicalizing(['MAN', 'POD', 'MED'], Categoria::pluck('prefijo')->all());
    }

    public function test_categoria_nueva_define_prefijo_de_codigos(): void
    {
        Livewire::test(AdminCategorias::class)->call('abrirModal')
            ->set('nombre', 'Herramienta Eléctrica')->set('prefijo', 'ele')
            ->call('guardar')->assertHasNoErrors();

        $cat = Categoria::where('nombre', 'Herramienta Eléctrica')->first();
        $this->assertSame('ELE', $cat->prefijo);

        Livewire::test(AdminHerramientas::class)->call('abrirModal')
            ->set('nombre', 'Multímetro')->set('cantidad', 2)->set('categoria_id', $cat->id)
            ->call('guardar');

        $this->assertEqualsCanonicalizing(['ELE-1', 'ELE-2'], Herramienta::pluck('codigo_barras')->all());
    }

    public function test_categoria_valida_nombre_y_prefijo(): void
    {
        Livewire::test(AdminCategorias::class)->call('abrirModal')
            ->set('nombre', 'Medición')->set('prefijo', 'MAN')
            ->call('guardar')->assertHasErrors(['nombre', 'prefijo']);

        Livewire::test(AdminCategorias::class)->call('abrirModal')
            ->set('nombre', 'Otra')->set('prefijo', 'A1')
            ->call('guardar')->assertHasErrors('prefijo');

        // Editar conservando su propio nombre y prefijo sí se permite
        Livewire::test(AdminCategorias::class)->call('editar', $this->categoria->id)
            ->set('descripcion', 'Martillos y desarmadores')
            ->call('guardar')->assertHasNoErrors();
    }

    public function test_categoria_con_herramientas_no_se_borra(): void
    {
        $this->herramienta('MAN-1');
        Livewire::test(AdminCategorias::class)->call('borrar', $this->categoria->id);
        $this->assertNotNull($this->categoria->fresh());

        $vacia = Categoria::where('prefijo', 'MED')->first();
        Livewire::test(AdminCategorias::class)->call('borrar', $vacia->id);
        $this->assertNull($vacia->fresh());
    }

    // ---------- Herramientas ----------

    public function test_alta_por_lote_genera_codigos_consecutivos(): void
    {
        Livewire::test(AdminHerramientas::class)
            ->call('abrirModal')
            ->set('nombre', 'Desarmador')->set('cantidad', 3)->set('categoria_id', $this->categoria->id)
            ->call('guardar');

        $this->assertEqualsCanonicalizing(['MAN-1', 'MAN-2', 'MAN-3'], Herramienta::pluck('codigo_barras')->all());
    }

    public function test_borrar_herramienta_con_historial_no_truena(): void
    {
        $h = $this->herramienta('MAN-1');
        $this->prestar($this->prestatario(), $h);

        Livewire::test(AdminHerramientas::class)->call('borrar', $h->id)->assertOk();
        $this->assertNotNull($h->fresh(), 'Se borró una herramienta con historial de préstamos.');
    }

    public function test_etiqueta_muestra_codigo_de_barras(): void
    {
        $h = $this->herramienta('MAN-1');
        Livewire::test(AdminHerramientas::class)
            ->call('mostrarQr', $h->id)
            ->assertSee('Imprimir Etiqueta')
            ->assertSee('data:image/png;base64', false);
    }

    // ---------- Usuarios ----------

    public function test_usuario_numero_de_control_duplicado_muestra_error(): void
    {
        $this->prestatario('211101234');
        Livewire::test(AdminUsuarios::class)
            ->call('abrirModal')
            ->set('numero_control', '211101234')->set('nombre_completo', 'Otra persona')
            ->set('foto', UploadedFile::fake()->image('f.jpg'))
            ->call('guardar')
            ->assertHasErrors('numero_control');
    }

    public function test_varios_trabajadores_sin_numero_de_control(): void
    {
        foreach (['Juan Pérez', 'Luis Gómez'] as $nombre) {
            Livewire::test(AdminUsuarios::class)
                ->call('abrirModal')
                ->set('numero_control', 'NO HAY')->set('nombre_completo', $nombre)->set('tipo_usuario', 'Trabajador')
                ->set('foto', UploadedFile::fake()->image('f.jpg'))
                ->call('guardar')
                ->assertHasNoErrors();
        }

        $numeros = Prestatario::pluck('numero_control');
        $this->assertCount(2, $numeros->unique());
        $this->assertTrue($numeros->every(fn ($n) => str_starts_with($n, 'EXT-')));

        // Al editar y dejar "NO HAY", conserva su número automático
        $juan = Prestatario::where('nombre_completo', 'Juan Pérez')->first();
        Livewire::test(AdminUsuarios::class)
            ->call('editar', $juan->id)->set('numero_control', 'NO HAY')->call('guardar')->assertHasNoErrors();
        $this->assertSame($juan->numero_control, $juan->fresh()->numero_control);
    }

    public function test_usuario_sin_nombre_muestra_error(): void
    {
        Livewire::test(AdminUsuarios::class)
            ->call('abrirModal')
            ->set('numero_control', '211109999')
            ->set('foto', UploadedFile::fake()->image('f.jpg'))
            ->call('guardar')
            ->assertHasErrors('nombre_completo');
    }

    public function test_borrar_usuario_con_prestamos_lo_inactiva(): void
    {
        $p = $this->prestatario();
        $this->prestar($p, $this->herramienta('MAN-1'));
        Livewire::test(AdminUsuarios::class)->call('borrar', $p->id);
        $this->assertSame('inactivo', $p->fresh()->estado);
    }

    // ---------- Almacenistas ----------

    public function test_almacenista_fijo_crea_cuenta_y_temporal_no(): void
    {
        Livewire::test(AdminAlmacenistas::class)->call('abrirModal')
            ->set('nombre', 'Pepe')->set('tipo', 'fijo')
            ->set('email_usuario', 'pepe@almacen.com')->set('password', 'secreto123')
            ->call('guardar')->assertHasNoErrors();
        Livewire::test(AdminAlmacenistas::class)->call('abrirModal')
            ->set('nombre', 'Servicio Social')->set('tipo', 'temporal')
            ->call('guardar')->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'pepe@almacen.com']);
        $this->assertNotNull(Almacenista::where('nombre', 'Pepe')->value('user_id'));
    }

    public function test_almacenista_inactivo_no_puede_iniciar_sesion(): void
    {
        auth()->logout();
        $this->almacenista->update(['estado' => 'inactivo']);
        $this->post('/login', ['email' => $this->user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // ---------- Reportes ----------

    public function test_reportes_se_descargan(): void
    {
        $this->prestar($this->prestatario(), $this->herramienta('MAN-1'));

        Livewire::test(AdminPrestamos::class)->call('exportarExcel')->assertFileDownloaded();
        Livewire::test(AdminPrestamos::class)->call('exportarPdf')->assertFileDownloaded();
        Livewire::test(AdminInventario::class)->call('exportarExcel')->assertFileDownloaded();
        Livewire::test(AdminInventario::class)->call('exportarPdf')->assertFileDownloaded();
    }

}
