#!/bin/sh
cd "$(dirname "$0")" || exit 1

if [ ! -f public/router.php ]; then
  echo "ไม่พบไฟล์โปรแกรม เปิดเทอร์มินัลในโฟลเดอร์ company-calendar แล้วรัน sh start.sh"
  exit 1
fi

if ! command -v php >/dev/null 2>&1; then
  echo "ยังไม่มีคำสั่ง php บนเครื่องนี้"
  echo "บน Mac ติดตั้งด้วย: brew install php"
  exit 1
fi

echo "กำลังเปิดปฏิทินที่ http://127.0.0.1:8080"
echo "อย่าปิดหน้าต่างนี้ ถ้าปิดแล้วเปิดเบราว์เซอร์ จะขึ้น Error Code: -102"
if command -v open >/dev/null 2>&1; then
  (sleep 1 && open "http://127.0.0.1:8080/") >/dev/null 2>&1 &
fi
php -S 127.0.0.1:8080 -t public public/router.php
