const MONEDAS = ['COP', 'USD', 'EUR']

/**
 * Descripcion: Devuelve el formulario de oferta con todos los campos vacíos.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
export function ofertaVacia() {
  return {
    objeto: '',
    descripcion: '',
    moneda: '',
    presupuesto: '',
    actividad_id: '',
    actividad_etiqueta: '',
    fecha_inicio: '',
    hora_inicio: '',
    fecha_cierre: '',
    hora_cierre: '',
  }
}

/**
 * Descripcion: Revisa las reglas de la oferta y devuelve los errores encontrados.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
export function validarOferta(form) {
  const errores = {}
  const objeto = texto(form.objeto)
  const descripcion = texto(form.descripcion)

  if (!objeto) {
    errores.objeto = 'El objeto es obligatorio y no puede ser solo espacios.'
  } else if ([...objeto].length > 150) {
    errores.objeto = 'El objeto no puede superar 150 caracteres.'
  }

  if (!descripcion) {
    errores.descripcion = 'La descripción es obligatoria y no puede ser solo espacios.'
  } else if ([...descripcion].length > 400) {
    errores.descripcion = 'La descripción no puede superar 400 caracteres.'
  }

  if (!form.moneda) {
    errores.moneda = 'La moneda es obligatoria.'
  } else if (!MONEDAS.includes(form.moneda)) {
    errores.moneda = 'La moneda solo puede ser COP, USD o EUR.'
  }

  if (!texto(form.presupuesto)) {
    errores.presupuesto = 'El presupuesto es obligatorio.'
  } else if (!/^\d{1,13}(\.\d{1,2})?$/.test(form.presupuesto)) {
    errores.presupuesto = 'El presupuesto solo admite números y punto, con máximo dos decimales.'
  } else if (Number(form.presupuesto) <= 0) {
    errores.presupuesto = 'El presupuesto debe ser mayor que 0.'
  }

  if (!form.actividad_id) {
    errores.actividad_id = 'La actividad es obligatoria.'
  }

  if (!fechaValida(form.fecha_inicio)) {
    errores.fecha_inicio = 'La fecha de inicio es obligatoria y debe tener formato AAAA-MM-DD.'
  }
  if (!fechaValida(form.fecha_cierre)) {
    errores.fecha_cierre = 'La fecha de cierre es obligatoria y debe tener formato AAAA-MM-DD.'
  }
  if (!horaValida(form.hora_inicio)) {
    errores.hora_inicio = 'La hora de inicio es obligatoria y debe tener formato HH:mm.'
  }
  if (!horaValida(form.hora_cierre)) {
    errores.hora_cierre = 'La hora de cierre es obligatoria y debe tener formato HH:mm.'
  }

  if (
    fechaValida(form.fecha_inicio) &&
    fechaValida(form.fecha_cierre) &&
    form.fecha_cierre < form.fecha_inicio
  ) {
    errores.fecha_cierre = 'La fecha de cierre no puede ser anterior a la fecha de inicio.'
  }

  if (
    !errores.fecha_inicio &&
    !errores.fecha_cierre &&
    !errores.hora_inicio &&
    !errores.hora_cierre &&
    !errores.fecha_cierre
  ) {
    const inicio = `${form.fecha_inicio} ${form.hora_inicio}`
    const cierre = `${form.fecha_cierre} ${form.hora_cierre}`
    if (inicio >= cierre) {
      errores.hora_cierre = 'La fecha y hora de cierre deben ser posteriores al inicio.'
    }
  }

  return errores
}

/**
 * Descripcion: Revisa título, descripción y archivo PDF o ZIP de un documento.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
export function validarDocumento({ titulo, descripcion, archivo }) {
  const errores = {}
  const tituloLimpio = texto(titulo)
  const descripcionLimpia = texto(descripcion)

  if (!tituloLimpio) {
    errores.titulo = 'El título del documento es obligatorio.'
  } else if ([...tituloLimpio].length > 100) {
    errores.titulo = 'El título del documento no puede superar 100 caracteres.'
  }

  if (!descripcionLimpia) {
    errores.descripcion = 'La descripción del documento es obligatoria.'
  } else if ([...descripcionLimpia].length > 200) {
    errores.descripcion = 'La descripción del documento no puede superar 200 caracteres.'
  }

  if (!archivo) {
    errores.archivo = 'El archivo es obligatorio.'
  } else {
    const extension = archivo.name.split('.').pop()?.toLowerCase()
    if (!['pdf', 'zip'].includes(extension)) {
      errores.archivo = 'Solo se permiten archivos PDF o ZIP.'
    } else if (archivo.size <= 0 || archivo.size > 10 * 1024 * 1024) {
      errores.archivo = 'El archivo debe pesar entre 1 byte y 10 MB.'
    }
  }

  return errores
}

/**
 * Descripcion: Recorta un texto al máximo de caracteres permitido.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
export function limitarTexto(valor, maximo) {
  return [...String(valor ?? '')].slice(0, maximo).join('')
}

/**
 * Descripcion: Deja solo números y punto, con hasta dos decimales.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
export function limpiarPresupuesto(valor) {
  let limpio = String(valor ?? '').replace(/[^\d.]/g, '')
  const partes = limpio.split('.')
  if (partes.length > 2) {
    limpio = `${partes[0]}.${partes.slice(1).join('')}`
  }
  const [entero, decimal] = limpio.split('.')
  const enteroCorto = (entero || '').slice(0, 13)
  if (decimal === undefined) {
    return enteroCorto
  }
  return `${enteroCorto}.${decimal.slice(0, 2)}`
}

/**
 * Descripcion: Recorta un valor a texto sin espacios laterales.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function texto(valor) {
  return String(valor ?? '').trim()
}

/**
 * Descripcion: Comprueba que la fecha exista y tenga formato AAAA-MM-DD.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function fechaValida(valor) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(valor || '')) {
    return false
  }
  const [anio, mes, dia] = valor.split('-').map(Number)
  const fecha = new Date(anio, mes - 1, dia)
  return fecha.getFullYear() === anio && fecha.getMonth() === mes - 1 && fecha.getDate() === dia
}

/**
 * Descripcion: Comprueba que la hora tenga formato HH:mm de 24 horas.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function horaValida(valor) {
  return /^([01]\d|2[0-3]):[0-5]\d$/.test(valor || '')
}
