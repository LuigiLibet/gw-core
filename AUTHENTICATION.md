# Configuración de Autenticación para GitHub

## Opción 1: Personal Access Token (PAT) - Recomendado

### Paso 1: Crear un Personal Access Token

1. Ve a GitHub: https://github.com/settings/tokens
2. Click en "Generate new token" → "Generate new token (classic)"
3. Dale un nombre (ej: "gw-core-releases")
4. Selecciona los permisos:
   - ✅ `repo` (acceso completo a repositorios privados)
5. Click en "Generate token"
6. **IMPORTANTE**: Copia el token inmediatamente (solo se muestra una vez)

### Paso 2: Configurar Git para usar el token

Tienes dos opciones:

#### Opción A: Usar el token directamente en la URL (temporal)
```bash
git remote set-url origin https://TU_TOKEN@github.com/LuigiLibet/gw-core.git
```

#### Opción B: Usar Git Credential Helper (recomendado)
```bash
# Configurar el credential helper para macOS
git config --global credential.helper osxkeychain

# Luego, la primera vez que hagas push, Git te pedirá:
# Username: tu_usuario_de_github
# Password: pega_tu_token_aqui
```

### Paso 3: Probar el push
```bash
git push origin main
git push origin v1.2.2
```

## Opción 2: Configurar SSH (si prefieres)

### Paso 1: Verificar tu clave pública
```bash
cat ~/.ssh/id_ed25519.pub
```

### Paso 2: Agregar la clave a GitHub
1. Copia el contenido de la clave pública
2. Ve a: https://github.com/settings/keys
3. Click en "New SSH key"
4. Pega la clave y guarda

### Paso 3: Cambiar el remote a SSH
```bash
git remote set-url origin git@github.com:LuigiLibet/gw-core.git
```

### Paso 4: Probar
```bash
ssh -T git@github.com
git push origin main
```

## Opción 3: GitHub CLI (gh)

Si tienes GitHub CLI instalado:
```bash
gh auth login
```

Luego Git usará automáticamente las credenciales de `gh`.
