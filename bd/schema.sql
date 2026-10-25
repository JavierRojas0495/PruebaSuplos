-- Módulo de licitaciones (ofertas).
-- MySQL 8+ o MariaDB 10.5+, utf8mb4.
--
-- Este archivo crea la base y las tablas.
-- Los 49.022 productos UNSPSC van en actividades.sql y se insertan
-- en el mismo paso de instalación. No hace falta volver a descargar el Excel.

CREATE DATABASE IF NOT EXISTS suplos_ofertas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE suplos_ofertas;

CREATE TABLE actividades (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo_segmento INT NOT NULL,
  segmento VARCHAR(200) NOT NULL,
  codigo_familia INT NOT NULL,
  familia VARCHAR(200) NOT NULL,
  codigo_clase INT NOT NULL,
  clase VARCHAR(200) NOT NULL,
  codigo_producto INT NOT NULL,
  producto VARCHAR(200) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_actividades_producto (producto),
  KEY idx_actividades_codigo_producto (codigo_producto)
) ENGINE=InnoDB;

CREATE TABLE ofertas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  consecutivo VARCHAR(20) NOT NULL,
  objeto VARCHAR(150) NOT NULL,
  descripcion VARCHAR(400) NOT NULL,
  moneda VARCHAR(3) NOT NULL,
  presupuesto DECIMAL(15,2) NOT NULL,
  actividad_id INT UNSIGNED NOT NULL,
  fecha_inicio DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  fecha_cierre DATE NOT NULL,
  hora_cierre TIME NOT NULL,
  estado VARCHAR(20) NOT NULL,
  creado_en DATETIME NOT NULL,
  actualizado_en DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_ofertas_consecutivo (consecutivo),
  KEY idx_ofertas_objeto (objeto),
  KEY idx_ofertas_actividad (actividad_id),
  CONSTRAINT fk_ofertas_actividad
    FOREIGN KEY (actividad_id) REFERENCES actividades (id)
) ENGINE=InnoDB;

CREATE TABLE ofertas_documentos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  licitacion_id INT UNSIGNED NOT NULL,
  titulo VARCHAR(100) NOT NULL,
  descripcion VARCHAR(200) NOT NULL,
  archivo VARCHAR(255) NOT NULL,
  creado_en DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_documentos_licitacion (licitacion_id),
  CONSTRAINT fk_documentos_oferta
    FOREIGN KEY (licitacion_id) REFERENCES ofertas (id)
) ENGINE=InnoDB;

-- Apoyo para el consecutivo O-0001-25. No es una entidad de negocio:
-- evita dos altas simultáneas con el mismo número dentro del año.
CREATE TABLE secuencias (
  anio CHAR(2) NOT NULL,
  ultimo INT UNSIGNED NOT NULL,
  PRIMARY KEY (anio)
) ENGINE=InnoDB;

-- Siguiente archivo del mismo paso: bd/actividades.sql
