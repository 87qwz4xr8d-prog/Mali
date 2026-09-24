"use client";

import { upsertPartAction } from "@/lib/actions";
import { Alert, Field, inputClass } from "@/components/ui";
import { PART_CATEGORIES, UNITS } from "@/lib/constants";
import type { Part } from "@/lib/types";
import { useActionState } from "react";

export function PartForm({ part }: { part?: Part }) {
  const [state, action, pending] = useActionState(upsertPartAction, {});
  return (
    <form action={action} className="grid gap-4 sm:grid-cols-2 rounded-xl border border-line bg-card p-4">
      {state.error ? <div className="sm:col-span-2"><Alert>{state.error}</Alert></div> : null}
      {part ? <input type="hidden" name="id" value={part.id} /> : null}
      <Field label="รหัส" required hint="เช่น YTP-E-0100">
        <input name="code" required defaultValue={part?.code} className={inputClass()} />
      </Field>
      <Field label="หมวด">
        <select name="category" defaultValue={part?.category ?? "E"} className={inputClass()}>
          {PART_CATEGORIES.map((c) => (
            <option key={c.code} value={c.code}>
              {c.label}
            </option>
          ))}
        </select>
      </Field>
      <Field label="พาสโค้ด (OEM)">
        <input name="oem_code" defaultValue={part?.oem_code ?? ""} className={inputClass()} />
      </Field>
      <Field label="รายการ" required>
        <input name="name" required defaultValue={part?.name} className={inputClass()} />
      </Field>
      <Field label="จำนวนคงเหลือ">
        <input name="qty" type="number" defaultValue={part?.qty ?? 0} className={inputClass()} />
      </Field>
      <Field label="หน่วย">
        <select name="unit" defaultValue={part?.unit ?? "PCS."} className={inputClass()}>
          {UNITS.map((u) => (
            <option key={u}>{u}</option>
          ))}
        </select>
      </Field>
      <Field label="ราคาต้นทุน">
        <input name="unit_cost" type="number" defaultValue={part?.unit_cost ?? 0} className={inputClass()} />
      </Field>
      <Field label="ใช้กับเครื่อง">
        <input name="used_on" defaultValue={part?.used_on ?? ""} className={inputClass()} />
      </Field>
      <Field label="ที่เก็บ">
        <input name="location" defaultValue={part?.location ?? ""} className={inputClass()} />
      </Field>
      <Field label="ระยะเวลาส่งมอบ">
        <input name="lead_time" defaultValue={part?.lead_time ?? ""} className={inputClass()} />
      </Field>
      <Field label="จำนวนต่ำสุด">
        <input name="min_qty" type="number" defaultValue={part?.min_qty ?? 0} className={inputClass()} />
      </Field>
      <Field label="จำนวนต้องสั่งซื้อ">
        <input name="reorder_qty" type="number" defaultValue={part?.reorder_qty ?? 0} className={inputClass()} />
      </Field>
      <Field label="จำนวนสูงสุด">
        <input name="max_qty" type="number" defaultValue={part?.max_qty ?? 0} className={inputClass()} />
      </Field>
      <div className="sm:col-span-2">
        <Field label="หมายเหตุ">
          <textarea name="notes" defaultValue={part?.notes ?? ""} className={inputClass()} />
        </Field>
      </div>
      <div className="sm:col-span-2">
        <button type="submit" disabled={pending} className="rounded-lg bg-accent text-white px-4 py-2 text-sm">
          {pending ? "กำลังบันทึก…" : "บันทึกอะไหล่"}
        </button>
      </div>
    </form>
  );
}
