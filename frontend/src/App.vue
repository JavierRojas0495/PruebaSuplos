<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const enListado = computed(() => route.name === 'listado')
</script>

<template>
  <div class="min-vh-100 d-flex flex-column">
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
