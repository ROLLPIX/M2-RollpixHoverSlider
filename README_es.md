# Rollpix Image Flip & Hover Slider para Magento 2

![Magento 2.4.x](https://img.shields.io/badge/Magento-2.4.x-orange?logo=magento)
![PHP 7.4–8.3](https://img.shields.io/badge/PHP-7.4–8.3-777BB4?logo=php&logoColor=white)
![Luma Compatible](https://img.shields.io/badge/Luma-Compatible-blue)
![Hyvä Compatible](https://img.shields.io/badge/Hyvä-Compatible-4DC0B5)
![License MIT](https://img.shields.io/badge/License-MIT-green)

**Sponsor: [www.rollpix.com](https://www.rollpix.com)**

> **[Read in English](README.md)**

---

## Descripcion General

Permite ver las imagenes de la galeria de un producto directamente desde el listado (PLP) sin entrar a la ficha de producto. Dos modos:

- **Flip** (clasico): muestra una imagen alternativa al pasar el mouse
- **Slider** (v2.0): navegar todas las imagenes con flechas, swipe, seguimiento de mouse y click en indicadores

Compatible con temas **Luma** y **Hyva**.

---

## Indice

- [Caracteristicas](#caracteristicas)
- [Requisitos Tecnicos](#requisitos-tecnicos)
- [Instalacion](#instalacion)
- [Configuracion](#configuracion)
- [Productos Configurables](#productos-configurables)
- [Soporte Hyva](#soporte-hyva)
- [Solucion de Problemas](#solucion-de-problemas)
- [Changelog](#changelog)
- [Licencia](#licencia)

---

## Caracteristicas

### Modo Flip
- Roles de imagen configurables (nativos o custom)
- Opcion "Segunda Imagen de Galeria" (sin asignar roles)
- Rol de respaldo
- 8 tipos de animacion: Fade, Slide (4 direcciones), Zoom, Flip Horizontal/Vertical

### Modo Slider (v2.0)
- Navegacion completa de galeria desde PLP
- **Navegacion**: Flechas, Seguimiento de mouse (desktop), Swipe (mobile), Click en indicadores
- **Indicadores**: Barras proporcionales, Puntos, Pills (punto activo alargado), Contador (1/5), Ninguno
- **Transiciones**: Slide (carrusel), Fade (fundido), Instantaneo
- Configuracion independiente desktop/mobile
- Hover flip opcional (avance automatico a imagen 2 al hacer hover)
- Navegacion circular y retorno automatico al sacar el mouse

### Productos Configurables
- Recopila imagenes de todos los hijos (no solo el primero)
- **Imagenes por variante**: limite configurable (ej: 1 = una foto por color)
- **Integracion con ConfigurableGallery**: filtra imagenes del padre por variante cuando `Rollpix_ConfigurableGallery` esta instalado

### General
- Habilitar/deshabilitar por ubicacion (categorias, widgets, busqueda, relacionados, CMS, Page Builder)
- Carga lazy (imagenes al primer hover)
- Soporte contenido dinamico (AJAX, scroll infinito, sliders)
- Respeta `prefers-reduced-motion`
- CSS facilmente personalizable desde el tema

---

## Requisitos Tecnicos

| Requisito | Version |
|---|---|
| **Nombre del modulo** | `rollpix/module-image-flip-hover` |
| **Magento** | 2.4.x |
| **PHP** | 7.4 - 8.3 |

---

## Instalacion

```bash
composer require rollpix/module-image-flip-hover:^2.0
bin/magento module:enable Rollpix_ImageFlipHover
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

Eso es todo: sirve igual para Luma y para Hyva, no hay un modulo companero que
haya que habilitar aparte.

---

## Configuracion

Ir a **Tiendas > Configuracion > ROLLPIX > Image Flip Hover**.

### Configuracion General

| Campo | Descripcion | Default |
|---|---|---|
| Habilitar | Activa/desactiva el modulo | Si |
| Modo de Hover | **Flip** (imagen alternativa) o **Slider** (galeria completa) | Flip |
| Solo Desktop | Desactiva en dispositivos moviles (< 768px) | Si |

### Configuracion del Modo Flip

| Campo | Descripcion | Default |
|---|---|---|
| Rol de Imagen Principal | Rol de imagen para el hover | Segunda Imagen de Galeria |
| Rol de Imagen de Respaldo | Se usa si no se encuentra el rol principal | Segunda Imagen de Galeria |
| Tipo de Animacion | Efecto de transicion | Fade |
| Velocidad de Animacion (ms) | Duracion de la animacion | 300 |

### Configuracion del Modo Slider

| Campo | Descripcion | Default |
|---|---|---|
| Habilitar Hover Flip | Avanzar automaticamente a imagen 2 al hacer hover | Si |
| Tipo de Transicion | Slide / Fade / Instantaneo | Fade |
| Velocidad de Transicion (ms) | Duracion de la transicion | 250 |
| Maximo de Imagenes | Maximo de imagenes de galeria por producto (2-20) | 8 |
| Imagenes por Variante | Para configurables: max imagenes por hijo. 0 = todas | 0 |
| Navegacion Circular | Navegacion circular (ultima → primera) | No |
| Volver a Imagen Principal | Volver a imagen 1 al sacar el mouse | Si |

**Navegacion Desktop** (multiselect): Flechas, Seguimiento de mouse, Click en indicadores

**Navegacion Mobile** (multiselect): Flechas, Deslizar (Tactil), Click en indicadores

**Indicadores** (por dispositivo): Barras, Puntos, Pills, Contador, Ninguno — con posicion (arriba/abajo/debajo)

### Ubicaciones

Habilitar/deshabilitar para: Paginas de Categoria, Widgets, Busqueda, Relacionados/Upsell/Cross-sell, Bloques CMS, Page Builder.

---

## Productos Configurables

El slider recopila imagenes de **todos los hijos** de un producto configurable. Combinado con el campo **Imagenes por Variante**:

| Valor | Resultado (3 colores × 7 fotos cada uno) |
|---|---|
| `0` (todas) | Muestra hasta 8 imagenes (limite max_images) |
| `1` | Muestra 3 imagenes (primera foto de cada color) |
| `2` | Muestra 6 imagenes (primeras 2 de cada color) |

### Integracion con ConfigurableGallery

Cuando `Rollpix_ConfigurableGallery` esta instalado, el slider lee la columna `associated_attributes` para agrupar las imagenes del padre por variante. Funciona cuando las fotos estan subidas al producto configurable padre y asociadas a colores via la UI de admin de ConfigurableGallery.

---

## Soporte Hyva

Hyva funciona sin tocar nada. El modulo trae dos builds del mismo slider y elige
uno segun el tema activo:

| Tema | Build | Como se carga |
|---|---|---|
| Luma y derivados | `js/hover-slider.js` (AMD + jQuery) | `x-magento-init` |
| Hyva y derivados | `js/hover-slider-standalone.js` (vanilla) | `<script defer>` comun |

La deteccion recorre la cadena de herencia del tema, asi que un tema hijo de
`Hyva/default` se reconoce como Hyva. El markup, las clases CSS y todas las
funciones --flip, slider, tipos de navegacion, indicadores-- son las mismas en
los dos.

> **Obsoleto**: `Rollpix_ImageFlipHoverHyvaCompat` era el que traia el build
> vanilla y habia que habilitarlo a mano. Desde 2.2.0 no hace nada; da igual si
> queda habilitado o deshabilitado.

---

## Solucion de Problemas

### No se ve el flip/slider
1. Verificar que el modulo esta habilitado y la ubicacion activa
2. Verificar que el producto tiene 2+ imagenes en su galeria
3. Limpiar cache: `bin/magento cache:flush`

### Las imagenes no cargan al hacer hover
1. Redesplegar contenido estatico: `bin/magento setup:static-content:deploy -f`
2. Revisar la consola del navegador por errores de JS

### Error despues de actualizar
```bash
rm -rf generated/code/*
bin/magento setup:di:compile
bin/magento cache:flush
```

---

## Changelog

Ver [CHANGELOG.md](CHANGELOG.md) para el historial completo de versiones.

---

## Licencia

MIT License

---

## Soporte

- **Issues**: https://github.com/ROLLPIX/M2-ImageFlipHover/issues
- **Website**: [www.rollpix.com](https://www.rollpix.com)

---

Hecho con ❤️ por [ROLLPIX](https://www.rollpix.com)
