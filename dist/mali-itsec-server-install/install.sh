#!/usr/bin/env bash
# Mali IT Security Policy — Server Installer
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ -f "${SCRIPT_DIR}/config.env" ]]; then
  # shellcheck source=/dev/null
  source "${SCRIPT_DIR}/config.env"
fi

INSTALL_ROOT="${INSTALL_ROOT:-/opt/mali/itsec-policies}"
WEB_USER="${WEB_USER:-}"
WEB_GROUP="${WEB_GROUP:-}"
INSTALL_NGINX_SITE="${INSTALL_NGINX_SITE:-no}"
NGINX_SITE_NAME="${NGINX_SITE_NAME:-mali-itsec-policies}"
CREATE_SYMLINK_WWW="${CREATE_SYMLINK_WWW:-no}"
WWW_LINK_PATH="${WWW_LINK_PATH:-/var/www/mali-itsec-policies}"

log() { printf '[INFO] %s\n' "$*"; }
warn() { printf '[WARN] %s\n' "$*" >&2; }
err() { printf '[ERROR] %s\n' "$*" >&2; exit 1; }

[[ "${EUID}" -eq 0 ]] || err "กรุณารันด้วย sudo"

if [[ -z "${WEB_USER}" || -z "${WEB_GROUP}" ]]; then
  if id www-data >/dev/null 2>&1; then WEB_USER=www-data; WEB_GROUP=www-data
  elif id nginx >/dev/null 2>&1; then WEB_USER=nginx; WEB_GROUP=nginx
  else WEB_USER=root; WEB_GROUP=root; warn "ใช้ owner เป็น root"
  fi
fi

VERSION="$(tr -d '[:space:]' < "${SCRIPT_DIR}/VERSION")"
log "ติดตั้ง Mali IT Security Policy Pack v${VERSION} -> ${INSTALL_ROOT}"

mkdir -p "${INSTALL_ROOT}"/{policies,www,nginx,logs,bin}
cp -a "${SCRIPT_DIR}/VERSION" "${SCRIPT_DIR}/INSTALL.md" "${SCRIPT_DIR}/MANIFEST.txt" "${INSTALL_ROOT}/" 2>/dev/null || true
cp -a "${SCRIPT_DIR}/VERSION" "${SCRIPT_DIR}/INSTALL.md" "${INSTALL_ROOT}/"
[[ -f "${SCRIPT_DIR}/MANIFEST.txt" ]] && cp -a "${SCRIPT_DIR}/MANIFEST.txt" "${INSTALL_ROOT}/"
cp -a "${SCRIPT_DIR}/policies/." "${INSTALL_ROOT}/policies/"
cp -a "${SCRIPT_DIR}/www/." "${INSTALL_ROOT}/www/"
cp -a "${SCRIPT_DIR}/nginx/." "${INSTALL_ROOT}/nginx/"
cp -a "${SCRIPT_DIR}/bin/." "${INSTALL_ROOT}/bin/"
cp -a "${SCRIPT_DIR}/uninstall.sh" "${INSTALL_ROOT}/uninstall.sh"
rm -rf "${INSTALL_ROOT}/www/files"
ln -sfn "${INSTALL_ROOT}/policies" "${INSTALL_ROOT}/www/files"

cat > "${INSTALL_ROOT}/install-info.txt" <<EOF
package=mali-itsec-policies
version=${VERSION}
installed_at=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
install_root=${INSTALL_ROOT}
web_user=${WEB_USER}
web_group=${WEB_GROUP}
iso9001_document_control=STD-QMS-001
EOF

chown -R "${WEB_USER}:${WEB_GROUP}" "${INSTALL_ROOT}"
find "${INSTALL_ROOT}" -type d -exec chmod 755 {} \;
find "${INSTALL_ROOT}" -type f -exec chmod 644 {} \;
chmod 755 "${INSTALL_ROOT}/uninstall.sh" "${INSTALL_ROOT}/bin/"*.sh || true

if [[ "${CREATE_SYMLINK_WWW}" == "yes" ]]; then
  mkdir -p "$(dirname "${WWW_LINK_PATH}")"
  ln -sfn "${INSTALL_ROOT}/www" "${WWW_LINK_PATH}"
  log "symlink ${WWW_LINK_PATH}"
fi

if [[ "${INSTALL_NGINX_SITE}" == "yes" ]] && command -v nginx >/dev/null 2>&1; then
  conf_src="${INSTALL_ROOT}/nginx/mali-itsec-policies.conf"
  if [[ -d /etc/nginx/sites-available ]]; then
    conf_dst="/etc/nginx/sites-available/${NGINX_SITE_NAME}.conf"
    sed "s|__INSTALL_ROOT__|${INSTALL_ROOT}|g" "${conf_src}" > "${conf_dst}"
    ln -sfn "${conf_dst}" "/etc/nginx/sites-enabled/${NGINX_SITE_NAME}.conf"
  elif [[ -d /etc/nginx/conf.d ]]; then
    conf_dst="/etc/nginx/conf.d/${NGINX_SITE_NAME}.conf"
    sed "s|__INSTALL_ROOT__|${INSTALL_ROOT}|g" "${conf_src}" > "${conf_dst}"
  fi
  nginx -t && systemctl reload nginx || warn "reload nginx ไม่สำเร็จ"
fi

log "ติดตั้งเสร็จ: ${INSTALL_ROOT}"
log "ทดสอบ: ${INSTALL_ROOT}/bin/serve-local.sh"
log "ตรวจสุขภาพ: ${INSTALL_ROOT}/bin/healthcheck.sh"
