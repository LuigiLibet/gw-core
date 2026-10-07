#!/bin/bash

# ⚠️  SECURITY NOTICE
# Passing the token as an argument leaves it in your shell history (~/.zsh_history,
# ~/.bash_history) and in the process list. Prefer one of these methods instead:
#   - GitHub CLI:        gh auth login   (then: git push)
#   - Credential helper: git config --global credential.helper osxkeychain
# This script is kept only as a one-off fallback. See AUTHENTICATION.md.
#
# Script to push using a GitHub token
# Usage: ./scripts/push-with-token.sh YOUR_TOKEN_HERE

if [ -z "$1" ]; then
    echo "Error: You must provide your GitHub token"
    echo "Usage: ./scripts/push-with-token.sh YOUR_TOKEN"
    exit 1
fi

TOKEN=$1

# Point the remote at the token-authenticated URL
git remote set-url origin https://${TOKEN}@github.com/LuigiLibet/gw-core.git

# Push
echo "Pushing commits..."
git push origin main

echo "Pushing tags..."
git push origin --tags

echo "✓ Push completed!"

# Restore the remote without the token (for security)
git remote set-url origin https://github.com/LuigiLibet/gw-core.git

echo "✓ Remote restored to the normal URL"
