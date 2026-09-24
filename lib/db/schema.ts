export const SCHEMA_SQL = `
PRAGMA foreign_keys = ON;
PRAGMA journal_mode = WAL;

CREATE TABLE IF NOT EXISTS sites (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  company_name TEXT NOT NULL,
  customer_name TEXT,
  site_name TEXT,
  notes TEXT,
  UNIQUE(company_name, site_name)
);

CREATE TABLE IF NOT EXISTS teams (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  repair_group TEXT,
  scope TEXT,
  email TEXT,
  notes TEXT
);

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  name_th TEXT NOT NULL,
  name_en TEXT NOT NULL,
  email TEXT,
  role TEXT NOT NULL,
  team_id INTEGER REFERENCES teams(id),
  site_id INTEGER REFERENCES sites(id),
  employee_code TEXT,
  position TEXT,
  department TEXT,
  phone TEXT,
  started_at TEXT,
  active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS assets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  site_id INTEGER NOT NULL REFERENCES sites(id),
  type TEXT NOT NULL,
  brand TEXT,
  code TEXT UNIQUE NOT NULL,
  model TEXT,
  serial TEXT,
  manufacturer TEXT,
  received_at TEXT,
  warranty_until TEXT,
  asset_number TEXT,
  description TEXT,
  price REAL,
  contact_name TEXT,
  contact_phone TEXT,
  notes TEXT,
  hour_meter INTEGER NOT NULL DEFAULT 0,
  operational_status TEXT NOT NULL DEFAULT 'พร้อมใช้งาน',
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS parts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  code TEXT UNIQUE NOT NULL,
  category TEXT,
  oem_code TEXT,
  name TEXT NOT NULL,
  qty INTEGER NOT NULL DEFAULT 0,
  unit_cost REAL NOT NULL DEFAULT 0,
  unit TEXT NOT NULL DEFAULT 'PCS.',
  used_on TEXT,
  location TEXT,
  lead_time TEXT,
  min_qty INTEGER NOT NULL DEFAULT 0,
  reorder_qty INTEGER NOT NULL DEFAULT 0,
  max_qty INTEGER NOT NULL DEFAULT 0,
  notes TEXT
);

CREATE TABLE IF NOT EXISTS vendors (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  code TEXT UNIQUE NOT NULL,
  company_name TEXT NOT NULL,
  description TEXT,
  contact TEXT,
  phone TEXT,
  address TEXT,
  email TEXT,
  notes TEXT
);

CREATE TABLE IF NOT EXISTS job_requests (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  number TEXT UNIQUE NOT NULL,
  asset_id INTEGER NOT NULL REFERENCES assets(id),
  site_id INTEGER NOT NULL REFERENCES sites(id),
  job_type TEXT NOT NULL,
  status TEXT NOT NULL,
  internal_status TEXT NOT NULL,
  result TEXT NOT NULL DEFAULT 'InProcess',
  priority TEXT NOT NULL DEFAULT 'ปกติ',
  symptom TEXT,
  requester_id INTEGER NOT NULL REFERENCES users(id),
  requester_phone TEXT,
  hour_meter INTEGER,
  location_note TEXT,
  signature_name TEXT,
  signed_at TEXT,
  work_order_id INTEGER,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS work_orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  number TEXT UNIQUE NOT NULL,
  job_request_id INTEGER NOT NULL UNIQUE REFERENCES job_requests(id),
  asset_id INTEGER NOT NULL REFERENCES assets(id),
  job_type TEXT NOT NULL,
  status TEXT NOT NULL,
  internal_status TEXT NOT NULL,
  result TEXT NOT NULL DEFAULT 'InProcess',
  team_id INTEGER REFERENCES teams(id),
  technician_id INTEGER REFERENCES users(id),
  special_info TEXT,
  countermeasure TEXT,
  started_at TEXT,
  completed_at TEXT,
  signature_name TEXT,
  signed_at TEXT,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS work_order_parts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  work_order_id INTEGER NOT NULL REFERENCES work_orders(id),
  part_id INTEGER NOT NULL REFERENCES parts(id),
  qty INTEGER NOT NULL,
  cost_per_unit REAL NOT NULL,
  total_price REAL NOT NULL
);

CREATE TABLE IF NOT EXISTS checklist_templates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  lift_type TEXT,
  kind TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS checklist_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  template_id INTEGER NOT NULL REFERENCES checklist_templates(id),
  code TEXT NOT NULL,
  label TEXT NOT NULL,
  sort_order INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS pm_schedules (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  asset_id INTEGER NOT NULL REFERENCES assets(id),
  title TEXT NOT NULL,
  frequency_days INTEGER NOT NULL,
  last_done_at TEXT,
  next_due_at TEXT NOT NULL,
  checklist_template_id INTEGER REFERENCES checklist_templates(id),
  active INTEGER NOT NULL DEFAULT 1,
  notes TEXT
);

CREATE TABLE IF NOT EXISTS inspections (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  template_id INTEGER NOT NULL REFERENCES checklist_templates(id),
  job_request_id INTEGER REFERENCES job_requests(id),
  work_order_id INTEGER REFERENCES work_orders(id),
  pm_schedule_id INTEGER REFERENCES pm_schedules(id),
  inspector_id INTEGER REFERENCES users(id),
  completed_at TEXT,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS checklist_results (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  inspection_id INTEGER NOT NULL REFERENCES inspections(id),
  item_id INTEGER NOT NULL REFERENCES checklist_items(id),
  result TEXT NOT NULL DEFAULT '',
  notes TEXT,
  UNIQUE(inspection_id, item_id)
);

CREATE TABLE IF NOT EXISTS asset_status_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  asset_id INTEGER NOT NULL REFERENCES assets(id),
  status TEXT NOT NULL,
  branch TEXT,
  details TEXT,
  start_at TEXT,
  end_at TEXT,
  reported_by INTEGER REFERENCES users(id),
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS rental_jobs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  asset_id INTEGER NOT NULL REFERENCES assets(id),
  job_no TEXT,
  customer_name TEXT,
  job_site TEXT,
  start_date TEXT,
  end_date TEXT,
  created_by INTEGER REFERENCES users(id),
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS part_movements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  part_id INTEGER NOT NULL REFERENCES parts(id),
  kind TEXT NOT NULL,
  qty INTEGER NOT NULL,
  work_order_id INTEGER REFERENCES work_orders(id),
  note TEXT,
  created_by INTEGER REFERENCES users(id),
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS sequences (
  name TEXT PRIMARY KEY,
  value INTEGER NOT NULL
);
`;
