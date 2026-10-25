<script setup>
import BuscadorActividad from './BuscadorActividad.vue'
import { limitarTexto, limpiarPresupuesto } from '../validaciones/oferta'

const form = defineModel({ required: true })

defineProps({
  errores: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['blur'])

const monedas = [
  { valor: 'COP', etiqueta: 'COP — Peso colombiano' },
  { valor: 'USD', etiqueta: 'USD — Dólar' },
  { valor: 'EUR', etiqueta: 'EUR — Euro' },
]

/**
 * Descripcion: Avisa que el usuario salió de un campo para mostrar su validación.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function marcar(campo) {
  emit('blur', campo)
}
</script>

<template>
  <div>
    <section class="seccion p-3 p-md-4">
      <h2 class="h4 mb-1">Información básica</h2>
      <p class="subtitulo">Datos principales de la licitación.</p>

      <div class="mb-3">
        <div class="d-flex justify-content-between">
          <label class="form-label" for="objeto">Objeto</label>
          <span class="contador">{{ [...form.objeto].length }} / 150</span>
        </div>
        <input
          id="objeto"
          :value="form.objeto"
          class="form-control"
          :class="{ 'campo-invalido': errores.objeto }"
          type="text"
          maxlength="150"
          required
          @input="form.objeto = limitarTexto($event.target.value, 150)"
          @blur="marcar('objeto')"
        />
        <p v-if="errores.objeto" class="ayuda-error mb-0">{{ errores.objeto }}</p>
      </div>

      <div class="mb-3">
        <div class="d-flex justify-content-between">
          <label class="form-label" for="descripcion">Descripción / alcance</label>
          <span class="contador">{{ [...form.descripcion].length }} / 400</span>
        </div>
        <textarea
          id="descripcion"
          :value="form.descripcion"
          class="form-control"
          :class="{ 'campo-invalido': errores.descripcion }"
          rows="4"
          maxlength="400"
          required
          @input="form.descripcion = limitarTexto($event.target.value, 400)"
          @blur="marcar('descripcion')"
        />
        <p v-if="errores.descripcion" class="ayuda-error mb-0">{{ errores.descripcion }}</p>
      </div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="moneda">Moneda</label>
          <select
            id="moneda"
            v-model="form.moneda"
            class="form-select"
            :class="{ 'campo-invalido': errores.moneda }"
            required
            @blur="marcar('moneda')"
          >
            <option value="">Seleccione</option>
            <option v-for="moneda in monedas" :key="moneda.valor" :value="moneda.valor">{{ moneda.etiqueta }}</option>
          </select>
          <p v-if="errores.moneda" class="ayuda-error mb-0">{{ errores.moneda }}</p>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="presupuesto">Presupuesto</label>
          <input
            id="presupuesto"
            :value="form.presupuesto"
            class="form-control"
            :class="{ 'campo-invalido': errores.presupuesto }"
            type="text"
            inputmode="decimal"
            placeholder="0.00"
            required
            @input="form.presupuesto = limpiarPresupuesto($event.target.value)"
            @blur="marcar('presupuesto')"
          />
          <p v-if="errores.presupuesto" class="ayuda-error mb-0">{{ errores.presupuesto }}</p>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="actividad">Actividad</label>
          <BuscadorActividad
            v-model="form.actividad_id"
            v-model:etiqueta="form.actividad_etiqueta"
            :invalido="Boolean(errores.actividad_id)"
            :mensaje="errores.actividad_id || ''"
            @blur="marcar('actividad_id')"
          />
        </div>
      </div>
    </section>

    <section class="seccion p-3 p-md-4">
      <h2 class="h4 mb-1">Cronograma</h2>
      <p class="subtitulo">La fecha y hora de cierre deben quedar después del inicio.</p>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label" for="fecha_inicio">Fecha de inicio</label>
          <input
            id="fecha_inicio"
            v-model="form.fecha_inicio"
            class="form-control"
            :class="{ 'campo-invalido': errores.fecha_inicio }"
            type="date"
            required
            @blur="marcar('fecha_inicio')"
          />
          <p v-if="errores.fecha_inicio" class="ayuda-error mb-0">{{ errores.fecha_inicio }}</p>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="hora_inicio">Hora de inicio</label>
          <input
            id="hora_inicio"
            v-model="form.hora_inicio"
            class="form-control"
            :class="{ 'campo-invalido': errores.hora_inicio }"
            type="time"
            required
            @blur="marcar('hora_inicio')"
          />
          <p v-if="errores.hora_inicio" class="ayuda-error mb-0">{{ errores.hora_inicio }}</p>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="fecha_cierre">Fecha de cierre</label>
          <input
            id="fecha_cierre"
            v-model="form.fecha_cierre"
            class="form-control"
            :class="{ 'campo-invalido': errores.fecha_cierre }"
            type="date"
            :min="form.fecha_inicio || undefined"
            required
            @blur="marcar('fecha_cierre')"
          />
          <p v-if="errores.fecha_cierre" class="ayuda-error mb-0">{{ errores.fecha_cierre }}</p>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="hora_cierre">Hora de cierre</label>
          <input
            id="hora_cierre"
            v-model="form.hora_cierre"
            class="form-control"
            :class="{ 'campo-invalido': errores.hora_cierre }"
            type="time"
            required
            @blur="marcar('hora_cierre')"
          />
          <p v-if="errores.hora_cierre" class="ayuda-error mb-0">{{ errores.hora_cierre }}</p>
        </div>
      </div>
    </section>
  </div>
</template>
