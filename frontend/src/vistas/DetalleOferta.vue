<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import Espera from '../componentes/Espera.vue'
import { mensajeError, obtenerOferta, urlPublica } from '../api'

const route = useRoute()
const oferta = ref(null)
const pestana = ref('basica')
const error = ref('')
const cargando = ref(false)

const pestanas = [
  { id: 'basica', etiqueta: 'Información básica' },
  { id: 'cronograma', etiqueta: 'Cronograma' },
  { id: 'documentos', etiqueta: 'Documentos' },
]

/**
 * Descripcion: Consulta el detalle de la licitación indicada.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
async function cargar(id) {
  cargando.value = true
  error.value = ''
  oferta.value = null
  try {
    oferta.value = await obtenerOferta(id)
  } catch (excepcion) {
    error.value = mensajeError(excepcion).message
  } finally {
    cargando.value = false
  }
}

/**
 * Descripcion: Asigna la clase visual según el estado de la licitación.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function claseEstado(estado) {
  if (estado === 'En curso') return 'estado estado-en-curso'
  if (estado === 'Cerrada') return 'estado estado-cerrada'
  return 'estado estado-programada'
}

/**
 * Descripcion: Formatea el presupuesto con el símbolo y los decimales de su moneda.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function valorEnMoneda(monto, moneda) {
  const numero = Number(monto)
  const codigo = ['COP', 'USD', 'EUR'].includes(moneda) ? moneda : 'COP'
  if (!Number.isFinite(numero)) return String(monto ?? '')
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: codigo,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(numero)
}

watch(() => route.params.id, (id) => cargar(id), { immediate: true })
onMounted(() => {
  pestana.value = 'basica'
})
</script>

<template>
  <div>
    <Espera
      v-if="cargando"
      titulo="Cargando licitación"
      texto="La ficha aparece en cuanto el servidor responde."
    />
    <p v-else-if="error" class="alert alert-danger" role="alert">{{ error }}</p>

    <template v-else-if="oferta">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
          <p class="numero-oferta mb-1">{{ oferta.consecutivo }}</p>
          <h1 class="mb-2">{{ oferta.objeto }}</h1>
          <span :class="claseEstado(oferta.estado)">{{ oferta.estado }}</span>
        </div>
        <div class="acciones-pagina d-flex flex-wrap gap-2">
          <router-link class="btn btn-outline-secondary" to="/">Volver al listado</router-link>
          <router-link class="btn btn-accion" :to="`/ofertas/${oferta.id}/editar`">Editar licitación</router-link>
        </div>
      </div>

      <div class="superficie">
        <ul class="nav nav-tabs nav-ficha px-3 pt-3" role="tablist">
          <li v-for="item in pestanas" :key="item.id" class="nav-item" role="presentation">
            <button
              class="nav-link"
              :class="{ active: pestana === item.id }"
              type="button"
              role="tab"
              :aria-selected="pestana === item.id"
              @click="pestana = item.id"
            >
              {{ item.etiqueta }}
            </button>
          </li>
        </ul>

        <div class="p-3 p-md-4" role="tabpanel">
          <dl v-if="pestana === 'basica'" class="row mb-0">
            <dt class="col-sm-3">Descripción</dt>
            <dd class="col-sm-9">{{ oferta.descripcion }}</dd>
            <dt class="col-sm-3">Moneda</dt>
            <dd class="col-sm-9">{{ oferta.moneda }}</dd>
            <dt class="col-sm-3">Presupuesto</dt>
            <dd class="col-sm-9 valor-moneda">{{ valorEnMoneda(oferta.presupuesto, oferta.moneda) }}</dd>
            <dt class="col-sm-3">Actividad</dt>
            <dd class="col-sm-9">
              <template v-if="oferta.actividad">
                {{ oferta.actividad.codigo_producto }} · {{ oferta.actividad.producto }}
                <span class="d-block contador">{{ oferta.actividad.segmento }} / {{ oferta.actividad.familia }} / {{ oferta.actividad.clase }}</span>
              </template>
            </dd>
          </dl>

          <dl v-else-if="pestana === 'cronograma'" class="row mb-0">
            <dt class="col-sm-3">Inicio</dt>
            <dd class="col-sm-9">{{ oferta.fecha_inicio }} {{ oferta.hora_inicio }}</dd>
            <dt class="col-sm-3">Cierre</dt>
            <dd class="col-sm-9">{{ oferta.fecha_cierre }} {{ oferta.hora_cierre }}</dd>
          </dl>

          <div v-else>
            <p v-if="oferta.documentos.length === 0" class="mb-0">Esta licitación todavía no tiene documentos. Puede cargarlos al editar.</p>
            <ul v-else class="list-unstyled mb-0">
              <li v-for="documento in oferta.documentos" :key="documento.id" class="py-3 border-bottom">
                <strong>{{ documento.titulo }}</strong>
                <p class="mb-1">{{ documento.descripcion }}</p>
                <a :href="urlPublica(documento.url)">{{ documento.nombre }}</a>
                <span class="contador d-block">{{ documento.creado_en }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
