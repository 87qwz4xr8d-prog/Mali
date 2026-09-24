import type {
  Asset,
  AssetRow,
  AssetStatusLog,
  ChecklistItem,
  ChecklistResult,
  ChecklistTemplate,
  Inspection,
  JobRequest,
  JobRequestRow,
  Part,
  PmScheduleRow,
  PublicUser,
  RentalJob,
  Site,
  Team,
  User,
  Vendor,
  WorkOrderPart,
  WorkOrderRow,
} from "../types";
import { all, get, run, tx } from "./connection";
import { buddhistYear, nowIso, padSeq } from "../format";

const USER_PUBLIC = `id, username, name_th, name_en, email, role, team_id, site_id, employee_code, position, department, phone, started_at, active, created_at`;

export function nextNumber(kind: "JR" | "MC") {
  return tx(() => {
    const row = get<{ value: number }>(`SELECT value FROM sequences WHERE name = ?`, [kind]);
    const next = (row?.value ?? 0) + 1;
    if (row) run(`UPDATE sequences SET value = ? WHERE name = ?`, [next, kind]);
    else run(`INSERT INTO sequences (name, value) VALUES (?, ?)`, [kind, next]);
    return `${kind}${buddhistYear()}-${padSeq(next)}`;
  });
}

export function findUserByUsername(username: string) {
  return get<User>(`SELECT * FROM users WHERE username = ? AND active = 1`, [username]);
}

export function findUserById(id: number) {
  return get<PublicUser>(`SELECT ${USER_PUBLIC} FROM users WHERE id = ?`, [id]);
}

export function listUsers() {
  return all<PublicUser>(
    `SELECT ${USER_PUBLIC} FROM users ORDER BY role, name_th`,
  );
}

export function listTechnicians() {
  return all<PublicUser>(
    `SELECT ${USER_PUBLIC} FROM users WHERE role IN ('Technician', 'Engineer Review') AND active = 1 ORDER BY name_th`,
  );
}

export function listSites() {
  return all<Site>(`SELECT * FROM sites ORDER BY company_name, site_name`);
}

export function getSite(id: number) {
  return get<Site>(`SELECT * FROM sites WHERE id = ?`, [id]);
}

export function listTeams() {
  return all<Team>(`SELECT * FROM teams ORDER BY name`);
}

export function getTeam(id: number) {
  return get<Team>(`SELECT * FROM teams WHERE id = ?`, [id]);
}

const ASSET_JOIN = `
  SELECT a.*, s.company_name, s.customer_name, s.site_name
  FROM assets a
  JOIN sites s ON s.id = a.site_id
`;

export function listAssets(filters?: { q?: string; type?: string; status?: string; siteId?: number }) {
  const where: string[] = ["1=1"];
  const params: unknown[] = [];
  if (filters?.q) {
    where.push(
      `(a.code LIKE ? OR a.model LIKE ? OR a.serial LIKE ? OR a.brand LIKE ? OR s.site_name LIKE ?)`,
    );
    const like = `%${filters.q}%`;
    params.push(like, like, like, like, like);
  }
  if (filters?.type) {
    where.push(`a.type = ?`);
    params.push(filters.type);
  }
  if (filters?.status) {
    where.push(`a.operational_status = ?`);
    params.push(filters.status);
  }
  if (filters?.siteId) {
    where.push(`a.site_id = ?`);
    params.push(filters.siteId);
  }
  return all<AssetRow>(`${ASSET_JOIN} WHERE ${where.join(" AND ")} ORDER BY a.code`, params);
}

export function getAsset(id: number) {
  return get<AssetRow>(`${ASSET_JOIN} WHERE a.id = ?`, [id]);
}

export function getAssetByCode(code: string) {
  return get<AssetRow>(`${ASSET_JOIN} WHERE a.code = ?`, [code]);
}

export function listParts(filters?: { q?: string; belowMin?: boolean; category?: string }) {
  const where: string[] = ["1=1"];
  const params: unknown[] = [];
  if (filters?.q) {
    const like = `%${filters.q}%`;
    where.push(`(code LIKE ? OR name LIKE ? OR oem_code LIKE ?)`);
    params.push(like, like, like);
  }
  if (filters?.category) {
    where.push(`category = ?`);
    params.push(filters.category);
  }
  if (filters?.belowMin) {
    where.push(`qty <= min_qty`);
  }
  return all<Part>(`SELECT * FROM parts WHERE ${where.join(" AND ")} ORDER BY code`, params);
}

export function getPart(id: number) {
  return get<Part>(`SELECT * FROM parts WHERE id = ?`, [id]);
}

