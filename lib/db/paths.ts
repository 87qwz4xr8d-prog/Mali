import path from "node:path";

/** โฟลเดอร์ข้อมูลถาวร (SQLite + uploads) — โปรดักชันตั้ง MALI_DATA_DIR ได้ */
export function getDataDir() {
  return process.env.MALI_DATA_DIR || path.join(process.cwd(), "data");
}

export function getDbPath() {
  return path.join(getDataDir(), "mali.db");
}

export function getUploadsDir() {
  return path.join(getDataDir(), "uploads");
}

/**
 * ที่เก็บชุดสำรอง
 * - MALI_BACKUP_DIR ถ้าตั้งไว้
 * - ไม่เช่นนั้น $MALI_DATA_DIR/backups หรือ ./data/backups
 * บนเซิร์ฟเวอร์แนะนำ /var/backups/mali ผ่าน env
 */
export function getBackupRoot() {
  return process.env.MALI_BACKUP_DIR || path.join(getDataDir(), "backups");
}

export function getBackupKeep() {
  const n = Number(process.env.MALI_BACKUP_KEEP || "14");
  return Number.isFinite(n) && n > 0 ? Math.floor(n) : 14;
}
