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
import { getDb } from "./db/connection";
import { getBackupKeep, getBackupRoot, getDbPath, getUploadsDir } from "./db/paths";

export type BackupMode = "manual" | "auto";

export type BackupMeta = {
  stamp: string;
  created_at: string;
  mode: BackupMode;
  db_bytes: number;
  has_uploads: boolean;
  source: string;
};

export type BackupStatus = {
  last_ok_at: string | null;
  last_error: string | null;
  last_stamp: string | null;
  last_mode: BackupMode | null;
  backup_root: string;
  keep: number;
};

function sqlQuote(filePath: string) {
  return `'${filePath.replaceAll("'", "''")}'`;
}

function stampNow() {
  const d = new Date();
  const p = (n: number) => String(n).padStart(2, "0");
  return (
    `${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}` +
    `-${p(d.getHours())}${p(d.getMinutes())}${p(d.getSeconds())}`
  );
}

function statusPath() {
  return path.join(getBackupRoot(), "last-run.json");
}

function metaPath(dir: string) {
  return path.join(dir, "meta.json");
}

export function readBackupStatus(): BackupStatus {
  const root = getBackupRoot();
  const base: BackupStatus = {
    last_ok_at: null,
    last_error: null,
    last_stamp: null,
    last_mode: null,
    backup_root: root,
    keep: getBackupKeep(),
  };
  try {
    if (!existsSync(statusPath())) return base;
    const raw = JSON.parse(readFileSync(statusPath(), "utf8")) as Partial<BackupStatus>;
    return { ...base, ...raw, backup_root: root, keep: getBackupKeep() };
  } catch {
    return base;
  }
}

function writeStatus(partial: Partial<BackupStatus>) {
  const root = getBackupRoot();
  mkdirSync(root, { recursive: true });
  const next = { ...readBackupStatus(), ...partial, backup_root: root, keep: getBackupKeep() };
  writeFileSync(statusPath(), JSON.stringify(next, null, 2), "utf8");
}

function dirBytes(filePath: string): number {
  try {
    return statSync(filePath).size;
  } catch {
    return 0;
  }
}

export function listBackups(): BackupMeta[] {
  const root = getBackupRoot();
  if (!existsSync(root)) return [];
  const rows: BackupMeta[] = [];
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
    const dbFile = path.join(dir, "mali.db");
    if (!existsSync(dbFile)) continue;
    const metaFile = metaPath(dir);
    if (existsSync(metaFile)) {
      try {
        rows.push(JSON.parse(readFileSync(metaFile, "utf8")) as BackupMeta);
        continue;
      } catch {
        /* fall through */
      }
    }
    rows.push({
      stamp: name,
      created_at: st.mtime.toISOString(),
      mode: "auto",
      db_bytes: dirBytes(dbFile),
      has_uploads: existsSync(path.join(dir, "uploads")),
      source: "disk",
    });
  }
  return rows.sort((a, b) => (a.stamp < b.stamp ? 1 : a.stamp > b.stamp ? -1 : 0));
}

export function pruneBackups(keep = getBackupKeep()): string[] {
  const rows = listBackups();
  const removed: string[] = [];
  for (const row of rows.slice(keep)) {
    const dir = path.join(getBackupRoot(), row.stamp);
    rmSync(dir, { recursive: true, force: true });
    removed.push(row.stamp);
  }
  return removed;
}

export function getBackupDir(stamp: string) {
  if (!/^\d{8}-\d{6}(-\d+)?$/.test(stamp)) {
    throw new Error("รหัสชุดสำรองไม่ถูกต้อง");
  }
  return path.join(getBackupRoot(), stamp);
}

/**
 * สำรอง SQLite แบบออนไลน์ด้วย VACUUM INTO + คัดลอก uploads
 * ใช้ได้ทั้งจากหน้า Admin และสคริปต์ CLI
 */
export function createBackup(mode: BackupMode = "manual"): BackupMeta {
  const dbPath = getDbPath();
  if (!existsSync(dbPath)) {
    throw new Error(`ไม่พบฐานข้อมูล: ${dbPath}`);
  }

  const root = getBackupRoot();
  mkdirSync(root, { recursive: true });

  let stamp = stampNow();
  let dest = path.join(root, stamp);
  // กันชนชื่อถ้าสร้างถี่มาก
  if (existsSync(dest)) {
    stamp = `${stamp}-${process.pid}`;
    dest = path.join(root, stamp);
  }
  mkdirSync(dest, { recursive: true });

  const destDb = path.join(dest, "mali.db");
  try {
    // เปิด connection ปกติก่อน เพื่อให้ schema/WAL พร้อม แล้ว VACUUM INTO จาก connection นั้น
    const db = getDb();
    db.exec(`VACUUM INTO ${sqlQuote(destDb)}`);

    const uploads = getUploadsDir();
    let hasUploads = false;
    if (existsSync(uploads)) {
      cpSync(uploads, path.join(dest, "uploads"), { recursive: true });
      hasUploads = true;
    }

    const meta: BackupMeta = {
      stamp,
      created_at: new Date().toISOString(),
      mode,
      db_bytes: dirBytes(destDb),
      has_uploads: hasUploads,
      source: "mali",
    };
    writeFileSync(metaPath(dest), JSON.stringify(meta, null, 2), "utf8");
    pruneBackups();
    writeStatus({
      last_ok_at: meta.created_at,
      last_error: null,
      last_stamp: stamp,
      last_mode: mode,
    });
    return meta;
  } catch (err) {
    rmSync(dest, { recursive: true, force: true });
    const message = err instanceof Error ? err.message : String(err);
    writeStatus({ last_error: message, last_mode: mode });
    throw err;
  }
}

export function deleteBackup(stamp: string) {
  const dir = getBackupDir(stamp);
  if (!existsSync(dir)) throw new Error("ไม่พบชุดสำรองนี้");
  rmSync(dir, { recursive: true, force: true });
}

export function formatBytes(n: number) {
  if (n < 1024) return `${n} B`;
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
  return `${(n / (1024 * 1024)).toFixed(2)} MB`;
}
