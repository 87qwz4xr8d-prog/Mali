#!/bin/sh
cd "$(dirname "$0")" || exit 1

if [ ! -f public/router.php ]; then
  echo "ไม่พบไฟล์โปรแกรมในโฟลเดอร์นี้"
  exit 1
fi

echo "กำลังเปิดปฏิทินที่ http://127.0.0.1:8080"
echo "อย่าปิดหน้าต่างนี้ ถ้าปิดแล้วไปเปิดเบราว์เซอร์ จะขึ้น Error Code: -102"
php -S 127.0.0.1:8080 -t public public/router.php
