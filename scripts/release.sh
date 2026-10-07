#!/bin/bash

# Script to create a new version and update the manifest automatically
# Usage: ./scripts/release.sh [patch|minor|major] [commit message]

set -e

# Output colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Make sure we are in the right directory
if [ ! -f "manifest.json" ]; then
    echo -e "${RED}Error: manifest.json not found. Make sure you are in the project root directory.${NC}"
    exit 1
fi

# Get the current version from the latest git tag (more reliable than manifest.json)
LATEST_TAG=$(git tag --sort=-version:refname | grep -E '^v[0-9]+\.[0-9]+\.[0-9]+$' | head -1)

if [ -z "$LATEST_TAG" ]; then
    # If there are no tags, fall back to manifest.json
    CURRENT_VERSION=$(grep '"version"' manifest.json | sed -E 's/.*"version"[[:space:]]*:[[:space:]]*"([^"]+)".*/\1/' | sed 's/v//')
    echo -e "${YELLOW}No tags found, using the manifest version: v${CURRENT_VERSION}${NC}"
else
    CURRENT_VERSION=$(echo $LATEST_TAG | sed 's/v//')
    echo -e "${YELLOW}Latest tag found: ${LATEST_TAG}${NC}"
fi

CURRENT_MAJOR=$(echo $CURRENT_VERSION | cut -d. -f1)
CURRENT_MINOR=$(echo $CURRENT_VERSION | cut -d. -f2)
CURRENT_PATCH=$(echo $CURRENT_VERSION | cut -d. -f3)

# Determine the version type
VERSION_TYPE=${1:-patch}

# Calculate the new version
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
        echo -e "${RED}Error: Invalid version type. Use: patch, minor or major${NC}"
        exit 1
        ;;
esac

NEW_TAG="v${NEW_VERSION}"

# Commit message
COMMIT_MSG=${2:-"Version ${NEW_VERSION}"}

echo -e "${YELLOW}Current version: v${CURRENT_VERSION}${NC}"
echo -e "${YELLOW}New version: ${NEW_TAG}${NC}"
echo -e "${YELLOW}Commit message: ${COMMIT_MSG}${NC}"
echo ""

# Check for uncommitted changes
if ! git diff-index --quiet HEAD --; then
    echo -e "${GREEN}Committing changes...${NC}"
    git add .
    git commit -m "${COMMIT_MSG}"
    echo -e "${GREEN}✓ Changes committed${NC}"
else
    echo -e "${YELLOW}No changes to commit${NC}"
fi

# Check whether the tag already exists
if git rev-parse "${NEW_TAG}" >/dev/null 2>&1; then
    echo -e "${RED}Error: Tag ${NEW_TAG} already exists${NC}"
    exit 1
fi

# Create the tag
echo -e "${GREEN}Creating tag ${NEW_TAG}...${NC}"
git tag "${NEW_TAG}"
echo -e "${GREEN}✓ Tag created${NC}"

# Push commits and tags
echo -e "${GREEN}Pushing commits and tags...${NC}"
if git push origin main && git push origin "${NEW_TAG}"; then
    echo -e "${GREEN}✓ Push completed${NC}"
else
    echo ""
    echo -e "${YELLOW}⚠️  Authentication error while pushing${NC}"
    echo -e "${YELLOW}The commit and tag were created locally, but you need to authenticate to push.${NC}"
    echo ""
    echo -e "${YELLOW}To fix this:${NC}"
    echo -e "1. Read AUTHENTICATION.md for instructions"
    echo -e "2. Or run manually:"
    echo -e "   ${GREEN}git push origin main${NC}"
    echo -e "   ${GREEN}git push origin ${NEW_TAG}${NC}"
    echo ""
    exit 1
fi

echo ""
echo -e "${GREEN}✓ Process completed successfully!${NC}"
echo -e "${GREEN}The GitHub Actions workflow will update manifest.json automatically${NC}"
echo -e "${YELLOW}You can check progress at: https://github.com/$(git config --get remote.origin.url | sed 's/.*github.com[:/]\(.*\)\.git/\1/')/actions${NC}"
