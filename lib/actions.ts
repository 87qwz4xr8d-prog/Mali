"use server";

import { compareSync, hashSync } from "bcryptjs";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { z } from "zod";
import { clearSessionCookie, requireUser, setSessionCookie } from "./auth";
import { JOB_STATUSES } from "./constants";
import {
  all,
  createInspection,
  findUserByUsername,
  get,
  getAsset,
  getJobRequest,
  getPart,
  getPmSchedule,
  getTemplate,
  getWorkOrder,
  listInspectionResults,
  listTemplateItems,
  nextNumber,
  run,
  tx,
} from "./db/queries";
import { addDays, nowIso } from "./format";
import {
  canApproveClose,
  canAssignWorkOrder,
  canConvertToWorkOrder,
  canCreateJobRequest,
  canEditJobRequest,
  canEnterRepair,
  canIssueParts,
  canManageAssets,
  canManageMaster,
  canManageParts,
  canManageUsers,
} from "./permissions";
import type { ActionState, InternalStatus, JobType, OperationalStatus } from "./types";

function fail(error: string): ActionState {
  return { error };
}

function boom(error: string): never {
  throw new Error(error);
}

function ok(success: string, id?: number): ActionState {
  return { success, id };
}

export async function loginAction(_prev: ActionState, formData: FormData): Promise<ActionState> {
  const username = String(formData.get("username") || "").trim();
  const password = String(formData.get("password") || "");
  if (!username || !password) return fail("กรุณากรอกชื่อผู้ใช้และรหัสผ่าน");
  const user = findUserByUsername(username);
  if (!user || !compareSync(password, user.password_hash)) {
    return fail("ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง");
  }
  await setSessionCookie(user.id);
  redirect("/");
}

export async function logoutAction() {
  await clearSessionCookie();
  redirect("/login");
}

