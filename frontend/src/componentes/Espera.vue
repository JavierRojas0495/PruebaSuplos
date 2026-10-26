<script setup>
defineProps({
  titulo: { type: String, required: true },
  texto: { type: String, required: true },
})
</script>

<template>
  <div class="espera" role="status" aria-live="polite">
    <h2>{{ titulo }}</h2>
    <p>{{ texto }}</p>
    <div class="arranque-linea" aria-hidden="true"><span></span></div>
    <div class="espera-lineas" aria-hidden="true">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </div>
</template>
