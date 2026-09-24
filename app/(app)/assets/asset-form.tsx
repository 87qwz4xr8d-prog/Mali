"use client";

import { upsertAssetAction } from "@/lib/actions";
import { Alert, Field, inputClass } from "@/components/ui";
import { LIFT_TYPES, LIFT_TYPE_TH, OPERATIONAL_STATUSES } from "@/lib/constants";
import type { AssetRow, Site } from "@/lib/types";
import { useActionState } from "react";

export function AssetForm({ asset, sites }: { asset?: AssetRow; sites: Site[] }) {
  const [state, action, pending] = useActionState(upsertAssetAction, {});
  return (
    <form action={action} className="grid gap-4 sm:grid-cols-2 rounded-xl border border-line bg-card p-4">
      {state.error ? <div className="sm:col-span-2"><Alert>{state.error}</Alert></div> : null}
      {asset ? <input type="hidden" name="id" value={asset.id} /> : null}
      <Field label="รหัสรถ" required hint="เช่น YB007">
        <input name="code" required defaultValue={asset?.code} className={inputClass()} />
      </Field>
      <Field label="หน่วยงาน / หน้างาน" required>
        <select name="site_id" required defaultValue={asset?.site_id} className={inputClass()}>
          <option value="">เลือกหน้างาน</option>
          {sites.map((s) => (
            <option key={s.id} value={s.id}>
              {s.company_name} · {s.site_name}
            </option>
          ))}
        </select>
      </Field>
      <Field label="ประเภท" required>
        <select name="type" required defaultValue={asset?.type ?? "Boom Lift"} className={inputClass()}>
          {LIFT_TYPES.map((t) => (
            <option key={t} value={t}>
              {LIFT_TYPE_TH[t]} ({t})
            </option>
          ))}
        </select>
      </Field>
      <Field label="ยี่ห้อ">
        <input name="brand" defaultValue={asset?.brand ?? ""} className={inputClass()} />
      </Field>
      <Field label="รุ่น">
        <input name="model" defaultValue={asset?.model ?? ""} className={inputClass()} />
      </Field>
      <Field label="หมายเลขเครื่อง">
        <input name="serial" defaultValue={asset?.serial ?? ""} className={inputClass()} />
      </Field>
      <Field label="ผู้ผลิต">
        <input name="manufacturer" defaultValue={asset?.manufacturer ?? ""} className={inputClass()} />
      </Field>
      <Field label="เลขทรัพย์สิน">
        <input name="asset_number" defaultValue={asset?.asset_number ?? ""} className={inputClass()} />
      </Field>
      <Field label="วันที่รับเข้าระบบ">
        <input name="received_at" type="date" defaultValue={asset?.received_at ?? ""} className={inputClass()} />
      </Field>
      <Field label="วันที่หมดประกัน">
        <input name="warranty_until" type="date" defaultValue={asset?.warranty_until ?? ""} className={inputClass()} />
      </Field>
      <Field label="ราคา">
        <input name="price" type="number" defaultValue={asset?.price ?? ""} className={inputClass()} />
      </Field>
      <Field label="ชั่วโมงเครื่อง">
        <input name="hour_meter" type="number" defaultValue={asset?.hour_meter ?? 0} className={inputClass()} />
      </Field>
      <Field label="ผู้ติดต่อ">
        <input name="contact_name" defaultValue={asset?.contact_name ?? ""} className={inputClass()} />
      </Field>
      <Field label="เบอร์ติดต่อ">
        <input name="contact_phone" defaultValue={asset?.contact_phone ?? ""} className={inputClass()} />
      </Field>
      <Field label="สถานะใช้งาน">
        <select name="operational_status" defaultValue={asset?.operational_status ?? "พร้อมใช้งาน"} className={inputClass()}>
          {OPERATIONAL_STATUSES.map((s) => (
            <option key={s}>{s}</option>
          ))}
        </select>
      </Field>
      <div className="sm:col-span-2">
        <Field label="รายละเอียดเพิ่มเติม">
          <textarea name="description" defaultValue={asset?.description ?? ""} className={inputClass()} />
        </Field>
      </div>
      <div className="sm:col-span-2">
        <Field label="หมายเหตุ">
          <textarea name="notes" defaultValue={asset?.notes ?? ""} className={inputClass()} />
        </Field>
      </div>
      <div className="sm:col-span-2">
        <button type="submit" disabled={pending} className="rounded-lg bg-accent text-white px-4 py-2 text-sm">
          {pending ? "กำลังบันทึก…" : "บันทึกเครื่องจักร"}
        </button>
      </div>
    </form>
  );
}
