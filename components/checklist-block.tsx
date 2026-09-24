import { saveChecklistAction } from "@/lib/actions";
import { Badge } from "@/components/ui";
import type { ChecklistResult } from "@/lib/types";

export function ChecklistBlock({
  title,
  inspectionId,
  results,
  readOnly,
}: {
  title: string;
  inspectionId: number;
  results: ChecklistResult[];
  readOnly?: boolean;
}) {
  const fail = results.filter((r) => r.result === "fail").length;
  const pass = results.filter((r) => r.result === "pass").length;
  return (
    <section className="rounded-xl border border-line bg-card p-4">
      <div className="flex flex-wrap items-center justify-between gap-2 mb-3">
        <h2 className="font-semibold">{title}</h2>
        <div className="flex gap-2 text-xs">
          <Badge tone="ok">ผ่าน {pass}</Badge>
          <Badge tone="danger">ไม่ผ่าน {fail}</Badge>
        </div>
      </div>
      <form action={saveChecklistAction} className="space-y-3">
        <input type="hidden" name="inspection_id" value={inspectionId} />
        {results.map((item) => (
          <div key={item.item_id} className="grid gap-2 sm:grid-cols-[1fr_auto] border-b border-line pb-3">
            <div>
              <p className="text-sm">
                <span className="text-muted">{item.code}</span> · {item.label}
              </p>
              {readOnly ? (
                item.notes ? <p className="text-xs text-muted">{item.notes}</p> : null
              ) : (
                <input
                  name={`check_note_${item.item_id}`}
                  defaultValue={item.notes ?? ""}
                  className="mt-1 w-full rounded-lg border border-line px-3 py-1.5 text-sm"
                  placeholder="หมายเหตุ"
                />
              )}
            </div>
            {readOnly ? (
              <Badge tone={item.result === "fail" ? "danger" : item.result === "pass" ? "ok" : "neutral"}>
                {item.result === "pass" ? "ผ่าน" : item.result === "fail" ? "ไม่ผ่าน" : item.result === "na" ? "ไม่เกี่ยวข้อง" : "ยังไม่ตรวจ"}
              </Badge>
            ) : (
              <select
                name={`check_${item.item_id}`}
                defaultValue={item.result}
                className="h-9 rounded-lg border border-line bg-white px-2 text-sm"
              >
                <option value="">ยังไม่ตรวจ</option>
                <option value="pass">ผ่าน</option>
                <option value="fail">ไม่ผ่าน</option>
                <option value="na">ไม่เกี่ยวข้อง</option>
              </select>
            )}
          </div>
        ))}
        {readOnly ? null : (
          <button type="submit" className="rounded-lg bg-accent text-white px-3 py-2 text-sm">
            บันทึกใบตรวจ
          </button>
        )}
      </form>
    </section>
  );
}
