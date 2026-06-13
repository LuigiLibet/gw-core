#!/bin/bash

# ⚠️  AVISO DE SEGURIDAD
# Pasar el token como argumento lo deja en el historial del shell (~/.zsh_history,
# ~/.bash_history) y en la lista de procesos. Prefiere uno de estos métodos:
#   - GitHub CLI:        gh auth login   (luego: git push)
#   - Credential helper: git config --global credential.helper osxkeychain
# Este script se conserva solo como fallback puntual. Ver AUTHENTICATION.md.
#
# Script para hacer push usando un token de GitHub
# Uso: ./scripts/push-with-token.sh TU_TOKEN_AQUI

if [ -z "$1" ]; then
    echo "Error: Debes proporcionar tu token de GitHub"
    echo "Uso: ./scripts/push-with-token.sh TU_TOKEN"
    exit 1
fi

TOKEN=$1

# Configurar el remote con el token
git remote set-url origin https://${TOKEN}@github.com/LuigiLibet/gw-core.git

# Hacer push
echo "Haciendo push de commits..."
git push origin main

echo "Haciendo push de tags..."
git push origin --tags

echo "✓ Push completado!"

# Restaurar el remote sin el token (por seguridad)
git remote set-url origin https://github.com/LuigiLibet/gw-core.git

echo "✓ Remote restaurado a URL normal"
