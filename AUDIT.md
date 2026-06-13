# Auditoría de GW Core

> Fecha: 2026-06-12
> Alcance: framework PHP (`gw-custom-blocks.php`), plantillas de los 8 bloques incluidos,
> núcleo JS del editor, scripts de release y workflow de CI.

Veredicto general: el framework está bien diseñado (registro declarativo de bloques,
escaping consistente en casi todas las plantillas, endpoints REST con `permission_callback`,
sin credenciales hardcodeadas). Esta auditoría lista los hallazgos accionables, ordenados
por prioridad.

**Prioridad recomendada para el próximo release:** #1, #2 y #6.

---

## 🔴 Seguridad

### 1. PHP Object Injection en `gw_get_repeater_items()`
- **Archivo:** `gw-custom-blocks/gw-custom-blocks.php:636`
- **Severidad:** Alta — explotable por cualquier usuario con permiso para editar posts.
- **Problema:** El fallback acepta explícitamente strings que empiezan con `O:`
  (objetos serializados) y los pasa a `@unserialize()` sin restricciones. Los atributos
  de bloque los controla el editor, así que un autor podría inyectar un objeto serializado
  y detonar un gadget chain (POP) si algún plugin instalado tiene clases explotables.
- **Fix:**
  ```php
  // Eliminar la rama que acepta 'O:' (un repeater nunca es un objeto) y blindar unserialize:
  $unserialized = @unserialize($trimmed, array('allowed_classes' => false));
  ```
- **Estado:** ✅ Resuelto

### 2. URL sin validar en Link Wrapper (XSS almacenado)
- **Archivo:** `included-blocks/link-wrapper/view.php:13`
- **Severidad:** Alta — XSS almacenado para roles sin `unfiltered_html` (autores).
- **Problema:** El atributo `url` va directo al `href` sin pasar por `esc_url()`.
  `get_block_wrapper_attributes()` aplica `esc_attr`, que **no** bloquea
  `javascript:alert(1)`.
- **Fix:** Aplicar `esc_url()` al href y validar `target` contra una whitelist
  (`_self`, `_blank`, `_parent`, `_top`), igual que ya se hace con `wrapperTag` en el menú.
  ```php
  if (!is_admin() && !empty($href)) {
      $wrapper_args['href'] = esc_url($href);
      $allowed_targets = array('_self', '_blank', '_parent', '_top');
      if (!empty($attributes['target']) && in_array($attributes['target'], $allowed_targets, true)) {
          $wrapper_args['target'] = $attributes['target'];
      }
      // ...rel
  }
  ```
- **Estado:** ✅ Resuelto

### 3. Swiper desde CDN sin SRI
- **Archivo:** `included-blocks.php:489`
- **Severidad:** Media — riesgo de supply chain.
- **Problema:** Se carga JS/CSS de jsDelivr en el frontend de todos los sitios cliente
  sin atributo `integrity`. Si el CDN se compromete, todos los sitios ejecutan código
  de terceros.
- **Fix:** Empaquetar Swiper localmente dentro del plugin, o añadir SRI
  (`integrity` + `crossorigin`) a los handles registrados.
- **Estado:** ✅ Resuelto

---

## 🟡 Bugs / fragilidad

### 4. Dependencias ocultas del theme (no documentadas)
- **Severidad:** Media — el bloque se rompe fuera del theme GlitchWood.
- **Problema:** El plugin asume cosas que no documenta en ningún sitio:
  - **CSS de iconos:** Share Icons emite `<i class="icon-facebook">`
    (`included-blocks/share-icons/view.php:83`) pero ninguna hoja del plugin define esas
    clases. Si el theme no las trae, los iconos no se ven.
  - **JS del menú móvil:** `included-blocks/navigation-menu/view.php:106` imprime
    `#menu_trigger` y `#mobile_menu_container`, pero el JS que los activa vive en el theme,
    no en el plugin.
- **Fix:** Documentar ambas dependencias en el README, o resolverlas dentro del plugin
  (incluir CSS de iconos base y JS del toggle móvil).
- **Estado:** ✅ Resuelto

### 5. Detección de editor inconsistente
- **Archivos:** `included-blocks/navigation-menu/view.php:52`,
  `included-blocks/link-wrapper/view.php:42,56`
