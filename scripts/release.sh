#!/bin/bash

# Script para crear una nueva versión y actualizar el manifest automáticamente
# Uso: ./scripts/release.sh [patch|minor|major] [mensaje de commit]

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar que estamos en el directorio correcto
if [ ! -f "manifest.json" ]; then
    echo -e "${RED}Error: No se encontró manifest.json. Asegúrate de estar en el directorio raíz del proyecto.${NC}"
    exit 1
fi

# Obtener la versión actual del último tag de git (más confiable que manifest.json)
LATEST_TAG=$(git tag --sort=-version:refname | grep -E '^v[0-9]+\.[0-9]+\.[0-9]+$' | head -1)

if [ -z "$LATEST_TAG" ]; then
    # Si no hay tags, usar el manifest.json como fallback
    CURRENT_VERSION=$(grep '"version"' manifest.json | sed -E 's/.*"version"[[:space:]]*:[[:space:]]*"([^"]+)".*/\1/' | sed 's/v//')
    echo -e "${YELLOW}No se encontraron tags, usando versión del manifest: v${CURRENT_VERSION}${NC}"
else
    CURRENT_VERSION=$(echo $LATEST_TAG | sed 's/v//')
    echo -e "${YELLOW}Último tag encontrado: ${LATEST_TAG}${NC}"
fi

CURRENT_MAJOR=$(echo $CURRENT_VERSION | cut -d. -f1)
CURRENT_MINOR=$(echo $CURRENT_VERSION | cut -d. -f2)
CURRENT_PATCH=$(echo $CURRENT_VERSION | cut -d. -f3)

# Determinar el tipo de versión
VERSION_TYPE=${1:-patch}

# Calcular nueva versión
case $VERSION_TYPE in
    patch)
        NEW_PATCH=$((CURRENT_PATCH + 1))
        NEW_VERSION="${CURRENT_MAJOR}.${CURRENT_MINOR}.${NEW_PATCH}"
        ;;
    minor)
        NEW_VERSION="${CURRENT_MAJOR}.$((CURRENT_MINOR + 1)).0"
        ;;
    major)
        NEW_VERSION="$((CURRENT_MAJOR + 1)).0.0"
        ;;
    *)
        echo -e "${RED}Error: Tipo de versión inválido. Usa: patch, minor o major${NC}"
        exit 1
        ;;
esac

NEW_TAG="v${NEW_VERSION}"

# Mensaje de commit
COMMIT_MSG=${2:-"Version ${NEW_VERSION}"}

echo -e "${YELLOW}Versión actual: v${CURRENT_VERSION}${NC}"
echo -e "${YELLOW}Nueva versión: ${NEW_TAG}${NC}"
echo -e "${YELLOW}Mensaje de commit: ${COMMIT_MSG}${NC}"
echo ""

# Verificar si hay cambios sin commitear
if ! git diff-index --quiet HEAD --; then
    echo -e "${GREEN}Haciendo commit de los cambios...${NC}"
    git add .
    git commit -m "${COMMIT_MSG}"
    echo -e "${GREEN}✓ Cambios commiteados${NC}"
else
    echo -e "${YELLOW}No hay cambios para commitear${NC}"
fi

# Verificar si el tag ya existe
if git rev-parse "${NEW_TAG}" >/dev/null 2>&1; then
    echo -e "${RED}Error: El tag ${NEW_TAG} ya existe${NC}"
    exit 1
fi

# Crear el tag
echo -e "${GREEN}Creando tag ${NEW_TAG}...${NC}"
git tag "${NEW_TAG}"
echo -e "${GREEN}✓ Tag creado${NC}"

# Hacer push de commits y tags
echo -e "${GREEN}Haciendo push de commits y tags...${NC}"
if git push origin main && git push origin "${NEW_TAG}"; then
    echo -e "${GREEN}✓ Push completado${NC}"
else
    echo ""
    echo -e "${YELLOW}⚠️  Error de autenticación al hacer push${NC}"
    echo -e "${YELLOW}El commit y el tag se crearon localmente, pero necesitas autenticarte para hacer push.${NC}"
    echo ""
    echo -e "${YELLOW}Para resolver esto:${NC}"
    echo -e "1. Lee el archivo AUTHENTICATION.md para instrucciones"
    echo -e "2. O ejecuta manualmente:"
    echo -e "   ${GREEN}git push origin main${NC}"
    echo -e "   ${GREEN}git push origin ${NEW_TAG}${NC}"
    echo ""
    exit 1
fi

echo ""
echo -e "${GREEN}✓ Proceso completado exitosamente!${NC}"
echo -e "${GREEN}El workflow de GitHub Actions actualizará automáticamente el manifest.json${NC}"
echo -e "${YELLOW}Puedes verificar el progreso en: https://github.com/$(git config --get remote.origin.url | sed 's/.*github.com[:/]\(.*\)\.git/\1/')/actions${NC}"
