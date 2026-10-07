# GitHub Authentication Setup

> **Current recommendation (most secure):** use the **GitHub CLI** (`gh auth login`) or the
> **macOS credential helper** (`git config --global credential.helper osxkeychain`).
> Both store the token in the system keychain and keep it out of the command line and your
> shell history. The manual flow below and `scripts/push-with-token.sh` are kept only as a
> fallback.

## Option 1: Personal Access Token (PAT)

### Step 1: Create a Personal Access Token

1. Go to GitHub: https://github.com/settings/tokens
2. Click "Generate new token" → "Generate new token (classic)"
3. Give it a name (e.g. "gw-core-releases")
4. Select the scopes:
   - ✅ `repo` (full access to private repositories)
   - ✅ `workflow` (update workflow files) - **IMPORTANT if you will modify workflows**
5. Click "Generate token"
6. **IMPORTANT**: Copy the token right away (it is only shown once)

### Step 2: Configure Git to use the token

You have two options:

#### Option A: Put the token directly in the URL (temporary)
```bash
git remote set-url origin https://YOUR_TOKEN@github.com/LuigiLibet/gw-core.git
```

#### Option B: Use the Git Credential Helper (recommended)
```bash
# Configure the credential helper for macOS
git config --global credential.helper osxkeychain

# The first time you push, Git will ask for:
# Username: your_github_username
# Password: paste_your_token_here
```

### Step 3: Test the push
```bash
git push origin main
git push origin v1.2.2
```

## Option 2: Configure SSH (if you prefer)

### Step 1: Check your public key
```bash
cat ~/.ssh/id_ed25519.pub
```

### Step 2: Add the key to GitHub
1. Copy the contents of the public key
2. Go to: https://github.com/settings/keys
3. Click "New SSH key"
4. Paste the key and save

### Step 3: Switch the remote to SSH
```bash
git remote set-url origin git@github.com:LuigiLibet/gw-core.git
```

### Step 4: Test
```bash
ssh -T git@github.com
git push origin main
```

## Option 3: GitHub CLI (gh)

If you have the GitHub CLI installed:
```bash
gh auth login
```

Git will then use the `gh` credentials automatically.
