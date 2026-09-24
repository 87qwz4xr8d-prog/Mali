import { DatabaseSync } from "node:sqlite";
import { mkdirSync } from "node:fs";
import path from "node:path";
import { requireProductionSecret } from "../runtime-config";
import { SCHEMA_SQL } from "./schema";
import { seedIfEmpty } from "./seed";

export function getDataDir() {
  return process.env.MALI_DATA_DIR || path.join(process.cwd(), "data");
}

export function getDbPath() {
  return path.join(getDataDir(), "mali.db");
}

type GlobalDb = { maliDb?: DatabaseSync };

const g = globalThis as typeof globalThis & GlobalDb;

export function getDb() {
  if (!g.maliDb) {
    requireProductionSecret();
    if (process.env.NEXT_PHASE === "phase-production-build") {
      const db = new DatabaseSync(":memory:");
      db.exec("PRAGMA foreign_keys = ON");
      db.exec(SCHEMA_SQL);
      g.maliDb = db;
      return g.maliDb;
    }
    const dataDir = getDataDir();
    mkdirSync(path.join(dataDir, "uploads"), { recursive: true });
    const db = new DatabaseSync(getDbPath());
    db.exec("PRAGMA foreign_keys = ON");
    db.exec("PRAGMA journal_mode = WAL");
    db.exec(SCHEMA_SQL);
    seedIfEmpty(db);
    g.maliDb = db;
  }
  return g.maliDb;
}

function asPlain<T>(row: T): T {
  return JSON.parse(JSON.stringify(row)) as T;
}

export function all<T>(sql: string, params: unknown[] = []): T[] {
  return (getDb().prepare(sql).all(...params) as T[]).map((row) => asPlain(row));
}

export function get<T>(sql: string, params: unknown[] = []): T | undefined {
  const row = getDb().prepare(sql).get(...params);
  return row == null ? undefined : asPlain(row as T);
}

export function run(sql: string, params: unknown[] = []) {
  const result = getDb().prepare(sql).run(...params);
  return {
    lastInsertRowid: Number(result.lastInsertRowid),
    changes: Number(result.changes),
  };
}

export function tx<T>(fn: () => T): T {
  const db = getDb();
  db.exec("BEGIN");
  try {
    const out = fn();
    db.exec("COMMIT");
    return out;
  } catch (err) {
    db.exec("ROLLBACK");
    throw err;
  }
}
