#!/usr/bin/env node
/**
 * CLI สำรองข้อมูลมาลี — ใช้กับ cron / systemd / Task Scheduler
 *   node scripts/mali-backup.mjs --mode auto
 *   node scripts/mali-backup.mjs --mode manual
 *   node scripts/mali-backup.mjs --list
 *   node scripts/mali-backup.mjs --prune
 */
import {
  cpSync,
  existsSync,
  mkdirSync,
  readdirSync,
  readFileSync,
  rmSync,
  statSync,
  writeFileSync,
} from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { DatabaseSync } from "node:sqlite";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const REPO_ROOT = path.resolve(__dirname, "..");

function env(name, fallback = "") {
  return process.env[name] || fallback;
}

function getDataDir() {
  return env("MALI_DATA_DIR", path.join(REPO_ROOT, "data"));
}

function getDbPath() {
  return path.join(getDataDir(), "mali.db");
}

function getUploadsDir() {
  return path.join(getDataDir(), "uploads");
}

function getBackupRoot() {
  if (process.env.MALI_BACKUP_DIR) return process.env.MALI_BACKUP_DIR;
  if (getDataDir() === "/var/lib/mali") return "/var/backups/mali";
  return path.join(getDataDir(), "backups");
}

function getKeep() {
  const n = Number(env("MALI_BACKUP_KEEP", "14"));
  return Number.isFinite(n) && n > 0 ? Math.floor(n) : 14;
}

function sqlQuote(filePath) {
  return `'${String(filePath).replaceAll("'", "''")}'`;
}

function stampNow() {
  const d = new Date();
  const p = (n) => String(n).padStart(2, "0");
  return `${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}-${p(d.getHours())}${p(d.getMinutes())}${p(d.getSeconds())}`;
}

function statusPath() {
  return path.join(getBackupRoot(), "last-run.json");
}

function listBackups() {
  const root = getBackupRoot();
  if (!existsSync(root)) return [];
  const rows = [];
  for (const name of readdirSync(root)) {
    if (name === "last-run.json") continue;
    if (!/^\d{8}-\d{6}(-\d+)?$/.test(name)) continue;
    const dir = path.join(root, name);
    let st;
    try {
      st = statSync(dir);
    } catch {
      continue;
    }
    if (!st.isDirectory()) continue;
    if (!existsSync(path.join(dir, "mali.db"))) continue;
    rows.push({ stamp: name, mtime: st.mtimeMs });
  }
  return rows.sort((a, b) => b.mtime - a.mtime);
}

function prune(keep = getKeep()) {
  const removed = [];
  for (const row of listBackups().slice(keep)) {
    rmSync(path.join(getBackupRoot(), row.stamp), { recursive: true, force: true });
    removed.push(row.stamp);
  }
  return removed;
}

function writeStatus(partial) {
  const root = getBackupRoot();
  mkdirSync(root, { recursive: true });
  let prev = {};
  try {
    if (existsSync(statusPath())) prev = JSON.parse(readFileSync(statusPath(), "utf8"));
  } catch {
    prev = {};
  }
  writeFileSync(
    statusPath(),
    JSON.stringify({ ...prev, ...partial, backup_root: root, keep: getKeep() }, null, 2),
    "utf8",
  );
}

function createBackup(mode = "auto") {
  const dbPath = getDbPath();
  if (!existsSync(dbPath)) {
    throw new Error(`ไม่พบฐานข้อมูล: ${dbPath}`);
  }
  const root = getBackupRoot();
  mkdirSync(root, { recursive: true });
  let stamp = stampNow();
  let dest = path.join(root, stamp);
  if (existsSync(dest)) {
    stamp = `${stamp}-${process.pid}`;
    dest = path.join(root, stamp);
  }
  mkdirSync(dest, { recursive: true });
  const destDb = path.join(dest, "mali.db");

  try {
    // เปิด DB แยกจากแอป แล้ว VACUUM INTO เพื่อสแนปช็อตที่สอดคล้อง
    const db = new DatabaseSync(dbPath, { readOnly: false });
    try {
      db.exec(`VACUUM INTO ${sqlQuote(destDb)}`);
    } finally {
      db.close();
    }

    let hasUploads = false;
    const uploads = getUploadsDir();
    if (existsSync(uploads)) {
      cpSync(uploads, path.join(dest, "uploads"), { recursive: true });
      hasUploads = true;
    }

    const meta = {
      stamp,
      created_at: new Date().toISOString(),
      mode,
      db_bytes: statSync(destDb).size,
      has_uploads: hasUploads,
      source: "cli",
    };
    writeFileSync(path.join(dest, "meta.json"), JSON.stringify(meta, null, 2), "utf8");
    const removed = prune();
    writeStatus({
      last_ok_at: meta.created_at,
      last_error: null,
      last_stamp: stamp,
      last_mode: mode,
    });
    return { meta, removed };
  } catch (err) {
    rmSync(dest, { recursive: true, force: true });
    writeStatus({
      last_error: err instanceof Error ? err.message : String(err),
      last_mode: mode,
    });
    throw err;
  }
}

function main() {
  const args = process.argv.slice(2);
  if (args.includes("--help") || args.includes("-h")) {
    console.log(`mali-backup — สำรอง SQLite ของมาลี

  node scripts/mali-backup.mjs --mode auto|manual
  node scripts/mali-backup.mjs --list
  node scripts/mali-backup.mjs --prune

Env: MALI_DATA_DIR MALI_BACKUP_DIR MALI_BACKUP_KEEP`);
    return;
  }
  if (args.includes("--list")) {
    console.log(JSON.stringify({ root: getBackupRoot(), keep: getKeep(), items: listBackups() }, null, 2));
    return;
  }
  if (args.includes("--prune")) {
    const removed = prune();
    console.log(removed.length ? `ลบ ${removed.length} ชุด: ${removed.join(", ")}` : "ไม่มีชุดเกินกำหนด");
    return;
  }
  let mode = "auto";
  const i = args.indexOf("--mode");
  if (i >= 0) mode = args[i + 1] || "auto";
  if (args.includes("--manual")) mode = "manual";

  const { meta, removed } = createBackup(mode);
  console.log(`สำรองแล้ว: ${path.join(getBackupRoot(), meta.stamp)}`);
  console.log(`ขนาด DB: ${meta.db_bytes} bytes · uploads: ${meta.has_uploads ? "มี" : "ไม่มี"}`);
  if (removed.length) console.log(`ตัดชุดเก่า: ${removed.join(", ")}`);
}

try {
  main();
} catch (err) {
  console.error(err instanceof Error ? err.message : err);
  process.exit(1);
}

