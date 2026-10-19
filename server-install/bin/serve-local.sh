#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${PORT:-8080}"
cd "${ROOT}/www"
echo "http://0.0.0.0:${PORT}/"
exec python3 -m http.server "${PORT}" --bind 0.0.0.0
