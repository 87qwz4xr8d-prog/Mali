"use client";

import { updateWorkOrderAction } from "@/lib/actions";
import { Alert } from "@/components/ui";
import { JOB_STATUSES } from "@/lib/constants";
import type { Team, PublicUser, WorkOrderRow } from "@/lib/types";
import { useActionState } from "react";

export function WorkOrderEditForm({
  wo,
  teams,
  techs,
}: {
  wo: WorkOrderRow;
  teams: Team[];
  techs: PublicUser[];
}) {
  const [state, action, pending] = useActionState(updateWorkOrderAction, {});
  return (
    <form action={action} className="mt-4 space-y-3 border-t border-line pt-3">
      {state.error ? <Alert>{state.error}</Alert> : null}
      {state.success ? <Alert tone="ok">{state.success}</Alert> : null}
      <input type="hidden" name="id" value={wo.id} />
      <div className="grid sm:grid-cols-2 gap-3">
        <label className="text-sm">
          ทีม
          <select name="team_id" defaultValue={wo.team_id ?? ""} className="mt-1 w-full rounded-lg border border-line px-3 py-2 bg-white">
            <option value="">—</option>
            {teams.map((t) => (
              <option key={t.id} value={t.id}>
                {t.name}
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm">
          ช่าง
          <select name="technician_id" defaultValue={wo.technician_id ?? ""} className="mt-1 w-full rounded-lg border border-line px-3 py-2 bg-white">
            <option value="">—</option>
            {techs.map((t) => (
              <option key={t.id} value={t.id}>
                {t.name_th}
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm">
          สถานะ
          <select name="status" defaultValue={wo.status} className="mt-1 w-full rounded-lg border border-line px-3 py-2 bg-white">
            {JOB_STATUSES.map((s) => (
              <option key={s}>{s}</option>
            ))}
          </select>
        </label>
      </div>
      <label className="block text-sm">
        ข้อมูลมอบหมาย
        <textarea name="special_info" rows={2} defaultValue={wo.special_info ?? ""} className="mt-1 w-full rounded-lg border border-line px-3 py-2" />
      </label>
      <label className="block text-sm">
        สาเหตุและวิธีแก้ไข
        <textarea name="countermeasure" rows={3} defaultValue={wo.countermeasure ?? ""} className="mt-1 w-full rounded-lg border border-line px-3 py-2" />
      </label>
      <button type="submit" disabled={pending} className="rounded-lg bg-accent text-white px-3 py-2 text-sm">
        {pending ? "กำลังบันทึก…" : "บันทึกใบงาน"}
      </button>
    </form>
  );
}