export function listVendors() {
  return all<Vendor>(`SELECT * FROM vendors ORDER BY code`);
}

export function getVendor(id: number) {
  return get<Vendor>(`SELECT * FROM vendors WHERE id = ?`, [id]);
}

const JR_JOIN = `
  SELECT jr.*, a.code AS asset_code, a.model AS asset_model, a.type AS asset_type,
         s.site_name, s.company_name, u.name_th AS requester_name,
         wo.number AS work_order_number
  FROM job_requests jr
  JOIN assets a ON a.id = jr.asset_id
  JOIN sites s ON s.id = jr.site_id
  JOIN users u ON u.id = jr.requester_id
  LEFT JOIN work_orders wo ON wo.id = jr.work_order_id
`;

export function listJobRequests(filters?: {
  q?: string;
  status?: string;
  jobType?: string;
  openOnly?: boolean;
}) {
  const where: string[] = ["1=1"];
  const params: unknown[] = [];
  if (filters?.q) {
    const like = `%${filters.q}%`;
    where.push(`(jr.number LIKE ? OR a.code LIKE ? OR jr.symptom LIKE ?)`);
    params.push(like, like, like);
  }
  if (filters?.status) {
    where.push(`jr.status = ?`);
    params.push(filters.status);
  }
  if (filters?.jobType) {
    where.push(`jr.job_type = ?`);
    params.push(filters.jobType);
  }
  if (filters?.openOnly) {
    where.push(`jr.status NOT IN ('ปิดจบงาน', 'ซ่อมเสร็จแล้ว')`);
  }
  return all<JobRequestRow>(
    `${JR_JOIN} WHERE ${where.join(" AND ")} ORDER BY jr.created_at DESC`,
    params,
  );
}

export function getJobRequest(id: number) {
  return get<JobRequestRow>(`${JR_JOIN} WHERE jr.id = ?`, [id]);
}

const WO_JOIN = `
  SELECT wo.*, a.code AS asset_code, a.model AS asset_model, a.type AS asset_type,
         s.site_name, s.company_name, jr.number AS job_request_number, jr.symptom,
         t.name AS team_name, u.name_th AS technician_name
  FROM work_orders wo
  JOIN assets a ON a.id = wo.asset_id
  JOIN job_requests jr ON jr.id = wo.job_request_id
  JOIN sites s ON s.id = jr.site_id
  LEFT JOIN teams t ON t.id = wo.team_id
  LEFT JOIN users u ON u.id = wo.technician_id
`;

export function listWorkOrders(filters?: {
  q?: string;
  status?: string;
  technicianId?: number;
  openOnly?: boolean;
}) {
  const where: string[] = ["1=1"];
  const params: unknown[] = [];
  if (filters?.q) {
    const like = `%${filters.q}%`;
    where.push(`(wo.number LIKE ? OR a.code LIKE ? OR jr.number LIKE ?)`);
    params.push(like, like, like);
  }
  if (filters?.status) {
    where.push(`wo.status = ?`);
    params.push(filters.status);
  }
  if (filters?.technicianId) {
    where.push(`wo.technician_id = ?`);
    params.push(filters.technicianId);
  }
  if (filters?.openOnly) {
    where.push(`wo.status NOT IN ('ปิดจบงาน')`);
  }
  return all<WorkOrderRow>(
    `${WO_JOIN} WHERE ${where.join(" AND ")} ORDER BY wo.created_at DESC`,
    params,
  );
}

export function getWorkOrder(id: number) {
  return get<WorkOrderRow>(`${WO_JOIN} WHERE wo.id = ?`, [id]);
}

export function listWorkOrderParts(workOrderId: number) {
  return all<WorkOrderPart>(
    `SELECT wop.*, p.code AS part_code, p.name AS part_name, p.unit
     FROM work_order_parts wop JOIN parts p ON p.id = wop.part_id
     WHERE wop.work_order_id = ? ORDER BY wop.id`,
    [workOrderId],
  );
}

export function listPmSchedules(filters?: { due?: "overdue" | "upcoming" | "all"; q?: string }) {
  const where: string[] = ["ps.active = 1"];
  const params: unknown[] = [];
  const today = new Date().toISOString().slice(0, 10);
  if (filters?.due === "overdue") {
    where.push(`ps.next_due_at < ?`);
    params.push(today);
  } else if (filters?.due === "upcoming") {
    where.push(`ps.next_due_at >= ?`);
    params.push(today);
  }
  if (filters?.q) {
    const like = `%${filters.q}%`;
    where.push(`(a.code LIKE ? OR ps.title LIKE ?)`);
    params.push(like, like);
  }
  return all<PmScheduleRow>(
    `SELECT ps.*, a.code AS asset_code, a.model AS asset_model, a.type AS asset_type, s.site_name
     FROM pm_schedules ps
     JOIN assets a ON a.id = ps.asset_id
     JOIN sites s ON s.id = a.site_id
     WHERE ${where.join(" AND ")}
     ORDER BY ps.next_due_at ASC`,
    params,
  );
}

