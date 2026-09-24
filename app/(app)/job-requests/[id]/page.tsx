import { Badge, Button, Card, PageHeader, jobTypeTone, statusTone } from "@/components/ui";
import { convertToWorkOrderAction, saveChecklistAction } from "@/lib/actions";
import { requireUser } from "@/lib/auth";
import { getInspectionForJobRequest, getJobRequest, listInspectionResults, listTeams, listTechnicians } from "@/lib/db/queries";
import { formatThaiDateTime } from "@/lib/format";
import { canConvertToWorkOrder, canEditJobRequest } from "@/lib/permissions";
import { notFound } from "next/navigation";
import { ChecklistBlock } from "@/components/checklist-block";

export default async function JobRequestViewPage({ params }: { params: Promise<{ id: string }> }) {
  const user = await requireUser();
  const { id } = await params;
  const jr = getJobRequest(Number(id));
  if (!jr) notFound();
  const inspection = getInspectionForJobRequest(jr.id);
  const results = inspection ? listInspectionResults(inspection.id) : [];
  const teams = listTeams();
  const techs = listTechnicians();

  return (
    <div>
      <PageHeader
        title={jr.number}
        description={`${jr.asset_code} · ${jr.asset_model ?? ""} · ${jr.site_name ?? ""}`}
        actions={
          <>
            {canEditJobRequest(user.role, jr.status === "ปิดจบงาน") ? (
              <Button href={`/job-requests/${jr.id}/edit`} variant="secondary">
                แก้ไข
              </Button>
            ) : null}
            <Button href="/job-requests" variant="ghost">
              รายการ
            </Button>
          </>
        }
      />
      <div className="grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2 p-4 space-y-3">
          <div className="flex flex-wrap gap-2">
            <Badge tone={jobTypeTone(jr.job_type)}>{jr.job_type}</Badge>
            <Badge tone={statusTone(jr.status)}>{jr.status}</Badge>
            <Badge>{jr.priority}</Badge>
          </div>
          <dl className="grid sm:grid-cols-2 gap-3 text-sm">
            <div>
              <dt className="text-muted">ผู้แจ้ง</dt>
              <dd>{jr.requester_name}</dd>
            </div>
            <div>
              <dt className="text-muted">เบอร์</dt>
              <dd>{jr.requester_phone || "—"}</dd>
            </div>
            <div>
              <dt className="text-muted">ชั่วโมงเครื่อง</dt>
              <dd>{jr.hour_meter ?? "—"}</dd>
            </div>
            <div>
              <dt className="text-muted">แจ้งเมื่อ</dt>
              <dd>{formatThaiDateTime(jr.created_at)}</dd>
            </div>
            <div className="sm:col-span-2">
              <dt className="text-muted">ตำแหน่งรถ</dt>
              <dd>{jr.location_note || "—"}</dd>
            </div>
            <div className="sm:col-span-2">
              <dt className="text-muted">อาการ</dt>
              <dd className="whitespace-pre-wrap">{jr.symptom}</dd>
            </div>
            <div>
              <dt className="text-muted">ลายเซ็นผู้แจ้ง</dt>
              <dd>{jr.signature_name ? `${jr.signature_name} (${formatThaiDateTime(jr.signed_at)})` : "ยังไม่ลงนาม"}</dd>
            </div>
            <div>
              <dt className="text-muted">ใบงานซ่อม</dt>
              <dd>
                {jr.work_order_id ? (
                  <a className="underline" href={`/work-orders/${jr.work_order_id}`}>
                    {jr.work_order_number}
                  </a>
                ) : (
                  "ยังไม่เปิดใบงาน"
                )}
              </dd>
            </div>
          </dl>
        </Card>
        <Card className="p-4">
          <h2 className="font-semibold mb-2">เปิดใบงานซ่อม</h2>
          {jr.work_order_id ? (
            <p className="text-sm text-muted">เปิดแล้ว — ไปทำงานในใบงานซ่อม</p>
          ) : canConvertToWorkOrder(user.role) ? (
            <form action={convertToWorkOrderAction} className="space-y-3">
              <input type="hidden" name="job_request_id" value={jr.id} />
              <label className="block text-sm">
                ทีมซ่อม
                <select name="team_id" className="mt-1 w-full rounded-lg border border-line px-3 py-2 bg-white">
                  <option value="">ยังไม่มอบหมาย</option>
                  {teams.map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.name} · {t.repair_group}
                    </option>
                  ))}
                </select>
              </label>
              <label className="block text-sm">
                ช่าง
                <select name="technician_id" className="mt-1 w-full rounded-lg border border-line px-3 py-2 bg-white">
                  <option value="">ยังไม่มอบหมาย</option>
                  {techs.map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.name_th}
                    </option>
                  ))}
                </select>
              </label>
              <label className="block text-sm">
                ข้อมูลมอบหมาย
                <textarea name="special_info" rows={3} className="mt-1 w-full rounded-lg border border-line px-3 py-2" />
              </label>
              <Button type="submit">สร้างใบงานซ่อมจากใบนี้</Button>
            </form>
          ) : (
            <p className="text-sm text-muted">รอวิศวกรหรือหัวหน้าเปิดใบงานซ่อม</p>
          )}
        </Card>
      </div>
      {inspection ? (
        <div className="mt-4">
          <ChecklistBlock
            title="ใบตรวจก่อนแจ้งซ่อม"
            inspectionId={inspection.id}
            results={results}
            readOnly={!canEditJobRequest(user.role, jr.status === "ปิดจบงาน")}
          />
        </div>
      ) : null}
    </div>
  );
}
