import { Badge, Button, Card, Field, PageHeader, inputClass } from "@/components/ui";
import { generatePmJobAction, upsertPmAction } from "@/lib/actions";
import { requireUser } from "@/lib/auth";
import { getPmSchedule } from "@/lib/db/queries";
import { daysUntil, formatThaiDate } from "@/lib/format";
import { notFound } from "next/navigation";

export default async function PmDetailPage({ params }: { params: Promise<{ id: string }> }) {
  await requireUser();
  const { id } = await params;
  const pm = getPmSchedule(Number(id));
  if (!pm) notFound();
  const d = daysUntil(pm.next_due_at);

  return (
    <div>
      <PageHeader
        title={pm.title}
        description={`${pm.asset_code} · ${pm.asset_model ?? ""}`}
        actions={
          <form action={generatePmJobAction}>
            <input type="hidden" name="pm_id" value={pm.id} />
            <Button type="submit">สร้างใบแจ้งซ่อม PM</Button>
          </form>
        }
      />
      <div className="grid gap-4 lg:grid-cols-2">
        <Card className="p-4 space-y-2 text-sm">
          <p>
            ครบกำหนด {formatThaiDate(pm.next_due_at)}{" "}
            <Badge tone={d < 0 ? "danger" : "ok"}>{d < 0 ? `เลย ${-d} วัน` : `อีก ${d} วัน`}</Badge>
          </p>
          <p>ทำล่าสุด {formatThaiDate(pm.last_done_at)}</p>
          <p>รอบทุก {pm.frequency_days} วัน</p>
          <p>สถานที่ {pm.site_name}</p>
        </Card>
        <Card className="p-4">
          <h2 className="font-semibold mb-3">แก้ไขแผน</h2>
          <form action={upsertPmAction} className="grid gap-3">
            <input type="hidden" name="id" value={pm.id} />
            <Field label="ชื่อแผน">
              <input name="title" defaultValue={pm.title} className={inputClass()} />
            </Field>
            <Field label="รอบ (วัน)">
              <input name="frequency_days" type="number" defaultValue={pm.frequency_days} className={inputClass()} />
            </Field>
            <Field label="ทำล่าสุด">
              <input name="last_done_at" type="date" defaultValue={pm.last_done_at ?? ""} className={inputClass()} />
            </Field>
            <Field label="ครบกำหนดถัดไป" required>
              <input name="next_due_at" type="date" required defaultValue={pm.next_due_at} className={inputClass()} />
            </Field>
            <Field label="หมายเหตุ">
              <textarea name="notes" defaultValue={pm.notes ?? ""} className={inputClass()} />
            </Field>
            <label className="text-sm flex items-center gap-2">
              <input type="checkbox" name="active" defaultChecked={pm.active === 1} />
              ใช้งานแผนนี้
            </label>
            <Button type="submit" variant="secondary">
              บันทึกแผน
            </Button>
          </form>
        </Card>
      </div>
    </div>
  );
}
