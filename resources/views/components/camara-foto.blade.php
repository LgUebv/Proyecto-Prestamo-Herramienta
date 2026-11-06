@props([
    'model',                // Propiedad de Livewire que recibe la foto (WithFileUploads)
    'autoIniciar' => false, // Abrir la cámara en cuanto aparece el componente
    'fotoActual' => null,   // URL de la foto ya guardada (al editar)
])

<div wire:ignore
     x-data="{
        modelo: @js($model),
        stream: null,
        activa: false,
        preview: @js($fotoActual),
        error: '',
        subiendo: false,

        init() {
            if (@js($autoIniciar) && ! this.preview) this.iniciar();
        },

        async iniciar() {
            this.error = '';
            if (! navigator.mediaDevices?.getUserMedia) {
                this.error = 'Este navegador no permite usar la cámara aquí (requiere https o localhost). Usa el botón para subir o tomar la foto con el teléfono.';
                return;
            }
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
                    audio: false,
                });
                this.$refs.video.srcObject = this.stream;
                this.activa = true;
            } catch (e) {
                this.error = e.name === 'NotAllowedError'
                    ? 'Se negó el permiso para usar la cámara. Actívalo en el navegador o sube la foto con el botón.'
                    : 'No se encontró una cámara disponible. Puedes subir la foto con el botón.';
            }
        },

        detener() {
            this.stream?.getTracks().forEach(track => track.stop());
            this.stream = null;
            this.activa = false;
        },

        capturar() {
            const video = this.$refs.video;
            const escala = Math.min(1, 640 / video.videoWidth);
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(video.videoWidth * escala);
            canvas.height = Math.round(video.videoHeight * escala);
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(blob => {
                this.detener();
                this.subir(new File([blob], 'foto.jpg', { type: 'image/jpeg' }));
            }, 'image/jpeg', 0.85);
        },

        elegirArchivo(evento) {
            const archivo = evento.target.files[0];
            evento.target.value = '';
            if (! archivo) return;
            this.detener();
            this.subir(archivo);
        },

        subir(archivo) {
            this.error = '';
            this.preview = URL.createObjectURL(archivo);
            this.subiendo = true;
            $wire.upload(this.modelo, archivo,
                () => { this.subiendo = false; },
                () => { this.subiendo = false; this.preview = null; this.error = 'No se pudo subir la foto. Intenta de nuevo.'; },
            );
        },

        repetir() {
            this.preview = null;
            $wire.set(this.modelo, null);
            this.iniciar();
        },

        destroy() {
            this.detener();
        },
     }"
     {{ $attributes->merge(['class' => 'rounded-md border border-gray-200 bg-white p-3']) }}>

    <p class="text-xs font-bold text-gray-700 mb-2">Fotografía del prestatario</p>

    <div class="relative mx-auto w-full max-w-xs aspect-[4/3] overflow-hidden rounded-md bg-gray-100">
        <video x-ref="video" x-show="activa && ! preview" autoplay playsinline muted class="h-full w-full object-cover -scale-x-100"></video>
        <img x-show="preview" :src="preview" alt="Foto del prestatario" class="h-full w-full object-cover">

        <div x-show="! activa && ! preview" class="absolute inset-0 flex items-center justify-center text-gray-400">
            <svg class="size-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/>
            </svg>
        </div>

        <div x-show="subiendo" class="absolute inset-x-0 bottom-0 bg-gray-900/70 py-1 text-center text-xs text-white">Subiendo foto...</div>
    </div>

    <p x-show="error" x-text="error" class="mt-2 text-xs text-red-600"></p>

    <div class="mt-3 flex flex-wrap justify-center gap-2">
        <button type="button" x-show="activa" @click="capturar()" class="px-3 py-1.5 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
            Tomar foto
        </button>
        <button type="button" x-show="! activa && ! preview" @click="iniciar()" class="px-3 py-1.5 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
            Abrir cámara
        </button>
        <button type="button" x-show="preview && ! subiendo" @click="repetir()" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-100">
            Tomar otra
        </button>
        <label class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-100 cursor-pointer">
            Subir / tomar con el teléfono
            <input type="file" accept="image/*" capture="user" class="sr-only" @change="elegirArchivo($event)">
        </label>
    </div>
</div>
