"use client";

import { createJobRequestAction } from "@/lib/actions";
import { Alert, Field, inputClass } from "@/components/ui";
import { JOB_TYPES, PRIORITIES } from "@/lib/constants";
import type { ChecklistItem } from "@/lib/types";
import { useActionState } from "react";

export function JobRequestForm({
  assets,
  items,
  defaultPhone,
}: {
  assets: { id: number; label: string }[];
  items: ChecklistItem[];
  defaultPhone: string;
}) {
  const [state, action, pending] = useActionState(createJobRequestAction, {});
  return (
    <form action={action} className="space-y-6">
      {state.error ? <Alert>{state.error}</Alert> : null}
      <div className="grid gap-4 sm:grid-cols-2 rounded-xl border border-line bg-card p-4">
        <Field label="เครื่องจักร" required>
          <select name="asset_id" required className={inputClass()}>
            <option value="">เลือกโค้ดรถ</option>
            {assets.map((a) => (
              <option key={a.id} value={a.id}>
                {a.label}
              </option>
            ))}
          </select>
        </Field>
        <Field label="ประเภทงาน" required>
          <select name="job_type" required className={inputClass()} defaultValue="BD">
            {JOB_TYPES.map((t) => (
              <option key={t.code} value={t.code}>
                {t.label} — {t.hint}
              </option>
            ))}
          </select>
        </Field>
        <Field label="ความเร่งด่วน">
          <select name="priority" className={inputClass()} defaultValue="ปกติ">
            {PRIORITIES.map((p) => (
              <option key={p}>{p}</option>
            ))}
          </select>
        </Field>
        <Field label="ชั่วโมงเครื่อง">
          <input name="hour_meter" type="number" min={0} className={inputClass()} />
        </Field>
        <Field label="เบอร์ผู้แจ้ง">
          <input name="requester_phone" defaultValue={defaultPhone} className={inputClass()} />
        </Field>
        <Field label="ตำแหน่งรถ / หน้างาน">
          <input name="location_note" className={inputClass()} placeholder="เช่น อู่หนองใหญ่ ช่อง 2" />
        </Field>
        <div className="sm:col-span-2">
          <Field label="อาการ / รายละเอียด" required>
            <textarea name="symptom" required rows={4} className={inputClass()} placeholder="อธิบายอาการให้ช่างอ่านแล้วทำงานต่อได้" />
          </Field>
        </div>
        <Field label="ลายเซ็นผู้แจ้ง" hint="พิมพ์ชื่อ-สกุลแทนลายเซ็นบนกระดาษ">
          <input name="signature_name" className={inputClass()} placeholder="ชื่อผู้ลงนาม" />
        </Field>
      </div>
      <section className="rounded-xl border border-line bg-card p-4">
        <h2 className="font-semibold">ตรวจเช็คก่อนแจ้งซ่อม</h2>
        <p className="text-sm text-muted mb-3">ผ่าน / ไม่ผ่าน / ไม่เกี่ยวข้อง — ไม่บังคับครบทุกข้อตอนสร้างใบ</p>
        <ol className="space-y-3">
          {items.map((item) => (
            <li key={item.id} className="grid gap-2 sm:grid-cols-[1fr_auto] sm:items-center border-b border-line pb-3">
              <div>
                <p className="text-sm font-medium">
                  {item.code} · {item.label}
                </p>
                <input
                  name={`check_note_${item.id}`}
                  className={inputClass("mt-1")}
                  placeholder="หมายเหตุ (ถ้ามี)"
                />
              </div>
              <select name={`check_${item.id}`} className={inputClass("w-36")}>
                <option value="">ยังไม่ตรวจ</option>
                <option value="pass">ผ่าน</option>
                <option value="fail">ไม่ผ่าน</option>
                <option value="na">ไม่เกี่ยวข้อง</option>
              </select>
            </li>
          ))}
        </ol>
      </section>
      <button
        type="submit"
        disabled={pending}
        className="rounded-lg bg-accent text-white px-4 py-2.5 text-sm font-medium disabled:opacity-60"
      >
        {pending ? "กำลังบันทึก…" : "บันทึกใบแจ้งซ่อม"}
      </button>
    </form>
  );
}
