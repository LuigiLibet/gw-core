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

# Obtener la versión actual del manifest (compatible con macOS/BSD)
CURRENT_VERSION=$(grep '"version"' manifest.json | sed -E 's/.*"version"[[:space:]]*:[[:space:]]*"([^"]+)".*/\1/' | sed 's/v//')
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
git push origin main
git push origin "${NEW_TAG}"
echo -e "${GREEN}✓ Push completado${NC}"

echo ""
echo -e "${GREEN}✓ Proceso completado exitosamente!${NC}"
echo -e "${GREEN}El workflow de GitHub Actions actualizará automáticamente el manifest.json${NC}"
echo -e "${YELLOW}Puedes verificar el progreso en: https://github.com/$(git config --get remote.origin.url | sed 's/.*github.com[:/]\(.*\)\.git/\1/')/actions${NC}"