export async function createJobRequestAction(
  _prev: ActionState,
  formData: FormData,
): Promise<ActionState> {
  const user = await requireUser();
  if (!canCreateJobRequest(user.role)) return fail("บัญชีนี้สร้างใบแจ้งซ่อมไม่ได้");

  const assetId = Number(formData.get("asset_id"));
  const jobType = String(formData.get("job_type")) as JobType;
  const priority = String(formData.get("priority") || "ปกติ");
  const symptom = String(formData.get("symptom") || "").trim();
  const phone = String(formData.get("requester_phone") || "").trim();
  const hourMeter = formData.get("hour_meter") ? Number(formData.get("hour_meter")) : null;
  const location = String(formData.get("location_note") || "").trim();
  const signature = String(formData.get("signature_name") || "").trim();

  const asset = getAsset(assetId);
  if (!asset) return fail("ไม่พบเครื่องจักรที่เลือก");
  if (!symptom) return fail("กรุณาระบุอาการหรือรายละเอียดงาน");
  if (!["BD", "PM", "CM", "SV", "Drive"].includes(jobType)) return fail("ประเภทงานไม่ถูกต้อง");

  const template = getTemplate("request", asset.type);
  if (!template) return fail("ยังไม่มีเทมเพลตตรวจเช็คก่อนแจ้งซ่อม");

  const number = nextNumber("JR");
  const ts = nowIso();
  const internal: InternalStatus = signature ? "Wait" : "New";
  const id = tx(() => {
    const result = run(
      `INSERT INTO job_requests (number, asset_id, site_id, job_type, status, internal_status, result, priority, symptom, requester_id, requester_phone, hour_meter, location_note, signature_name, signed_at, created_at, updated_at)
       VALUES (?, ?, ?, ?, 'รอมอบหมายทีมซ่อม', ?, 'InProcess', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        number,
        asset.id,
        asset.site_id,
        jobType,
        internal,
        priority,
        symptom,
        user.id,
        phone || user.phone,
        hourMeter,
        location,
        signature || null,
        signature ? ts : null,
        ts,
        ts,
      ],
    );
    const jrId = result.lastInsertRowid;
    createInspection({ templateId: template.id, jobRequestId: jrId, inspectorId: user.id });
    if (jobType === "BD") {
      run(`UPDATE assets SET operational_status = 'รถเบรคดาวน์ไม่พร้อมใช้' WHERE id = ?`, [asset.id]);
    }
    return jrId;
  });

  const items = listTemplateItems(template.id);
  const inspection = get<{ id: number }>(
    `SELECT id FROM inspections WHERE job_request_id = ? ORDER BY id DESC LIMIT 1`,
    [id],
  );
  if (inspection) {
    for (const item of items) {
      const value = String(formData.get(`check_${item.id}`) || "");
      const notes = String(formData.get(`check_note_${item.id}`) || "");
      run(`UPDATE checklist_results SET result = ?, notes = ? WHERE inspection_id = ? AND item_id = ?`, [
        value,
        notes,
        inspection.id,
        item.id,
      ]);
    }
    const filled = items.some((item) => String(formData.get(`check_${item.id}`) || ""));
    if (filled) {
      run(`UPDATE inspections SET completed_at = ?, inspector_id = ? WHERE id = ?`, [
        ts,
        user.id,
        inspection.id,
      ]);
    }
  }

  revalidatePath("/job-requests");
  revalidatePath("/");
  redirect(`/job-requests/${id}`);
}

export async function updateJobRequestAction(
  _prev: ActionState,
  formData: FormData,
): Promise<ActionState> {
  const user = await requireUser();
  const id = Number(formData.get("id"));
  const jr = getJobRequest(id);
  if (!jr) return fail("ไม่พบใบแจ้งซ่อม");
  const closed = jr.status === "ปิดจบงาน";
  if (!canEditJobRequest(user.role, closed)) return fail("แก้ไขใบนี้ไม่ได้");

  const symptom = String(formData.get("symptom") || "").trim();
  const priority = String(formData.get("priority") || jr.priority);
  const phone = String(formData.get("requester_phone") || "");
  const location = String(formData.get("location_note") || "");
  const hourMeter = formData.get("hour_meter") ? Number(formData.get("hour_meter")) : jr.hour_meter;
  const signature = String(formData.get("signature_name") || "").trim();
  const ts = nowIso();
  const internal = signature ? "Wait" : jr.internal_status;

  run(
    `UPDATE job_requests SET symptom = ?, priority = ?, requester_phone = ?, location_note = ?, hour_meter = ?, signature_name = ?, signed_at = COALESCE(signed_at, ?), internal_status = ?, updated_at = ? WHERE id = ?`,
    [
      symptom,
      priority,
      phone,
      location,
      hourMeter,
      signature || jr.signature_name,
      signature ? ts : null,
      internal,
      ts,
      id,
    ],
  );
  revalidatePath(`/job-requests/${id}`);
  return ok("บันทึกใบแจ้งซ่อมแล้ว");
}

export async function convertToWorkOrderAction(formData: FormData): Promise<void> {
  const user = await requireUser();
  if (!canConvertToWorkOrder(user.role)) boom("เปิดใบงานซ่อมไม่ได้");
  const jrId = Number(formData.get("job_request_id"));
  const jr = getJobRequest(jrId);
  if (!jr) boom("ไม่พบใบแจ้งซ่อม");
  if (jr.work_order_id) redirect(`/work-orders/${jr.work_order_id}`);

  const teamId = formData.get("team_id") ? Number(formData.get("team_id")) : null;
  const technicianId = formData.get("technician_id") ? Number(formData.get("technician_id")) : null;
  const special = String(formData.get("special_info") || "").trim();
  const number = nextNumber("MC");
  const ts = nowIso();
  const assigned = Boolean(teamId || technicianId || special);
  const status = assigned ? "อยู่ระหว่างดำเนินการซ่อม" : "รอมอบหมายทีมซ่อม";
  const internal: InternalStatus = assigned ? "Assign" : "Wait";

  const woId = tx(() => {
    const result = run(
      `INSERT INTO work_orders (number, job_request_id, asset_id, job_type, status, internal_status, result, team_id, technician_id, special_info, started_at, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, 'InProcess', ?, ?, ?, ?, ?, ?)`,
      [
        number,
        jr.id,
        jr.asset_id,
        jr.job_type,
        status,
        internal,
        teamId,
        technicianId,
        special || null,
        assigned ? ts : null,
        ts,
        ts,
      ],
    );
    const id = result.lastInsertRowid;
    run(`UPDATE job_requests SET work_order_id = ?, status = ?, internal_status = ?, updated_at = ? WHERE id = ?`, [
      id,
      status,
      internal,
      ts,
      jr.id,
    ]);
    const asset = getAsset(jr.asset_id);
    const template = getTemplate("mc", asset?.type);
    if (template) {
      createInspection({ templateId: template.id, workOrderId: id, inspectorId: user.id });
    }
    return id;
  });

  revalidatePath("/work-orders");
  revalidatePath("/job-requests");
  redirect(`/work-orders/${woId}`);
}

export async function updateWorkOrderAction(
  _prev: ActionState,
  formData: FormData,
): Promise<ActionState> {
  const user = await requireUser();
  const id = Number(formData.get("id"));
  const wo = getWorkOrder(id);
  if (!wo) return fail("ไม่พบใบงานซ่อม");
  const closed = wo.status === "ปิดจบงาน";
  if (closed && user.role !== "Admin" && user.role !== "Engineer Review") {
    return fail("ปิดจบแล้ว แก้ไขได้เฉพาะวิศวกรหรือผู้ดูแลระบบ");
  }

  const teamId = formData.get("team_id") ? Number(formData.get("team_id")) : null;
  const technicianId = formData.get("technician_id") ? Number(formData.get("technician_id")) : null;
  const special = String(formData.get("special_info") || "");
  const counter = String(formData.get("countermeasure") || "");
  const status = String(formData.get("status") || wo.status);
  if (!JOB_STATUSES.includes(status as (typeof JOB_STATUSES)[number])) {
    return fail("สถานะไม่ถูกต้อง");
  }

  if ((teamId !== wo.team_id || technicianId !== wo.technician_id || special !== (wo.special_info || "")) &&
      !canAssignWorkOrder(user.role) && !closed) {
    if (!canEnterRepair(user.role)) return fail("มอบหมายงานไม่ได้");
  }
  if (counter !== (wo.countermeasure || "") && !canEnterRepair(user.role) && !canAssignWorkOrder(user.role)) {
    return fail("บันทึกวิธีแก้ไขไม่ได้");
  }

  let internal: InternalStatus = wo.internal_status;
  if (special && special !== (wo.special_info || "")) internal = "Assign";
  if (counter) internal = "Review";
  if (status === "รออะไหล่") internal = "Assign";
  if (status === "รออนุมัติ") internal = "Review";
  if (status === "หัวหน้างานอนุมัติซ่อม") internal = "Approve";
  if (status === "ซ่อมเสร็จแล้ว") internal = "Approve";
  if (status === "ปิดจบงาน") internal = "Closed";

  const ts = nowIso();
  run(
    `UPDATE work_orders SET team_id = ?, technician_id = ?, special_info = ?, countermeasure = ?, status = ?, internal_status = ?, started_at = COALESCE(started_at, ?), updated_at = ? WHERE id = ?`,
    [teamId, technicianId, special, counter, status, internal, ts, ts, id],
  );
  run(
    `UPDATE job_requests SET status = ?, internal_status = ?, updated_at = ? WHERE id = ?`,
    [status, internal, ts, wo.job_request_id],
  );
  revalidatePath(`/work-orders/${id}`);
  return ok("บันทึกใบงานซ่อมแล้ว");
}

export async function saveChecklistAction(formData: FormData): Promise<void> {
  const user = await requireUser();
  const inspectionId = Number(formData.get("inspection_id"));
  const items = all<{ item_id: number }>(
    `SELECT item_id FROM checklist_results WHERE inspection_id = ?`,
    [inspectionId],
  );
  for (const row of items) {
    const value = String(formData.get(`check_${row.item_id}`) || "");
    const notes = String(formData.get(`check_note_${row.item_id}`) || "");
    run(`UPDATE checklist_results SET result = ?, notes = ? WHERE inspection_id = ? AND item_id = ?`, [
      value,
      notes,
      inspectionId,
      row.item_id,
    ]);
  }
  run(`UPDATE inspections SET completed_at = ?, inspector_id = ? WHERE id = ?`, [
    nowIso(),
    user.id,
    inspectionId,
  ]);
  revalidatePath("/");
  revalidatePath("/job-requests");
  revalidatePath("/work-orders");
}

export async function addWorkOrderPartAction(formData: FormData): Promise<void> {
  const user = await requireUser();
  if (!canIssueParts(user.role)) boom("เบิกอะไหล่ไม่ได้");
  const woId = Number(formData.get("work_order_id"));
  const partId = Number(formData.get("part_id"));
  const qty = Number(formData.get("qty"));
  const wo = getWorkOrder(woId);
  const part = getPart(partId);
  if (!wo || !part) boom("ไม่พบใบงานหรืออะไหล่");
  if (!qty || qty <= 0) boom("จำนวนต้องมากกว่า 0");
  if (part.qty < qty) boom(`สต็อก ${part.code} ไม่พอ (เหลือ ${part.qty} ${part.unit})`);

  tx(() => {
    const total = qty * part.unit_cost;
    run(
      `INSERT INTO work_order_parts (work_order_id, part_id, qty, cost_per_unit, total_price) VALUES (?, ?, ?, ?, ?)`,
      [woId, partId, qty, part.unit_cost, total],
    );
    run(`UPDATE parts SET qty = qty - ? WHERE id = ?`, [qty, partId]);
    run(
      `INSERT INTO part_movements (part_id, kind, qty, work_order_id, note, created_by, created_at)
       VALUES (?, 'issue', ?, ?, ?, ?, ?)`,
      [partId, qty, woId, `เบิกเข้า ${wo.number}`, user.id, nowIso()],
    );
  });
  revalidatePath(`/work-orders/${woId}`);
  revalidatePath("/parts");
}

export async function receivePartAction(formData: FormData): Promise<void> {
  const user = await requireUser();
  if (!canManageParts(user.role)) boom("รับอะไหล่เข้าคลังไม่ได้");
  const partId = Number(formData.get("part_id"));
  const qty = Number(formData.get("qty"));
  const note = String(formData.get("note") || "รับเข้าคลัง");
  if (!qty || qty <= 0) boom("จำนวนต้องมากกว่า 0");
  tx(() => {
    run(`UPDATE parts SET qty = qty + ? WHERE id = ?`, [qty, partId]);
    run(
      `INSERT INTO part_movements (part_id, kind, qty, note, created_by, created_at) VALUES (?, 'receive', ?, ?, ?, ?)`,
      [partId, qty, note, user.id, nowIso()],
    );
  });
  revalidatePath(`/parts/${partId}`);
  revalidatePath("/parts");
}

export async function closeWorkOrderAction(formData: FormData): Promise<void> {
  const user = await requireUser();
  if (!canApproveClose(user.role)) boom("ปิดจบงานได้เฉพาะหัวหน้าหรือผู้ดูแลระบบ");
  const id = Number(formData.get("id"));
  const signature = String(formData.get("signature_name") || "").trim();
  const renew = String(formData.get("renew") || "") === "1";
  const wo = getWorkOrder(id);
  if (!wo) boom("ไม่พบใบงานซ่อม");
  if (!signature) boom("กรุณาลงชื่อผู้อนุมัติปิดงาน");

  const inspection = get<{ id: number }>(
    `SELECT id FROM inspections WHERE work_order_id = ? ORDER BY id DESC LIMIT 1`,
    [id],
  );
  if (inspection) {
    const results = listInspectionResults(inspection.id);
    const pending = results.filter((r) => !r.result);
    if (pending.length) boom("กรุณาตรวจเช็คหลังซ่อมให้ครบทุกข้อก่อนปิดงาน");
    const failed = results.filter((r) => r.result === "fail");
    if (failed.length && !renew) {
      boom("มีข้อที่ไม่ผ่าน — เลือกเปิดงานใหม่ (Renew) หรือแก้ให้ผ่านก่อนปิด");
    }
  }

  const ts = nowIso();
  if (renew) {
    run(
      `UPDATE work_orders SET internal_status = 'Renew', result = 'InProcess', status = 'อยู่ระหว่างดำเนินการซ่อม', updated_at = ? WHERE id = ?`,
      [ts, id],
    );
    run(
      `UPDATE job_requests SET internal_status = 'Renew', result = 'InProcess', status = 'อยู่ระหว่างดำเนินการซ่อม', updated_at = ? WHERE id = ?`,
      [ts, wo.job_request_id],
    );
    revalidatePath(`/work-orders/${id}`);
    return;
  }

  tx(() => {
    run(
      `UPDATE work_orders SET status = 'ปิดจบงาน', internal_status = 'Closed', result = 'Finished', completed_at = ?, signature_name = ?, signed_at = ?, updated_at = ? WHERE id = ?`,
      [ts, signature, ts, ts, id],
    );
    run(
      `UPDATE job_requests SET status = 'ปิดจบงาน', internal_status = 'Closed', result = 'Finished', updated_at = ? WHERE id = ?`,
      [ts, wo.job_request_id],
    );
    const jr = getJobRequest(wo.job_request_id);
    if (jr?.job_type === "PM") {
      const schedule = get<{ id: number; frequency_days: number }>(
        `SELECT id, frequency_days FROM pm_schedules WHERE asset_id = ? AND active = 1 ORDER BY next_due_at LIMIT 1`,
        [wo.asset_id],
      );
      if (schedule) {
        const next = addDays(ts.slice(0, 10), schedule.frequency_days);
        run(
          `UPDATE pm_schedules SET last_done_at = ?, next_due_at = ? WHERE id = ?`,
          [ts.slice(0, 10), next, schedule.id],
        );
      }
    }
    if (jr?.job_type === "BD" || jr?.job_type === "CM" || jr?.job_type === "PM") {
      run(`UPDATE assets SET operational_status = 'พร้อมใช้งาน' WHERE id = ? AND operational_status = 'รถเบรคดาวน์ไม่พร้อมใช้'`, [
        wo.asset_id,
      ]);
    }
  });
  revalidatePath(`/work-orders/${id}`);
  revalidatePath("/pm");
  revalidatePath("/");
  redirect(`/work-orders/${id}`);
}

export async function generatePmJobAction(formData: FormData): Promise<void> {
  const user = await requireUser();
  if (!canCreateJobRequest(user.role) && user.role !== "Technician") {
    boom("สร้างงาน PM ไม่ได้");
  }
  const pmId = Number(formData.get("pm_id"));
  const pm = getPmSchedule(pmId);
  if (!pm) boom("ไม่พบแผน PM");
  const asset = getAsset(pm.asset_id);
  if (!asset) boom("ไม่พบเครื่องจักร");

  const existing = get<{ id: number }>(
    `SELECT id FROM job_requests WHERE asset_id = ? AND job_type = 'PM' AND status NOT IN ('ปิดจบงาน') ORDER BY id DESC LIMIT 1`,
    [asset.id],
  );
  if (existing) redirect(`/job-requests/${existing.id}`);

  const template = getTemplate("request", asset.type);
  const number = nextNumber("JR");
  const ts = nowIso();
  const id = tx(() => {
    const result = run(
      `INSERT INTO job_requests (number, asset_id, site_id, job_type, status, internal_status, result, priority, symptom, requester_id, hour_meter, location_note, created_at, updated_at)
       VALUES (?, ?, ?, 'PM', 'รอมอบหมายทีมซ่อม', 'New', 'InProcess', 'ปกติ', ?, ?, ?, ?, ?, ?)`,
      [
        number,
        asset.id,
        asset.site_id,
        pm.title,
        user.id,
        asset.hour_meter,
        asset.site_name,
        ts,
        ts,
      ],
    );
    const jrId = result.lastInsertRowid;
    if (template) createInspection({ templateId: template.id, jobRequestId: jrId, inspectorId: user.id, pmScheduleId: pm.id });
    return jrId;
  });
  revalidatePath("/pm");
  redirect(`/job-requests/${id}`);
}

export async function upsertAssetAction(
  _prev: ActionState,
  formData: FormData,
): Promise<ActionState> {
  const user = await requireUser();
  if (!canManageAssets(user.role)) return fail("บันทึกเครื่องจักรไม่ได้");
  const parsed = z
    .object({
      id: z.string().optional(),
      site_id: z.coerce.number(),
      type: z.string().min(1),
      brand: z.string().optional(),
      code: z.string().min(1, "กรุณากรอกรหัสรถ"),
      model: z.string().optional(),
      serial: z.string().optional(),
      manufacturer: z.string().optional(),
      received_at: z.string().optional(),
      warranty_until: z.string().optional(),
      asset_number: z.string().optional(),
      description: z.string().optional(),
      price: z.coerce.number().optional(),
      contact_name: z.string().optional(),
      contact_phone: z.string().optional(),
      notes: z.string().optional(),
      hour_meter: z.coerce.number().optional(),
      operational_status: z.string().optional(),
    })
    .safeParse(Object.fromEntries(formData));
  if (!parsed.success) return fail(parsed.error.issues[0]?.message || "ข้อมูลไม่ครบ");
  const d = parsed.data;
  const existingCode = get<{ id: number }>(`SELECT id FROM assets WHERE code = ?`, [d.code.trim().toUpperCase()]);
  const ts = nowIso();
  if (d.id) {
    const id = Number(d.id);
    if (existingCode && existingCode.id !== id) return fail("รหัสรถนี้มีอยู่แล้ว");
    run(
      `UPDATE assets SET site_id=?, type=?, brand=?, code=?, model=?, serial=?, manufacturer=?, received_at=?, warranty_until=?, asset_number=?, description=?, price=?, contact_name=?, contact_phone=?, notes=?, hour_meter=?, operational_status=? WHERE id=?`,
      [
        d.site_id,
        d.type,
        d.brand || null,
        d.code.trim().toUpperCase(),
        d.model || null,
        d.serial || null,
        d.manufacturer || null,
        d.received_at || null,
        d.warranty_until || null,
        d.asset_number || null,
        d.description || null,
        d.price ?? null,
        d.contact_name || null,
        d.contact_phone || null,
        d.notes || null,
        d.hour_meter ?? 0,
        d.operational_status || "พร้อมใช้งาน",
        id,
      ],
    );
    revalidatePath(`/assets/${id}`);
    redirect(`/assets/${id}`);
  }
  if (existingCode) return fail("รหัสรถนี้มีอยู่แล้ว");
  const result = run(
    `INSERT INTO assets (site_id, type, brand, code, model, serial, manufacturer, received_at, warranty_until, asset_number, description, price, contact_name, contact_phone, notes, hour_meter, operational_status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      d.site_id,
      d.type,
      d.brand || null,
      d.code.trim().toUpperCase(),
      d.model || null,
      d.serial || null,
      d.manufacturer || null,
      d.received_at || null,
      d.warranty_until || null,
      d.asset_number || null,
      d.description || null,
      d.price ?? null,
      d.contact_name || null,
      d.contact_phone || null,
      d.notes || null,
      d.hour_meter ?? 0,
      d.operational_status || "พร้อมใช้งาน",
      ts,
    ],
  );
  const id = result.lastInsertRowid;
  const pmTpl = getTemplate("pm", d.type);
  run(
    `INSERT INTO pm_schedules (asset_id, title, frequency_days, next_due_at, checklist_template_id, active, notes)
     VALUES (?, ?, 30, date('now', '+30 day'), ?, 1, 'สร้างอัตโนมัติเมื่อรับรถเข้าเครื่องจักร')`,
    [id, `PM รายเดือน ${d.code.trim().toUpperCase()}`, pmTpl?.id ?? null],
  );
  revalidatePath("/assets");
  redirect(`/assets/${id}`);
}

export async function upsertPartAction(
  _prev: ActionState,
  formData: FormData,
): Promise<ActionState> {
  const user = await requireUser();
  if (!canManageParts(user.role)) return fail("บันทึกอะไหล่ไม่ได้");
  const id = formData.get("id") ? Number(formData.get("id")) : null;
  const code = String(formData.get("code") || "").trim().toUpperCase();
  const name = String(formData.get("name") || "").trim();
  if (!code || !name) return fail("กรุณากรอกรหัสและชื่ออะไหล่");
  const fields = [
    code,
    String(formData.get("category") || ""),
    String(formData.get("oem_code") || ""),
    name,
    Number(formData.get("qty") || 0),
    Number(formData.get("unit_cost") || 0),
    String(formData.get("unit") || "PCS."),
    String(formData.get("used_on") || ""),
    String(formData.get("location") || ""),
    String(formData.get("lead_time") || ""),
    Number(formData.get("min_qty") || 0),
    Number(formData.get("reorder_qty") || 0),
    Number(formData.get("max_qty") || 0),
    String(formData.get("notes") || ""),
  ];
  if (id) {
    run(
      `UPDATE parts SET code=?, category=?, oem_code=?, name=?, qty=?, unit_cost=?, unit=?, used_on=?, location=?, lead_time=?, min_qty=?, reorder_qty=?, max_qty=?, notes=? WHERE id=?`,
      [...fields, id],
    );
    revalidatePath(`/parts/${id}`);
    redirect(`/parts/${id}`);
  }
  const dup = get<{ id: number }>(`SELECT id FROM parts WHERE code = ?`, [code]);
  if (dup) return fail("รหัสอะไหล่นี้มีอยู่แล้ว");
  const result = run(
    `INSERT INTO parts (code, category, oem_code, name, qty, unit_cost, unit, used_on, location, lead_time, min_qty, reorder_qty, max_qty, notes)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    fields,
  );
  revalidatePath("/parts");
  redirect(`/parts/${result.lastInsertRowid}`);
}

export async function upsertSiteAction(formData: FormData): Promise<void> {
  const user = await requireUser();
  if (!canManageMaster(user.role)) boom("บันทึกหน่วยงานไม่ได้");
  const id = formData.get("id") ? Number(formData.get("id")) : null;
  const company = String(formData.get("company_name") || "").trim();
  if (!company) boom("กรุณากรอกชื่อบริษัท");
  const customer = String(formData.get("customer_name") || "");
  const site = String(formData.get("site_name") || "");
  const notes = String(formData.get("notes") || "");
  if (id) {
    run(`UPDATE sites SET company_name=?, customer_name=?, site_name=?, notes=? WHERE id=?`, [
      company,
      customer,
      site,
      notes,
      id,
    ]);
  } else {
    run(`INSERT INTO sites (company_name, customer_name, site_name, notes) VALUES (?, ?, ?, ?)`, [
      company,
      customer,
      site,
      notes,
    ]);
  }
  revalidatePath("/master/sites");
  redirect("/master/sites");
}

export async function upsertTeamAction(formData: FormData): Promise<void> {
  const user = await requireUser();
  if (!canManageMaster(user.role)) boom("บันทึกทีมไม่ได้");
  const id = formData.get("id") ? Number(formData.get("id")) : null;
  const name = String(formData.get("name") || "").trim();
  if (!name) boom("กรุณากรอกชื่อทีม");
  const args = [
    name,
    String(formData.get("repair_group") || ""),
    String(formData.get("scope") || ""),
    String(formData.get("email") || ""),
    String(formData.get("notes") || ""),
  ];
  if (id) run(`UPDATE teams SET name=?, repair_group=?, scope=?, email=?, notes=? WHERE id=?`, [...args, id]);
  else run(`INSERT INTO teams (name, repair_group, scope, email, notes) VALUES (?, ?, ?, ?, ?)`, args);
  revalidatePath("/master/teams");
  redirect("/master/teams");
}

export async function upsertVendorAction(formData: FormData): Promise<void> {
  const user = await requireUser();
  if (!canManageMaster(user.role)) boom("บันทึกร้านค้าไม่ได้");
  const id = formData.get("id") ? Number(formData.get("id")) : null;
  const code = String(formData.get("code") || "").trim().toUpperCase();
  const company = String(formData.get("company_name") || "").trim();
  if (!code || !company) boom("กรุณากรอกรหัสและชื่อร้าน");
  const args = [
    code,
    company,
    String(formData.get("description") || ""),
    String(formData.get("contact") || ""),
    String(formData.get("phone") || ""),
    String(formData.get("address") || ""),
    String(formData.get("email") || ""),
    String(formData.get("notes") || ""),
  ];
  if (id) {
    run(
      `UPDATE vendors SET code=?, company_name=?, description=?, contact=?, phone=?, address=?, email=?, notes=? WHERE id=?`,
      [...args, id],
    );
  } else {
    run(
      `INSERT INTO vendors (code, company_name, description, contact, phone, address, email, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
      args,
    );
  }
  revalidatePath("/master/vendors");
  redirect("/master/vendors");
}

export async function upsertUserAction(formData: FormData): Promise<void> {
  const actor = await requireUser();
  if (!canManageUsers(actor.role)) boom("จัดการผู้ใช้ไม่ได้");
  const id = formData.get("id") ? Number(formData.get("id")) : null;
  const username = String(formData.get("username") || "").trim();
  const nameTh = String(formData.get("name_th") || "").trim();
  const nameEn = String(formData.get("name_en") || "").trim();
  const role = String(formData.get("role") || "");
  if (!username || !nameTh || !role) boom("กรุณากรอกชื่อผู้ใช้ ชื่อภาษาไทย และสิทธิ์");
  const password = String(formData.get("password") || "");
  const teamId = formData.get("team_id") ? Number(formData.get("team_id")) : null;
  const siteId = formData.get("site_id") ? Number(formData.get("site_id")) : null;
  const common = [
    username,
    nameTh,
    nameEn || nameTh,
    String(formData.get("email") || ""),
    role,
    teamId,
    siteId,
    String(formData.get("employee_code") || ""),
    String(formData.get("position") || ""),
    String(formData.get("department") || ""),
    String(formData.get("phone") || ""),
    String(formData.get("started_at") || ""),
    formData.get("active") ? 1 : 0,
  ];
  if (id) {
    if (password) {
      run(
        `UPDATE users SET username=?, name_th=?, name_en=?, email=?, role=?, team_id=?, site_id=?, employee_code=?, position=?, department=?, phone=?, started_at=?, active=?, password_hash=? WHERE id=?`,
        [...common, hashSync(password, 10), id],
      );
    } else {
      run(
        `UPDATE users SET username=?, name_th=?, name_en=?, email=?, role=?, team_id=?, site_id=?, employee_code=?, position=?, department=?, phone=?, started_at=?, active=? WHERE id=?`,
        [...common, id],
      );
    }
  } else {
    if (!password) boom("กรุณาตั้งรหัสผ่านสำหรับผู้ใช้ใหม่");
    run(
      `INSERT INTO users (username, name_th, name_en, email, role, team_id, site_id, employee_code, position, department, phone, started_at, active, password_hash, created_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [...common, hashSync(password, 10), nowIso()],
    );
  }
  revalidatePath("/master/users");
  redirect("/master/users");
}

export async function updateAssetStatusAction(formData: FormData): Promise<void> {
  const user = await requireUser();
  const assetId = Number(formData.get("asset_id"));
  const status = String(formData.get("status")) as OperationalStatus;
  const branch = String(formData.get("branch") || "");
  const details = String(formData.get("details") || "");
  const start = String(formData.get("start_at") || "") || nowIso().slice(0, 10);
  const end = String(formData.get("end_at") || "") || null;
  run(`UPDATE assets SET operational_status = ? WHERE id = ?`, [status, assetId]);
  run(
    `INSERT INTO asset_status_logs (asset_id, status, branch, details, start_at, end_at, reported_by, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
    [assetId, status, branch, details, start, end, user.id, nowIso()],
  );
  revalidatePath("/ops");
  revalidatePath(`/assets/${assetId}`);
}

export async function createRentalAction(formData: FormData): Promise<void> {
  const user = await requireUser();
  const assetId = Number(formData.get("asset_id"));
  const jobNo = String(formData.get("job_no") || "").trim();
  const customer = String(formData.get("customer_name") || "").trim();
  const site = String(formData.get("job_site") || "").trim();
  const start = String(formData.get("start_date") || "");
  const end = String(formData.get("end_date") || "");
  if (!assetId || !customer || !start || !end) boom("กรุณากรอกเครื่องจักร ลูกค้า และช่วงวันที่");
  run(
    `INSERT INTO rental_jobs (asset_id, job_no, customer_name, job_site, start_date, end_date, created_by, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
    [assetId, jobNo, customer, site, start, end, user.id, nowIso()],
  );
  run(`UPDATE assets SET operational_status = 'รถอยู่หน้างาน' WHERE id = ?`, [assetId]);
  revalidatePath("/ops");
}

export async function upsertPmAction(formData: FormData): Promise<void> {
  const user = await requireUser();
  if (!canManageAssets(user.role) && user.role !== "Technician") boom("แก้แผน PM ไม่ได้");
  const id = Number(formData.get("id"));
  const title = String(formData.get("title") || "").trim();
  const freq = Number(formData.get("frequency_days") || 30);
  const next = String(formData.get("next_due_at") || "");
  const last = String(formData.get("last_done_at") || "") || null;
  const notes = String(formData.get("notes") || "");
  const active = formData.get("active") ? 1 : 0;
  if (!title || !next) boom("กรุณากรอกชื่อแผนและวันครบกำหนด");
  run(
    `UPDATE pm_schedules SET title=?, frequency_days=?, next_due_at=?, last_done_at=?, notes=?, active=? WHERE id=?`,
    [title, freq, next, last, notes, active, id],
  );
  revalidatePath(`/pm/${id}`);
  redirect(`/pm/${id}`);
}
