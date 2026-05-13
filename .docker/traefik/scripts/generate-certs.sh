#!/usr/bin/env bash
# generate-certs.sh
#
# Generates locally-trusted TLS certificates using mkcert.
# Run this once after cloning the repository, or whenever you need to
# add new domains to the certificate.
#
# Prerequisites:
#   - mkcert must be installed: https://github.com/FiloSottile/mkcert
#     Ubuntu/Debian:  sudo apt install mkcert
#     Arch:           sudo pacman -S mkcert
#     macOS:          brew install mkcert
#     Windows (WSL2): install mkcert on both Windows and WSL2 sides,
#                     then run `mkcert -install` in both environments.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../../.." && pwd)"
CERTS_DIR="${PROJECT_ROOT}/.docker/traefik/certs"

# ── Dependency check ───────────────────────────────────────────────────────────
if ! command -v mkcert &>/dev/null; then
  echo ""
  echo "ERROR: mkcert is not installed or not in PATH."
  echo ""
  exit 1
fi

# ── Install local CA (idempotent) ──────────────────────────────────────────────
echo ">>> Installing mkcert local CA (sudo may be required)..."
echo ""
mkcert -install

# ── Generate certificate ───────────────────────────────────────────────────────
mkdir -p "${CERTS_DIR}"

echo ""
echo ">>> Generating certificate for:"
echo ""
echo "- *.docker"
echo "- localhost"
echo "- 127.0.0.1"
echo "- ::1"
echo ""
echo ""
echo ">>> Output files:"
echo ""
echo "- ${CERTS_DIR}/local.crt"
echo "- ${CERTS_DIR}/local.key"
echo ""

mkcert \
  -cert-file "${CERTS_DIR}/local.crt" \
  -key-file  "${CERTS_DIR}/local.key" \
  "*.docker" \
  "localhost" \
  "127.0.0.1" \
  "::1"

echo ""
echo ">>> Certificate generated successfully !"
echo ""
echo "────────────────────────────────────────────────────────────────────────────"
echo "!!! Next step: DON'T FORGET to add your local domains to your hosts file !!!"
echo "────────────────────────────────────────────────────────────────────────────"
echo ""
echo "- Add a line like the following in your hosts file:"
echo ""
echo " 127.0.0.1 ::1 sylius.docker mail.sylius.docker traefik.docker"
echo ""
echo ""
