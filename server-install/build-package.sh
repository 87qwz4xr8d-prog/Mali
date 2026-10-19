#!/usr/bin/env bash
# สร้างชุดติดตั้ง Server + แปลง docx + แพ็กเกจ tar.gz/zip
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="${ROOT}/docs/policies"
PKG="${ROOT}/server-install"
DIST="${ROOT}/dist"
VERSION="$(tr -d '[:space:]' < "${PKG}/VERSION")"
STAGE_NAME="mali-itsec-server-install"
STAGE="${DIST}/${STAGE_NAME}"
ARCHIVE="${DIST}/Mali-IT-Security-Policy-Server-Install-v${VERSION}.tar.gz"
ZIP_ARCHIVE="${DIST}/Mali-IT-Security-Policy-Server-Install-v${VERSION}.zip"

echo "[INFO] build pack v${VERSION}"

# แปลง markdown ทั้งหมดเป็น docx
for md in "${SRC}"/*.md; do
  base="$(basename "${md}" .md)"
  pandoc "${md}" -o "${SRC}/${base}.docx" --from markdown --to docx
  echo "[OK] docx ${base}"
done

# เตรียม stage
rm -rf "${STAGE}"
mkdir -p "${STAGE}"/{policies,www/policies,www/assets,www/files,bin,nginx}
mkdir -p "${PKG}/policies" "${PKG}/www/policies"

# คัดลอกเอกสาร
find "${SRC}" -maxdepth 1 \( -name '*.md' -o -name '*.docx' \) ! -name 'Mali-*' -print0 |
while IFS= read -r -d '' f; do
  cp -a "$f" "${PKG}/policies/"
  cp -a "$f" "${STAGE}/policies/"
  cp -a "$f" "${STAGE}/www/files/"
done

# สร้าง HTML
for md in "${SRC}"/*.md; do
  base="$(basename "${md}" .md)"
  out="${PKG}/www/policies/${base}.html"
  pandoc "${md}" -o "${out}" --from markdown --to html5 --standalone \
    --metadata title="${base}" --css="../assets/doc.css" -V lang=th
  python3 "${PKG}/bin/postprocess_html.py" "${out}"
  cp -a "${out}" "${STAGE}/www/policies/"
done

# คัดลอกสคริปต์/เว็บ
mkdir -p "${STAGE}/www/assets"
cp -a "${PKG}/www/index.html" "${STAGE}/www/"
cp -a "${PKG}/www/assets/." "${STAGE}/www/assets/"
cp -a "${PKG}/install.sh" "${PKG}/uninstall.sh" "${PKG}/INSTALL.md" \
  "${PKG}/VERSION" "${PKG}/config.env.example" "${STAGE}/"
cp -a "${PKG}/bin/." "${STAGE}/bin/"
cp -a "${PKG}/nginx/." "${STAGE}/nginx/"
chmod 755 "${STAGE}/install.sh" "${STAGE}/uninstall.sh" "${STAGE}/bin/"*.sh "${STAGE}/bin/postprocess_html.py"

# MANIFEST
{
  echo "Mali IT Security Policy Server Install Pack"
  echo "version=${VERSION}"
  echo "iso9001=STD-QMS-001"
  echo "built_at=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
  echo
  find "${STAGE}" -type f | sed "s|${STAGE}/||" | sort
} | tee "${STAGE}/MANIFEST.txt" > "${PKG}/MANIFEST.txt"

# รวม zip เอกสารอย่างเดียว
DOC_ZIP="${SRC}/Mali-IT-Security-Policy.zip"
rm -f "${DOC_ZIP}"
( cd "${ROOT}" && zip -qr "${DOC_ZIP}" README.md docs/policies/*.md docs/policies/*.docx \
  -x 'docs/policies/Mali-IT-Security-Policy.zip' \
  -x 'docs/policies/Mali-IT-Security-Policy-Server-Install-*' )

# tarball + zip ชุดติดตั้ง
mkdir -p "${DIST}" /opt/cursor/artifacts
tar -C "${DIST}" -czf "${ARCHIVE}" "${STAGE_NAME}"
rm -f "${ZIP_ARCHIVE}"
( cd "${DIST}" && zip -qr "${ZIP_ARCHIVE}" "${STAGE_NAME}" )
cp -a "${ARCHIVE}" "${ZIP_ARCHIVE}" "${SRC}/"
cp -a "${ARCHIVE}" "${ZIP_ARCHIVE}" /opt/cursor/artifacts/

# healthcheck บน stage (www/files เป็นโฟลเดอร์จริงใน stage)
INSTALL_ROOT="${STAGE}" bash "${STAGE}/bin/healthcheck.sh"

echo "[INFO] done"
ls -lh "${ARCHIVE}" "${ZIP_ARCHIVE}" "${DOC_ZIP}"
