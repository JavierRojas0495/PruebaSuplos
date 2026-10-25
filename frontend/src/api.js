import axios from 'axios'

/**
 * Descripcion: Resuelve la dirección del API: la variable de entorno, el servicio publicado o el proxy local.
 * Fecha: 25/10/26
 * Autor: Javier Rojas
 */
function baseApi() {
  const configurada = import.meta.env.VITE_API_URL
  if (configurada) {
    return String(configurada).replace(/\/$/, '')
  }
  if (import.meta.env.PROD) {
    return 'https://suplos-api.onrender.com/api'
  }
  return '/api'
}

const http = axios.create({
  baseURL: baseApi(),
  timeout: 60000,
})

/**
 * Descripcion: Comprueba que el API responda y tenga conexión con la base.
 * Fecha: 25/10/26
 * Autor: Javier Rojas
 */
export function servidorListo() {
  return http.get('/salud', { timeout: 8000 }).then((respuesta) => respuesta.data?.ok === true)
}

/**
 * Descripcion: Traduce el error de la petición al mensaje que muestra la pantalla.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
export function mensajeError(error) {
  const data = error?.response?.data
  if (data?.errors) {
    return data
  }
  return {
    message: data?.message || 'No se pudo completar la operación. Revise la conexión e intente de nuevo.',
    errors: {},
  }
}

/**
 * Descripcion: Busca actividades en la API.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
export function listarActividades(q = '') {
  return http.get('/actividades', { params: { q } }).then((respuesta) => respuesta.data.data)
}

/**
 * Descripcion: Consulta el listado paginado de licitaciones.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
export function listarOfertas(params) {
  return http.get('/ofertas', { params }).then((respuesta) => respuesta.data)
}

/**
 * Descripcion: Pide el Excel del listado con los filtros indicados.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
export function exportarOfertas(params) {
  return http.get('/ofertas/exportar', { params, responseType: 'blob' })
}

/**
 * Descripcion: Consulta el detalle de una licitación.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
export function obtenerOferta(id) {
  return http.get(`/ofertas/${id}`).then((respuesta) => respuesta.data.data)
}

/**
 * Descripcion: Envía la creación de una licitación.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
export function crearOferta(payload) {
  return http.post('/ofertas', payload).then((respuesta) => respuesta.data.data)
}

/**
 * Descripcion: Envía la edición de una licitación con sus archivos.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
export function actualizarOferta(id, formulario) {
  return http.post(`/ofertas/${id}`, formulario).then((respuesta) => respuesta.data.data)
}
