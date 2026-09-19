#!/usr/bin/env bash
# สร้างไฟล์ ZIP ชุดติดตั้ง Mali สำหรับอัปโหลดขึ้น Server
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="${1:-1.0.0}"
OUT_DIR="${ROOT}/dist"
STAGE="${OUT_DIR}/Mali"
ZIP_NAME="Mali-v${VERSION}-install.zip"

rm -rf "${STAGE}"
mkdir -p "${STAGE}"

copy_tree() {
  local src="$1"
  local dest="$2"
  mkdir -p "${dest}"
  cp -a "${src}/." "${dest}/"
}

# คัดลอกโฟลเดอร์หลัก
copy_tree "${ROOT}/config" "${STAGE}/config"
copy_tree "${ROOT}/public" "${STAGE}/public"
copy_tree "${ROOT}/sql" "${STAGE}/sql"
copy_tree "${ROOT}/src" "${STAGE}/src"
copy_tree "${ROOT}/views" "${STAGE}/views"

# ไฟล์ราก
cp -a "${ROOT}/INSTALL.txt" "${STAGE}/"
cp -a "${ROOT}/README.md" "${STAGE}/"
cp -a "${ROOT}/index.php" "${STAGE}/"

# ล้างไฟล์อัปโหลดทดสอบ เหลือแค่ .gitkeep
rm -rf "${STAGE}/public/uploads/receipts"
mkdir -p "${STAGE}/public/uploads/receipts"
touch "${STAGE}/public/uploads/receipts/.gitkeep"

# ใช้คอนฟิกตัวอย่างเป็นค่าเริ่มต้นในชุดติดตั้ง
cp "${ROOT}/config/database.example.php" "${STAGE}/config/database.php"

cd "${OUT_DIR}"
rm -f "${ZIP_NAME}"
if command -v zip >/dev/null 2>&1; then
  zip -r "${ZIP_NAME}" Mali -x "*.DS_Store" -x "*__MACOSX*"
else
  tar -czf "${ZIP_NAME%.zip}.tar.gz" Mali
  ZIP_NAME="${ZIP_NAME%.zip}.tar.gz"
fi

if [[ -d /opt/cursor/artifacts ]]; then
  mkdir -p /opt/cursor/artifacts/releases
  cp -f "${OUT_DIR}/${ZIP_NAME}" "/opt/cursor/artifacts/releases/${ZIP_NAME}"
fi

echo "สร้างชุดติดตั้งแล้ว: ${OUT_DIR}/${ZIP_NAME}"
ls -lh "${OUT_DIR}/${ZIP_NAME}"
