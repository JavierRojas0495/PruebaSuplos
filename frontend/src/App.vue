<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { servidorListo } from './api'

const route = useRoute()
const enListado = computed(() => route.name === 'listado')
const listo = ref(false)
const intento = ref(0)
const fallo = ref(false)
let temporizador = 0
let cancelado = false

const mensaje = computed(() => {
  if (fallo.value) {
    return 'El servidor no respondió. Vuelva a comprobarlo.'
  }
  if (intento.value > 2) {
    return 'El servicio gratuito tarda un momento en despertar. Puede esperar en esta pantalla.'
  }
  return 'Comprobando el servidor y la base de datos.'
})

/**
 * Descripcion: Espera el tiempo indicado y se puede cancelar al salir de la pantalla.
 * Fecha: 25/10/26
 * Autor: Javier Rojas
 */
function esperar(milisegundos) {
  return new Promise((resolver) => {
    temporizador = window.setTimeout(resolver, milisegundos)
  })
}

/**
 * Descripcion: Consulta el servidor hasta que responda bien y solo entonces muestra la aplicación.
 * Fecha: 25/10/26
 * Autor: Javier Rojas
 */
async function comprobar() {
  const inicio = Date.now()
  fallo.value = false
  listo.value = false
  intento.value = 0
  cancelado = false

  while (!cancelado && intento.value < 40) {
    intento.value += 1
    try {
      if (await servidorListo()) {
        const restante = 700 - (Date.now() - inicio)
        if (restante > 0) {
          await esperar(restante)
        }
        if (!cancelado) {
          listo.value = true
        }
        return
      }
    } catch {
      // El servicio puede estar apagado. Se vuelve a intentar.
    }
    if (!cancelado) {
      await esperar(3000)
    }
  }

  if (!cancelado) {
    fallo.value = true
  }
}

onMounted(comprobar)
onUnmounted(() => {
  cancelado = true
  window.clearTimeout(temporizador)
})
</script>

<template>
  <div v-if="!listo" class="arranque" role="status" aria-live="polite">
    <img src="/marca.webp" alt="Suplos" />
    <h1>Abriendo licitaciones</h1>
    <p>{{ mensaje }}</p>
    <div class="arranque-linea" aria-hidden="true"><span></span></div>
    <button v-if="fallo" class="btn btn-accion" type="button" @click="comprobar">Reintentar</button>
  </div>
  <div v-else class="min-vh-100 d-flex flex-column">
    <header class="barra">
      <div class="contenido barra-interior d-flex align-items-center justify-content-between py-3 my-0">
        <router-link class="marca" to="/" aria-label="Suplos, ir al listado">
          <img src="/marca.webp" alt="Suplos" />
        </router-link>
        <div class="acciones-barra d-flex align-items-center gap-2">
          <router-link v-if="!enListado" class="btn btn-sm btn-listado" to="/">Volver al listado</router-link>
          <router-link class="btn btn-sm btn-accion" to="/ofertas/nueva">Nueva licitación</router-link>
        </div>
      </div>
    </header>
    <main class="contenido flex-grow-1">
      <router-view v-slot="{ Component }">
        <transition name="pagina" mode="out-in">
          <component :is="Component" :key="route.fullPath" />
        </transition>
      </router-view>
    </main>
  </div>
</template>