export function getPmSchedule(id: number) {
  return get<PmScheduleRow>(
    `SELECT ps.*, a.code AS asset_code, a.model AS asset_model, a.type AS asset_type, s.site_name
     FROM pm_schedules ps
     JOIN assets a ON a.id = ps.asset_id
     JOIN sites s ON s.id = a.site_id
     WHERE ps.id = ?`,
    [id],
  );
}

export function getTemplate(kind: "request" | "mc" | "pm", liftType?: string | null) {
  if (liftType) {
    const typed = get<ChecklistTemplate>(
      `SELECT * FROM checklist_templates WHERE kind = ? AND lift_type = ?`,
      [kind, liftType],
    );
    if (typed) return typed;
  }
  return get<ChecklistTemplate>(
    `SELECT * FROM checklist_templates WHERE kind = ? AND (lift_type IS NULL OR lift_type = '')`,
    [kind],
  );
}

export function listTemplateItems(templateId: number) {
  return all<ChecklistItem>(
    `SELECT * FROM checklist_items WHERE template_id = ? ORDER BY sort_order`,
    [templateId],
  );
}

export function createInspection(input: {
  templateId: number;
  jobRequestId?: number;
  workOrderId?: number;
  pmScheduleId?: number;
  inspectorId?: number;
}) {
  const created = nowIso();
  const result = run(
    `INSERT INTO inspections (template_id, job_request_id, work_order_id, pm_schedule_id, inspector_id, created_at)
     VALUES (?, ?, ?, ?, ?, ?)`,
    [
      input.templateId,
      input.jobRequestId ?? null,
      input.workOrderId ?? null,
      input.pmScheduleId ?? null,
      input.inspectorId ?? null,
      created,
    ],
  );
  const items = listTemplateItems(input.templateId);
  for (const item of items) {
    run(
      `INSERT INTO checklist_results (inspection_id, item_id, result, notes) VALUES (?, ?, '', '')`,
      [result.lastInsertRowid, item.id],
    );
  }
  return result.lastInsertRowid;
}

export function getInspectionForJobRequest(jobRequestId: number) {
  return get<Inspection>(
    `SELECT * FROM inspections WHERE job_request_id = ? ORDER BY id DESC LIMIT 1`,
    [jobRequestId],
  );
}

export function getInspectionForWorkOrder(workOrderId: number) {
  return get<Inspection>(
    `SELECT * FROM inspections WHERE work_order_id = ? ORDER BY id DESC LIMIT 1`,
    [workOrderId],
  );
}

export function listInspectionResults(inspectionId: number) {
  return all<ChecklistResult>(
    `SELECT cr.*, ci.code, ci.label, ci.sort_order
     FROM checklist_results cr
     JOIN checklist_items ci ON ci.id = cr.item_id
     WHERE cr.inspection_id = ?
     ORDER BY ci.sort_order`,
    [inspectionId],
  );
}

export function listAssetStatusLogs(assetId: number) {
  return all<AssetStatusLog>(
    `SELECT l.*, u.name_th AS reporter_name
     FROM asset_status_logs l
     LEFT JOIN users u ON u.id = l.reported_by
     WHERE l.asset_id = ?
     ORDER BY l.created_at DESC`,
    [assetId],
  );
}

export function listRentalJobs(assetId?: number) {
  if (assetId) {
    return all<RentalJob>(
      `SELECT * FROM rental_jobs WHERE asset_id = ? ORDER BY start_date DESC`,
      [assetId],
    );
  }
  return all<RentalJob>(`SELECT * FROM rental_jobs ORDER BY start_date DESC`);
}