- **Severidad:** Media — render incorrecto en la preview del editor.
- **Problema:** Hay tres formas distintas de detectar "estoy en el editor":
  `gw_in_editor()` (la robusta, en el framework), `defined('REST_REQUEST') && REST_REQUEST`,
  e `is_admin()`. `is_admin()` es **false** durante un render REST del editor, así que el
  Link Wrapper probablemente renderiza como `<a>` en la preview en lugar de `<div>` —
  justo lo que el comentario dice querer evitar.
- **Fix:** Unificar toda la detección de contexto editor en `gw_in_editor()`.
- **Estado:** ✅ Resuelto

### 6. `GW_CORE_VERSION` se usa pero nunca se define
- **Archivo:** `init.php:53,65,84`
- **Severidad:** Media-Alta — posible fatal en PHP 8.
- **Problema:** La constante se pasa a `wp_enqueue_*` como versión de cache-busting, pero
  no existe ningún `define('GW_CORE_VERSION', ...)` en el repo. En PHP 8 una constante
  indefinida es un `Error` fatal; en PHP 7 es un warning + se usa el string literal
  `"GW_CORE_VERSION"` (cache-busting roto).
- **Fix:** Definir la constante, idealmente leyendo la versión desde `manifest.json`:
  ```php
  if (!defined('GW_CORE_VERSION')) {
      $manifest = @json_decode(@file_get_contents(__DIR__ . '/manifest.json'), true);
      define('GW_CORE_VERSION', $manifest['version'] ?? '1.0.0');
  }
  ```
- **Estado:** ✅ Resuelto

---

## 🟢 Mantenimiento

### 7. Convenciones de ruta inconsistentes en `init.php`
- **Archivo:** `init.php:44,76` vs `gw-custom-blocks.php`
- **Problema:** `init.php` hardcodea `get_stylesheet_directory_uri() . '/components/gw-core/'`
  mientras que `gw-custom-blocks.php` usa `/gw/gw-core/`. Dos convenciones distintas en el
  mismo plugin — una de las dos enqueues carga assets desde una ruta equivocada.
- **Estado:** ✅ Resuelto

### 8. `uniqid()` para IDs de slider
- **Archivo:** `included-blocks/slider/view.php:49`
- **Problema:** No es criptográfico y puede colisionar. El ID no se usa para el init
  (se hace por clase `.gw-slider`), así que se puede eliminar o usar un contador estático.
- **Estado:** ✅ Resuelto

### 9. Parser PHP-serialize manual en JS (código muerto)
- **Archivo:** `gw-custom-blocks/lib/utils.js:28`
- **Problema:** ~90 líneas de regex frágil que en la práctica nunca se ejercita, porque
  `phpSerialize` ya emite JSON. Código muerto de alto riesgo.
- **Fix:** Borrarlo y dejar solo el camino JSON.
- **Estado:** ✅ Resuelto

### 10. `print_r` de debug comentado
- **Archivo:** `included-blocks/link-wrapper/view.php:70`
- **Problema:** Línea de debug comentada. Limpieza menor.
- **Estado:** ✅ Resuelto

### 11. Polling con `setInterval` para esperar `GW_CUSTOM_BLOCKS`
- **Archivo:** `gw-custom-blocks/core/block-registry.js:14`
- **Problema:** Espera la variable global por polling. Las dependencias de script ya están
  declaradas correctamente vía `wp_register_script`, así que `wp_localize_script` debería
  garantizar el orden y el polling probablemente sea innecesario.
- **Decisión:** Conservado por diseño. Es código defensivo de bajo costo (timeout de 5s)
  que protege contra plugins que difieran o reordenen scripts. Eliminarlo arriesga
  regresiones de registro de bloques sin ganancia funcional. Evaluado y retenido.
- **Estado:** ☑️ Evaluado (sin cambios)

### 12. Flujo de tokens manual en docs/scripts
- **Archivos:** `AUTHENTICATION.md`, `scripts/push-with-token.sh`
- **Problema:** Documentan un flujo manual de PAT que invita a pegar tokens en la terminal
  (quedan en el historial de shell). Con `gh` CLI o el credential helper de macOS, estos
  podrían retirarse.
- **Estado:** ✅ Resuelto

---

## ✅ Lo que está bien
- Escaping correcto y consistente en meta-tag, footer-text, share-icons y el bloque demo.
- Validación de `wrapperTag` / `tag` contra whitelists.
- `permission_callback` en ambos endpoints REST (`/posts`, `/terms`).
- `.gitignore` completo (incluye los `._*` de macOS, que no están trackeados).
- Manejo elegante de menú borrado en el editor (placeholders informativos).
- Sin credenciales hardcodeadas en el repo.
