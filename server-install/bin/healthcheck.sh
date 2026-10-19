#!/usr/bin/env bash
set -euo pipefail
ROOT="${INSTALL_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
fail=0
check() { [[ -e "$1" ]] && echo "[OK] $1" || { echo "[FAIL] $1"; fail=1; }; }
echo "ตรวจที่: ${ROOT}"
check "${ROOT}/VERSION"
check "${ROOT}/www/index.html"
check "${ROOT}/policies/IT-Security-Policy.md"
check "${ROOT}/policies/Document-Control-Standard.md"
check "${ROOT}/policies/Security-Policy-Exception-Form.md"
check "${ROOT}/policies/Document-Master-List.md"
check "${ROOT}/policies/Document-Change-Request-Form.md"
check "${ROOT}/www/policies/IT-Security-Policy.html"
check "${ROOT}/www/files"
echo "md=$(find "${ROOT}/policies" -name '*.md' | wc -l) docx=$(find "${ROOT}/policies" -name '*.docx' | wc -l) html=$(find "${ROOT}/www/policies" -name '*.html' 2>/dev/null | wc -l)"
[[ "$fail" -eq 0 ]] || exit 1
echo "[INFO] ผ่าน"