export function dashboardStats() {
  const openJr = get<{ c: number }>(
    `SELECT COUNT(*) AS c FROM job_requests WHERE status NOT IN ('ปิดจบงาน')`,
  )?.c ?? 0;
  const openWo = get<{ c: number }>(
    `SELECT COUNT(*) AS c FROM work_orders WHERE status NOT IN ('ปิดจบงาน')`,
  )?.c ?? 0;
  const bd = get<{ c: number }>(
    `SELECT COUNT(*) AS c FROM job_requests WHERE job_type = 'BD' AND status NOT IN ('ปิดจบงาน')`,
  )?.c ?? 0;
  const today = new Date().toISOString().slice(0, 10);
  const pmDue = get<{ c: number }>(
    `SELECT COUNT(*) AS c FROM pm_schedules WHERE active = 1 AND next_due_at <= ?`,
    [today],
  )?.c ?? 0;
  const partsLow = get<{ c: number }>(
    `SELECT COUNT(*) AS c FROM parts WHERE qty <= min_qty`,
  )?.c ?? 0;
  const ready = get<{ c: number }>(
    `SELECT COUNT(*) AS c FROM assets WHERE operational_status = 'พร้อมใช้งาน'`,
  )?.c ?? 0;
  const breakdown = get<{ c: number }>(
    `SELECT COUNT(*) AS c FROM assets WHERE operational_status = 'รถเบรคดาวน์ไม่พร้อมใช้'`,
  )?.c ?? 0;
  const onSite = get<{ c: number }>(
    `SELECT COUNT(*) AS c FROM assets WHERE operational_status = 'รถอยู่หน้างาน'`,
  )?.c ?? 0;
  const totalAssets = get<{ c: number }>(`SELECT COUNT(*) AS c FROM assets`)?.c ?? 0;
  return { openJr, openWo, bd, pmDue, partsLow, ready, breakdown, onSite, totalAssets };
}

export function assetHistory(assetId: number) {
  const requests = listJobRequests();
  return {
    requests: requests.filter((r) => r.asset_id === assetId),
    workOrders: listWorkOrders().filter((w) => w.asset_id === assetId),
    pm: listPmSchedules({ due: "all" }).filter((p) => p.asset_id === assetId),
    status: listAssetStatusLogs(assetId),
    rentals: listRentalJobs(assetId),
  };
}

export function reportOpenBreakdown() {
  return listJobRequests({ jobType: "BD", openOnly: true });
}

export function reportPmCompliance() {
  const rows = listPmSchedules({ due: "all" });
  const today = new Date().toISOString().slice(0, 10);
  const overdue = rows.filter((r) => r.next_due_at < today);
  const ok = rows.filter((r) => r.next_due_at >= today);
  return { total: rows.length, overdue: overdue.length, ok: ok.length, rows };
}

export function reportPartsUsed() {
  return all<{
    part_code: string;
    part_name: string;
    qty: number;
    total_price: number;
    wo_number: string;
    asset_code: string;
  }>(
    `SELECT p.code AS part_code, p.name AS part_name, wop.qty, wop.total_price,
            wo.number AS wo_number, a.code AS asset_code
     FROM work_order_parts wop
     JOIN parts p ON p.id = wop.part_id
     JOIN work_orders wo ON wo.id = wop.work_order_id
     JOIN assets a ON a.id = wo.asset_id
     ORDER BY wo.created_at DESC`,
  );
}

export function insertAsset(data: Omit<Asset, "id" | "created_at">) {
  const result = run(
    `INSERT INTO assets (site_id, type, brand, code, model, serial, manufacturer, received_at, warranty_until, asset_number, description, price, contact_name, contact_phone, notes, hour_meter, operational_status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      data.site_id,
      data.type,
      data.brand,
      data.code,
      data.model,
      data.serial,
      data.manufacturer,
      data.received_at,
      data.warranty_until,
      data.asset_number,
      data.description,
      data.price,
      data.contact_name,
      data.contact_phone,
      data.notes,
      data.hour_meter,
      data.operational_status,
      nowIso(),
    ],
  );
  return result.lastInsertRowid;
}

export function updateAsset(id: number, data: Partial<Asset>) {
  const fields: string[] = [];
  const params: unknown[] = [];
  const map: Array<[keyof Asset, unknown]> = [
    ["site_id", data.site_id],
    ["type", data.type],
    ["brand", data.brand],
    ["code", data.code],
    ["model", data.model],
    ["serial", data.serial],
    ["manufacturer", data.manufacturer],
    ["received_at", data.received_at],
    ["warranty_until", data.warranty_until],
    ["asset_number", data.asset_number],
    ["description", data.description],
    ["price", data.price],
    ["contact_name", data.contact_name],
    ["contact_phone", data.contact_phone],
    ["notes", data.notes],
    ["hour_meter", data.hour_meter],
    ["operational_status", data.operational_status],
  ];
  for (const [k, v] of map) {
    if (v !== undefined) {
      fields.push(`${k} = ?`);
      params.push(v);
    }
  }
  if (!fields.length) return;
  params.push(id);
  run(`UPDATE assets SET ${fields.join(", ")} WHERE id = ?`, params);
}

export { run, get, all, tx };
