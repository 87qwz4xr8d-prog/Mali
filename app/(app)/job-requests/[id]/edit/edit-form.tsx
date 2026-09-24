"use client";

import { updateJobRequestAction } from "@/lib/actions";
import { Alert, Field, inputClass } from "@/components/ui";
import { PRIORITIES } from "@/lib/constants";
import type { JobRequestRow } from "@/lib/types";
import { useActionState } from "react";

export function EditJobRequestForm({ jr }: { jr: JobRequestRow }) {
  const [state, action, pending] = useActionState(updateJobRequestAction, {});
  return (
    <form action={action} className="grid gap-4 sm:grid-cols-2 rounded-xl border border-line bg-card p-4">
      {state.error ? <div className="sm:col-span-2"><Alert>{state.error}</Alert></div> : null}
      {state.success ? <div className="sm:col-span-2"><Alert tone="ok">{state.success}</Alert></div> : null}
      <input type="hidden" name="id" value={jr.id} />
      <Field label="ความเร่งด่วน">
        <select name="priority" defaultValue={jr.priority} className={inputClass()}>
          {PRIORITIES.map((p) => (
            <option key={p}>{p}</option>
          ))}
        </select>
      </Field>
      <Field label="ชั่วโมงเครื่อง">
        <input name="hour_meter" type="number" defaultValue={jr.hour_meter ?? ""} className={inputClass()} />
      </Field>
      <Field label="เบอร์ผู้แจ้ง">
        <input name="requester_phone" defaultValue={jr.requester_phone ?? ""} className={inputClass()} />
      </Field>
      <Field label="ตำแหน่งรถ">
        <input name="location_note" defaultValue={jr.location_note ?? ""} className={inputClass()} />
      </Field>
      <div className="sm:col-span-2">
        <Field label="อาการ">
          <textarea name="symptom" rows={4} defaultValue={jr.symptom ?? ""} className={inputClass()} />
        </Field>
      </div>
      <Field label="ลายเซ็นผู้แจ้ง">
        <input name="signature_name" defaultValue={jr.signature_name ?? ""} className={inputClass()} />
      </Field>
      <div className="sm:col-span-2">
        <button type="submit" disabled={pending} className="rounded-lg bg-accent text-white px-4 py-2 text-sm">
          {pending ? "กำลังบันทึก…" : "บันทึก"}
        </button>
      </div>
    </form>
  );
}
