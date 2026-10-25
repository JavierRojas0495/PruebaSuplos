<script setup>
import { onMounted, ref, watch } from 'vue'
import { listarActividades, mensajeError } from '../api'

const model = defineModel({ default: '' })
const etiqueta = defineModel('etiqueta', { default: '' })

defineProps({
  invalido: { type: Boolean, default: false },
  mensaje: { type: String, default: '' },
})

const emit = defineEmits(['blur'])

const texto = ref(etiqueta.value || '')
const abiertas = ref(false)
const cargando = ref(true)
const lista = ref([])
const cargada = ref(false)
const aviso = ref('')
let temporizador

watch(etiqueta, (valor) => {
  if (valor) {
    texto.value = valor
  }
})

/**
 * Descripcion: Consulta actividades en el servidor y habilita el campo al responder.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
async function buscar(termino) {
  cargando.value = true
  aviso.value = ''
  try {
    lista.value = await listarActividades(termino)
    cargada.value = true
  } catch (error) {
    aviso.value = mensajeError(error).message
  } finally {
    cargando.value = false
  }
}

/**
 * Descripcion: Limpia la selección y busca el texto escrito con un breve retraso.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function alEscribir(evento) {
  texto.value = evento.target.value
  model.value = ''
  etiqueta.value = ''
  clearTimeout(temporizador)
  temporizador = setTimeout(() => buscar(texto.value.trim()), 250)
}

/**
 * Descripcion: Fija la actividad seleccionada y cierra la lista.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function elegir(item) {
  texto.value = `${item.codigo_producto} · ${item.producto}`
  model.value = String(item.id)
  etiqueta.value = texto.value
  abiertas.value = false
  emit('blur')
}

/**
 * Descripcion: Cierra la lista al salir del campo.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function alSalir() {
  setTimeout(() => {
    abiertas.value = false
    emit('blur')
  }, 150)
}

onMounted(() => buscar(''))
</script>

<template>
  <div class="position-relative">
    <input
      :value="texto"
      class="form-control"
      :class="{ 'campo-invalido': invalido }"
      type="text"
      role="combobox"
      aria-autocomplete="list"
      :aria-expanded="abiertas"
      id="actividad"
      :disabled="!cargada"
      placeholder="Busque por producto, clase o código"
      autocomplete="off"
      @input="alEscribir"
      @focus="abiertas = true"
      @blur="alSalir"
    />
    <p v-if="!cargada && cargando" class="form-text mb-0">Cargando actividades…</p>
    <p v-if="aviso" class="ayuda-error mb-0">{{ aviso }}</p>
    <div v-if="abiertas && cargada" class="resultados" role="listbox">
      <p v-if="cargando" class="px-3 py-2 mb-0">Buscando…</p>
      <p v-else-if="lista.length === 0" class="px-3 py-2 mb-0">No hay actividades con ese criterio.</p>
      <button v-for="item in lista" :key="item.id" type="button" @mousedown.prevent="elegir(item)">
        <strong>{{ item.codigo_producto }}</strong> · {{ item.producto }}
        <span class="d-block contador">{{ item.familia }} / {{ item.clase }}</span>
      </button>
    </div>
    <p v-if="mensaje" class="ayuda-error mb-0">{{ mensaje }}</p>
  </div>
</template>
