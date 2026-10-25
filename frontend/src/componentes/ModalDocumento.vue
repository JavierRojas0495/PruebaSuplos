<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { limitarTexto, validarDocumento } from '../validaciones/oferta'

const abierto = defineModel('abierto', { type: Boolean, default: false })
const emit = defineEmits(['agregar'])

const form = reactive({ titulo: '', descripcion: '', archivo: null })
const intento = ref(false)
const errores = computed(() => validarDocumento(form))
const valido = computed(() => Object.keys(errores.value).length === 0)
const tituloInput = ref(null)

watch(abierto, (estaAbierto) => {
  if (!estaAbierto) {
    return
  }
  form.titulo = ''
  form.descripcion = ''
  form.archivo = null
  intento.value = false
  setTimeout(() => tituloInput.value?.focus(), 0)
})

/**
 * Descripcion: Muestra el error de un campo después del intento de agregar.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function visible(campo) {
  return intento.value ? errores.value[campo] : ''
}

/**
 * Descripcion: Relaciona el documento a la licitación cuando la modal es válida.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function agregar() {
  intento.value = true
  if (!valido.value) {
    return
  }
  emit('agregar', {
    titulo: form.titulo.trim(),
    descripcion: form.descripcion.trim(),
    archivo: form.archivo,
  })
  abierto.value = false
}

/**
 * Descripcion: Guarda el archivo elegido en el formulario de la modal.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function alArchivo(evento) {
  form.archivo = evento.target.files?.[0] || null
}

/**
 * Descripcion: Cierra la modal sin agregar el documento.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function cerrar() {
  abierto.value = false
}

/**
 * Descripcion: Cierra la modal cuando se pulsa Escape.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function alTecla(evento) {
  if (evento.key === 'Escape' && abierto.value) {
    cerrar()
  }
}

onMounted(() => window.addEventListener('keydown', alTecla))
onUnmounted(() => window.removeEventListener('keydown', alTecla))
</script>

<template>
  <div v-if="abierto" class="modal-documento position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center p-3">
    <div class="superficie p-4" style="width: min(560px, 100%)" role="dialog" aria-modal="true" aria-labelledby="titulo-modal">
      <h2 id="titulo-modal" class="h4">Agregar documento</h2>
      <p class="subtitulo">El archivo queda en el listado y se envía al guardar la licitación. Solo PDF o ZIP, hasta 10 MB.</p>

      <div class="mb-3">
        <div class="d-flex justify-content-between">
          <label class="form-label" for="titulo_documento">Título</label>
          <span class="contador">{{ [...form.titulo].length }} / 100</span>
        </div>
        <input
          id="titulo_documento"
          ref="tituloInput"
          :value="form.titulo"
          class="form-control"
          :class="{ 'campo-invalido': visible('titulo') }"
          type="text"
          maxlength="100"
          @input="form.titulo = limitarTexto($event.target.value, 100)"
        />
        <p v-if="visible('titulo')" class="ayuda-error mb-0">{{ visible('titulo') }}</p>
      </div>

      <div class="mb-3">
        <div class="d-flex justify-content-between">
          <label class="form-label" for="descripcion_documento">Descripción</label>
          <span class="contador">{{ [...form.descripcion].length }} / 200</span>
        </div>
        <textarea
          id="descripcion_documento"
          :value="form.descripcion"
          class="form-control"
          :class="{ 'campo-invalido': visible('descripcion') }"
          rows="3"
          maxlength="200"
          @input="form.descripcion = limitarTexto($event.target.value, 200)"
        />
        <p v-if="visible('descripcion')" class="ayuda-error mb-0">{{ visible('descripcion') }}</p>
      </div>

      <div class="mb-4">
        <label class="form-label" for="archivo_documento">Archivo</label>
        <input
          id="archivo_documento"
          class="form-control"
          :class="{ 'campo-invalido': visible('archivo') }"
          type="file"
          accept=".pdf,.zip,application/pdf,application/zip"
          @change="alArchivo"
        />
        <p v-if="visible('archivo')" class="ayuda-error mb-0">{{ visible('archivo') }}</p>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary" type="button" @click="cerrar">Cancelar</button>
        <button class="btn btn-accion" type="button" :disabled="!valido" @click="agregar">Agregar</button>
      </div>
    </div>
  </div>
</template>
