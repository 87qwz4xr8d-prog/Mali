#!/usr/bin/env bash
set -euo pipefail
INSTALL_ROOT="${INSTALL_ROOT:-/opt/mali/itsec-policies}"
NGINX_SITE_NAME="${NGINX_SITE_NAME:-mali-itsec-policies}"
WWW_LINK_PATH="${WWW_LINK_PATH:-/var/www/mali-itsec-policies}"
[[ "${EUID}" -eq 0 ]] || { echo "ต้องใช้ sudo" >&2; exit 1; }
rm -f "/etc/nginx/sites-enabled/${NGINX_SITE_NAME}.conf" "/etc/nginx/sites-available/${NGINX_SITE_NAME}.conf" "/etc/nginx/conf.d/${NGINX_SITE_NAME}.conf" 2>/dev/null || true
command -v nginx >/dev/null && nginx -t >/dev/null 2>&1 && systemctl reload nginx || true
[[ -L "${WWW_LINK_PATH}" ]] && rm -f "${WWW_LINK_PATH}"
[[ -d "${INSTALL_ROOT}" ]] && rm -rf "${INSTALL_ROOT}"
echo "[INFO] ถอนการติดตั้งเสร็จ"
