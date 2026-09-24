import { ChecklistBlock } from "@/components/checklist-block";
import { Badge, Button, Card, PageHeader, jobTypeTone, statusTone } from "@/components/ui";
import {
  addWorkOrderPartAction,
  closeWorkOrderAction,
  updateWorkOrderAction,
} from "@/lib/actions";
import { requireUser } from "@/lib/auth";
import { JOB_STATUSES } from "@/lib/constants";
import {
  getInspectionForWorkOrder,
  getWorkOrder,
  listInspectionResults,
  listParts,
  listTeams,
  listTechnicians,
  listWorkOrderParts,
} from "@/lib/db/queries";
import { baht, formatThaiDateTime } from "@/lib/format";
import { canApproveClose, canAssignWorkOrder, canEnterRepair, canIssueParts } from "@/lib/permissions";
import { notFound } from "next/navigation";
import { WorkOrderEditForm } from "./edit-form";

export default async function WorkOrderViewPage({ params }: { params: Promise<{ id: string }> }) {
  const user = await requireUser();
  const { id } = await params;
  const wo = getWorkOrder(Number(id));
  if (!wo) notFound();
  const parts = listWorkOrderParts(wo.id);
  const inspection = getInspectionForWorkOrder(wo.id);
  const results = inspection ? listInspectionResults(inspection.id) : [];
  const teams = listTeams();
  const techs = listTechnicians();
  const catalog = listParts();
  const cost = parts.reduce((s, p) => s + p.total_price, 0);
  const canEdit = canEnterRepair(user.role) || canAssignWorkOrder(user.role);

  return (
    <div>
      <PageHeader
        title={wo.number}
        description={`${wo.asset_code} · จากใบแจ้งซ่อม ${wo.job_request_number}`}
        actions={
          <Button href="/work-orders" variant="ghost">
            รายการ
          </Button>
        }
      />
      <div className="flex flex-wrap gap-2 mb-4">
        <Badge tone={jobTypeTone(wo.job_type)}>{wo.job_type}</Badge>
        <Badge tone={statusTone(wo.status)}>{wo.status}</Badge>
        <Badge>{wo.result}</Badge>
      </div>
      <div className="grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2 p-4 space-y-3 text-sm">
          <p className="whitespace-pre-wrap">{wo.symptom}</p>
          <dl className="grid sm:grid-cols-2 gap-3">
            <div>
              <dt className="text-muted">ทีม</dt>
              <dd>{wo.team_name || "—"}</dd>
            </div>
            <div>
              <dt className="text-muted">ช่าง</dt>
              <dd>{wo.technician_name || "—"}</dd>
            </div>
            <div>
              <dt className="text-muted">เริ่ม</dt>
              <dd>{formatThaiDateTime(wo.started_at)}</dd>
            </div>
            <div>
              <dt className="text-muted">เสร็จ</dt>
              <dd>{formatThaiDateTime(wo.completed_at)}</dd>
            </div>
            <div className="sm:col-span-2">
              <dt className="text-muted">ข้อมูลมอบหมาย</dt>
              <dd className="whitespace-pre-wrap">{wo.special_info || "—"}</dd>
            </div>
            <div className="sm:col-span-2">
              <dt className="text-muted">สาเหตุและวิธีแก้ไข</dt>
              <dd className="whitespace-pre-wrap">{wo.countermeasure || "ยังไม่บันทึก"}</dd>
            </div>
            <div>
              <dt className="text-muted">ลายเซ็นปิดงาน</dt>
              <dd>{wo.signature_name || "—"}</dd>
            </div>
          </dl>
          {canEdit && wo.status !== "ปิดจบงาน" ? (
            <WorkOrderEditForm wo={wo} teams={teams} techs={techs} />
          ) : null}
        </Card>
        <div className="space-y-4">
          <Card className="p-4">
            <h2 className="font-semibold mb-2">อะไหล่ในใบงาน · {baht(cost)}</h2>
            {parts.length === 0 ? (
              <p className="text-sm text-muted">ยังไม่เบิกอะไหล่</p>
            ) : (
              <ul className="text-sm divide-y divide-line">
                {parts.map((p) => (
                  <li key={p.id} className="py-2 flex justify-between gap-2">
                    <span>
                      {p.part_code} {p.part_name}
                      <span className="block text-xs text-muted">
                        {p.qty} {p.unit} × {baht(p.cost_per_unit)}
                      </span>
                    </span>
                    <span>{baht(p.total_price)}</span>
                  </li>
                ))}
              </ul>
            )}
            {canIssueParts(user.role) && wo.status !== "ปิดจบงาน" ? (
              <form action={addWorkOrderPartAction} className="mt-3 space-y-2">
                <input type="hidden" name="work_order_id" value={wo.id} />
                <select name="part_id" required className="w-full rounded-lg border border-line px-3 py-2 text-sm bg-white">
                  <option value="">เลือกอะไหล่</option>
                  {catalog.map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.code} · {p.name} (คงเหลือ {p.qty})
                    </option>
                  ))}
                </select>
                <input name="qty" type="number" min={1} defaultValue={1} className="w-full rounded-lg border border-line px-3 py-2 text-sm" />
                <Button type="submit" variant="secondary">
                  เบิกเข้าใบงาน
                </Button>
              </form>
            ) : null}
          </Card>
          {canApproveClose(user.role) && wo.status !== "ปิดจบงาน" ? (
            <Card className="p-4">
              <h2 className="font-semibold mb-2">อนุมัติปิดงาน</h2>
              <p className="text-xs text-muted mb-2">ต้องตรวจหลังซ่อมครบทุกข้อก่อนปิดจบ</p>
              <form action={closeWorkOrderAction} className="space-y-2">
                <input type="hidden" name="id" value={wo.id} />
                <input name="signature_name" required placeholder="ชื่อผู้อนุมัติ" className="w-full rounded-lg border border-line px-3 py-2 text-sm" />
                <label className="flex items-center gap-2 text-sm">
                  <input type="checkbox" name="renew" value="1" />
                  ส่งกลับช่าง (Renew) ถ้ายังไม่ผ่าน
                </label>
                <Button type="submit">ปิดจบงาน</Button>
              </form>
            </Card>
          ) : null}
        </div>
      </div>
      {inspection ? (
        <div className="mt-4">
          <ChecklistBlock
            title="ตรวจหลังซ่อม"
            inspectionId={inspection.id}
            results={results}
            readOnly={!canEnterRepair(user.role) || wo.status === "ปิดจบงาน"}
          />
        </div>
      ) : null}
    </div>
  );
}
