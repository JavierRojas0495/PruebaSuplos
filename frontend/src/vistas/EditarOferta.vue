<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FormularioOferta from '../componentes/FormularioOferta.vue'
import ModalDocumento from '../componentes/ModalDocumento.vue'
import { actualizarOferta, mensajeError, obtenerOferta, urlPublica } from '../api'
import { ofertaVacia, validarOferta } from '../validaciones/oferta'

const route = useRoute()
const router = useRouter()
const form = ref(ofertaVacia())
const existentes = ref([])
const pendientes = ref([])
const modal = ref(false)
const tocados = reactive({})
const erroresServidor = ref({})
const aviso = ref('')
const errorCarga = ref('')
const cargando = ref(false)
const guardando = ref(false)

const erroresOferta = computed(() => validarOferta(form.value))
const documentosSuficientes = computed(() => existentes.value.length + pendientes.value.length >= 1)
const errores = computed(() => {
  const unidos = { ...erroresOferta.value, ...erroresServidor.value }
  if (!documentosSuficientes.value) {
    unidos.documentos = 'Debe registrar al menos un documento.'
  }
  return unidos
})
const erroresVisibles = computed(() => {
  const visibles = {}
  for (const [campo, mensaje] of Object.entries(errores.value)) {
    if (campo === 'documentos' || tocados[campo] || erroresServidor.value[campo]) {
      visibles[campo] = mensaje
    }
  }
  return visibles
})
const valido = computed(() => Object.keys(erroresOferta.value).length === 0 && documentosSuficientes.value)

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
 * Descripcion: Agrega a la lista un documento capturado en la modal.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function agregarDocumento(documento) {
  pendientes.value.push(documento)
}

/**
 * Descripcion: Quita un documento que todavía no se ha enviado.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function quitarPendiente(indice) {
  pendientes.value.splice(indice, 1)
}

/**
 * Descripcion: Trae la licitación y llena el formulario con sus datos.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
async function cargar(id) {
  cargando.value = true
  aviso.value = ''
  errorCarga.value = ''
  try {
    const oferta = await obtenerOferta(id)
    existentes.value = oferta.documentos
    form.value = {
      objeto: oferta.objeto,
      descripcion: oferta.descripcion,
      moneda: oferta.moneda,
      presupuesto: oferta.presupuesto,
      actividad_id: String(oferta.actividad?.id || ''),
      actividad_etiqueta: oferta.actividad
        ? `${oferta.actividad.codigo_producto} · ${oferta.actividad.producto}`
        : '',
      fecha_inicio: oferta.fecha_inicio,
      hora_inicio: oferta.hora_inicio,
      fecha_cierre: oferta.fecha_cierre,
      hora_cierre: oferta.hora_cierre,
    }
  } catch (error) {
    errorCarga.value = mensajeError(error).message
  } finally {
    cargando.value = false
  }
}

/**
 * Descripcion: Envía los datos y los archivos nuevos cuando el formulario es válido.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
async function guardar() {
  Object.keys(erroresOferta.value).forEach((campo) => {
    tocados[campo] = true
  })
  if (!valido.value || guardando.value) {
    return
  }

  const datos = new FormData()
  const { actividad_etiqueta, ...payload } = form.value
  Object.entries(payload).forEach(([campo, valor]) => datos.append(campo, valor))
  pendientes.value.forEach((documento) => {
    datos.append('titulo_documento[]', documento.titulo)
    datos.append('descripcion_documento[]', documento.descripcion)
    datos.append('archivo[]', documento.archivo)
  })

  guardando.value = true
  aviso.value = ''
  erroresServidor.value = {}
  try {
    await actualizarOferta(route.params.id, datos)
    await router.push(`/ofertas/${route.params.id}`)
  } catch (error) {
    const cuerpo = mensajeError(error)
    erroresServidor.value = cuerpo.errors || {}
    aviso.value = cuerpo.message
  } finally {
    guardando.value = false
  }
}

watch(() => route.params.id, (id) => cargar(id), { immediate: true })
</script>

<template>
  <div>
    <h1 class="mb-1">Editar licitación</h1>
    <p class="subtitulo">Las secciones se cargan con los datos actuales. Para guardar hace falta al menos un documento.</p>
    <p v-if="cargando">Cargando datos…</p>
    <p v-if="errorCarga" class="alert alert-danger" role="alert">{{ errorCarga }}</p>
    <p v-if="aviso" class="alert alert-danger" role="alert">{{ aviso }}</p>

    <form v-if="!cargando && !errorCarga" class="superficie" @submit.prevent="guardar">
      <FormularioOferta v-model="form" :errores="erroresVisibles" @blur="marcar" />

      <section class="seccion p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
          <div>
            <h2 class="h4 mb-1">Documentos</h2>
            <p class="subtitulo mb-0">Agregue el documento al listado. El archivo se envía con el guardado final.</p>
          </div>
          <button class="btn btn-outline-secondary" type="button" @click="modal = true">Agregar documento</button>
        </div>

        <p v-if="erroresVisibles.documentos" class="ayuda-error">{{ erroresVisibles.documentos }}</p>
        <p v-if="existentes.length === 0 && pendientes.length === 0" class="mb-0">Todavía no hay documentos.</p>

        <ul v-else class="list-unstyled mb-0">
          <li v-for="documento in existentes" :key="documento.id" class="py-3 border-bottom">
            <strong>{{ documento.titulo }}</strong>
            <p class="mb-1">{{ documento.descripcion }}</p>
            <a :href="urlPublica(documento.url)">{{ documento.nombre }}</a>
            <span class="contador d-block">{{ documento.creado_en }}</span>
          </li>
          <li v-for="(documento, indice) in pendientes" :key="`nuevo-${indice}`" class="py-3 border-bottom d-flex justify-content-between gap-3">
            <div>
              <strong>{{ documento.titulo }}</strong>
              <p class="mb-1">{{ documento.descripcion }}</p>
              <span class="contador">{{ documento.archivo.name }} · pendiente de envío</span>
            </div>
            <button class="btn btn-sm btn-outline-secondary align-self-start" type="button" @click="quitarPendiente(indice)">Quitar</button>
          </li>
        </ul>
      </section>

      <div class="seccion acciones-formulario p-3 p-md-4 d-flex justify-content-end gap-2">
        <router-link class="btn btn-outline-secondary" to="/">Volver al listado</router-link>
        <router-link class="btn btn-outline-secondary" :to="`/ofertas/${route.params.id}`">Cancelar</router-link>
        <button class="btn btn-accion" type="submit" :disabled="!valido || guardando">
          {{ guardando ? 'Guardando…' : 'Guardar' }}
        </button>
      </div>
    </form>

    <ModalDocumento v-model:abierto="modal" @agregar="agregarDocumento" />
  </div>
</template>
