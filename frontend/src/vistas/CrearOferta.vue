<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import FormularioOferta from '../componentes/FormularioOferta.vue'
import { crearOferta, mensajeError } from '../api'
import { ofertaVacia, validarOferta } from '../validaciones/oferta'

const router = useRouter()
const form = ref(ofertaVacia())
const tocados = reactive({})
const erroresServidor = ref({})
const aviso = ref('')
const guardando = ref(false)

const errores = computed(() => ({ ...validarOferta(form.value), ...erroresServidor.value }))
const erroresVisibles = computed(() => {
  const visibles = {}
  for (const [campo, mensaje] of Object.entries(errores.value)) {
    if (tocados[campo] || erroresServidor.value[campo]) {
      visibles[campo] = mensaje
    }
  }
  return visibles
})
const valido = computed(() => Object.keys(validarOferta(form.value)).length === 0)

/**
 * Descripcion: Marca un campo como revisado y limpia su error de servidor.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function marcar(campo) {
  tocados[campo] = true
  delete erroresServidor.value[campo]
}

/**
 * Descripcion: Envía la licitación nueva cuando todas las validaciones se cumplen.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
async function guardar() {
  Object.keys(validarOferta(form.value)).forEach((campo) => {
    tocados[campo] = true
  })
  if (!valido.value || guardando.value) {
    return
  }

  guardando.value = true
  aviso.value = ''
  erroresServidor.value = {}
  try {
    const { actividad_etiqueta, ...payload } = form.value
    const creada = await crearOferta(payload)
    await router.push(`/ofertas/${creada.id}`)
  } catch (error) {
    const cuerpo = mensajeError(error)
    erroresServidor.value = cuerpo.errors || {}
    aviso.value = cuerpo.message
    Object.keys(erroresServidor.value).forEach((campo) => {
      tocados[campo] = true
    })
  } finally {
    guardando.value = false
  }
}
</script>

<template>
  <div>
    <h1 class="mb-1">Nueva licitación</h1>
    <p class="subtitulo">El consecutivo lo asigna el servidor al guardar. El botón permanece inactivo mientras haya errores.</p>

    <p v-if="aviso" class="alert alert-danger" role="alert">{{ aviso }}</p>

    <form class="superficie" @submit.prevent="guardar">
      <FormularioOferta v-model="form" :errores="erroresVisibles" @blur="marcar" />
      <div class="seccion acciones-formulario p-3 p-md-4 d-flex justify-content-end gap-2">
        <router-link class="btn btn-outline-secondary" to="/">Volver al listado</router-link>
        <button class="btn btn-accion" type="submit" :disabled="!valido || guardando">
          {{ guardando ? 'Guardando…' : 'Guardar' }}
        </button>
      </div>
    </form>
  </div>
</template>
