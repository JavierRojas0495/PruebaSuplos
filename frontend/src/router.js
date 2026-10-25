import { createRouter, createWebHistory } from 'vue-router'
import ListadoOfertas from './vistas/ListadoOfertas.vue'
import CrearOferta from './vistas/CrearOferta.vue'
import DetalleOferta from './vistas/DetalleOferta.vue'
import EditarOferta from './vistas/EditarOferta.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'listado', component: ListadoOfertas },
    { path: '/ofertas/nueva', name: 'crear', component: CrearOferta },
    { path: '/ofertas/:id', name: 'detalle', component: DetalleOferta },
    { path: '/ofertas/:id/editar', name: 'editar', component: EditarOferta },
  ],
})

export default router
