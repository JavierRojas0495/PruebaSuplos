<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { exportarOfertas, listarOfertas, mensajeError } from '../api'

const filtros = reactive({ consecutivo: '', objeto: '', descripcion: '' })
const aplicados = ref({ consecutivo: '', objeto: '', descripcion: '' })
const pagina = ref(1)
const ofertas = ref([])
const meta = ref({ page: 1, per_page: 10, total: 0, last_page: 1 })
const cargando = ref(false)
const exportando = ref(false)
const error = ref('')

const hayFiltros = computed(() => Object.values(aplicados.value).some(Boolean))

/**
 * Descripcion: Consulta la página actual del listado con los filtros aplicados.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
async function cargar() {
  cargando.value = true
  error.value = ''
  try {
    const respuesta = await listarOfertas({ ...aplicados.value, page: pagina.value, per_page: 10 })
    ofertas.value = respuesta.data
    meta.value = respuesta.meta
  } catch (excepcion) {
    error.value = mensajeError(excepcion).message
  } finally {
    cargando.value = false
  }
}

/**
 * Descripcion: Aplica los filtros y vuelve a la primera página.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function buscar() {
  aplicados.value = { ...filtros }
  pagina.value = 1
  cargar()
}

/**
 * Descripcion: Borra los filtros y recarga el listado.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function limpiar() {
  filtros.consecutivo = ''
  filtros.objeto = ''
  filtros.descripcion = ''
  buscar()
}

/**
 * Descripcion: Cambia de página en el listado.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function ir(destino) {
  pagina.value = destino
  cargar()
}

/**
 * Descripcion: Descarga el Excel del filtro actual.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
async function excel() {
  exportando.value = true
  error.value = ''
  try {
    const respuesta = await exportarOfertas(aplicados.value)
    const url = URL.createObjectURL(respuesta.data)
    const enlace = document.createElement('a')
    enlace.href = url
    enlace.download = 'ofertas.xlsx'
    enlace.click()
    URL.revokeObjectURL(url)
  } catch (excepcion) {
    error.value = mensajeError(excepcion).message
  } finally {
    exportando.value = false
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

onMounted(cargar)
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="mb-1">Licitaciones</h1>
        <p class="subtitulo mb-0">Listado de ofertas registradas. El Excel usa estos filtros y no respeta la página.</p>
      </div>
      <button class="btn btn-outline-secondary w-100 w-md-auto" type="button" :disabled="exportando" @click="excel">
        {{ exportando ? 'Generando…' : 'Exportar Excel' }}
      </button>
    </div>

    <form class="superficie p-3 mb-3" @submit.prevent="buscar">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label" for="filtro-consecutivo">Consecutivo</label>
          <input id="filtro-consecutivo" v-model="filtros.consecutivo" class="form-control" type="search" />
        </div>
        <div class="col-md-3">
          <label class="form-label" for="filtro-objeto">Objeto</label>
          <input id="filtro-objeto" v-model="filtros.objeto" class="form-control" type="search" />
        </div>
        <div class="col-md-3">
          <label class="form-label" for="filtro-descripcion">Descripción</label>
          <input id="filtro-descripcion" v-model="filtros.descripcion" class="form-control" type="search" />
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
          <button class="btn btn-accion flex-fill" type="submit">Filtrar</button>
          <button class="btn btn-outline-secondary flex-fill" type="button" @click="limpiar">Limpiar</button>
        </div>
      </div>
    </form>

    <p v-if="error" class="alert alert-danger" role="alert">{{ error }}</p>
    <p v-if="cargando">Cargando licitaciones…</p>

    <div v-else class="superficie superficie-lista">
      <p v-if="ofertas.length === 0" class="vacio mb-0">
        {{ hayFiltros ? 'No hay licitaciones con esos filtros.' : 'Todavía no hay licitaciones. Cree la primera desde Nueva licitación.' }}
      </p>
      <div v-else>
        <div class="d-md-none">
          <article v-for="oferta in ofertas" :key="`movil-${oferta.id}`" class="p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
              <strong class="numero-oferta">{{ oferta.consecutivo }}</strong>
              <span :class="claseEstado(oferta.estado)">{{ oferta.estado }}</span>
            </div>
            <h2 class="h5 mb-1">{{ oferta.objeto }}</h2>
            <p class="descripcion-corta mb-2">{{ oferta.descripcion }}</p>
            <p class="contador mb-3">Inicio {{ oferta.fecha_inicio }} · Cierre {{ oferta.fecha_cierre }}</p>
            <router-link class="btn btn-outline-secondary w-100" :to="`/ofertas/${oferta.id}`">Ver detalle</router-link>
          </article>
        </div>
        <div class="d-none d-md-block table-responsive">
        <table class="table tabla-ofertas mb-0">
          <thead>
            <tr>
              <th>Consecutivo</th>
              <th>Objeto</th>
              <th>Descripción</th>
              <th>Fecha de inicio</th>
              <th>Fecha de cierre</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="oferta in ofertas" :key="oferta.id">
              <td>{{ oferta.consecutivo }}</td>
              <td>{{ oferta.objeto }}</td>
              <td class="descripcion-corta">{{ oferta.descripcion }}</td>
              <td>{{ oferta.fecha_inicio }}</td>
              <td>{{ oferta.fecha_cierre }}</td>
              <td><span :class="claseEstado(oferta.estado)">{{ oferta.estado }}</span></td>
              <td>
                <router-link class="btn btn-sm btn-outline-secondary" :to="`/ofertas/${oferta.id}`">Ver detalle</router-link>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <nav v-if="meta.last_page > 1" class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3" aria-label="Paginación">
      <span class="contador">{{ meta.total }} licitaciones · página {{ meta.page }} de {{ meta.last_page }}</span>
      <div class="btn-group">
        <button class="btn btn-outline-secondary btn-sm" type="button" :disabled="pagina <= 1" @click="ir(pagina - 1)">Anterior</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" :disabled="pagina >= meta.last_page" @click="ir(pagina + 1)">Siguiente</button>
      </div>
    </nav>
  </div>
</template>
