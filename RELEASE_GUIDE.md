# Guía Rápida para Crear una Nueva Versión

## Flujo Completo

### 1. Hacer cambios en el código
Edita los archivos que necesites.

### 2. Crear la nueva versión

Tienes **dos opciones**:

#### Opción A: Desde Cursor (Recomendado)
1. Presiona `Cmd+Shift+P` (Mac) o `Ctrl+Shift+P` (Windows/Linux)
2. Escribe "Tasks: Run Task"
3. Selecciona:
   - **"Release: Patch Version"** → para cambios pequeños (1.2.3 → 1.2.4)
   - **"Release: Minor Version"** → para nuevas características (1.2.3 → 1.3.0)
   - **"Release: Major Version"** → para cambios importantes (1.2.3 → 2.0.0)

#### Opción B: Desde la Terminal
```bash
# Versión patch (1.2.3 → 1.2.4)
./scripts/release.sh patch "Descripción de cambios"

# Versión minor (1.2.3 → 1.3.0)
./scripts/release.sh minor "Nuevas características"

# Versión major (1.2.3 → 2.0.0)
./scripts/release.sh major "Cambios importantes"
```

El script automáticamente:
- ✅ Detecta el último tag
- ✅ Calcula la nueva versión
- ✅ Hace commit de los cambios
- ✅ Crea el tag
- ⚠️  Intenta hacer push (fallará por autenticación)

### 3. Hacer push con tu token

Después de que el script termine (aunque falle el push), ejecuta:

```bash
./scripts/push-with-token.sh TU_TOKEN_AQUI
```

Reemplaza `TU_TOKEN_AQUI` con tu Personal Access Token de GitHub.

**Nota:** El token se usa temporalmente y se elimina del remote después del push por seguridad.

### 4. Verificar el workflow

El workflow de GitHub Actions se ejecutará automáticamente y actualizará el `manifest.json`. Puedes verificar:

1. Ve a: https://github.com/LuigiLibet/gw-core/actions
2. O ejecuta: `git pull origin main` después de unos minutos para ver el manifest actualizado

## Resumen del Flujo

```
Cambios en código
    ↓
./scripts/release.sh patch "mensaje"
    ↓
./scripts/push-with-token.sh TU_TOKEN
    ↓
GitHub Actions actualiza manifest.json automáticamente
    ↓
✅ Versión publicada
```

## Archivos Útiles

- `scripts/release.sh` - Script principal para crear versiones
- `scripts/push-with-token.sh` - Script para hacer push con token
- `.vscode/tasks.json` - Tareas de Cursor para ejecutar desde el IDE
- `AUTHENTICATION.md` - Guía detallada de autenticación

## Tips

- **Guarda tu token de forma segura** (usa un gestor de contraseñas)
- El script detecta automáticamente el último tag, así que no necesitas preocuparte por eso
- Si olvidas hacer push, los commits y tags están en local, solo ejecuta el script de push
- El workflow tarda 1-2 minutos en actualizar el manifest.json
