import { Badge, Button, Empty, PageHeader, jobTypeTone, statusTone } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { JOB_STATUSES } from "@/lib/constants";
import { listWorkOrders } from "@/lib/db/queries";
import { formatThaiDateTime } from "@/lib/format";
import Link from "next/link";

export default async function WorkOrderListPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; status?: string }>;
}) {
  const user = await requireUser();
  const sp = await searchParams;
  const rows = listWorkOrders({
    q: sp.q,
    status: sp.status,
    technicianId: user.role === "Technician" ? user.id : undefined,
  });

  return (
    <div>
      <PageHeader
        title="ใบงานซ่อม"
        description="เปิดจากใบแจ้งซ่อมเท่านั้น — ไม่สร้างใบงานลอย"
      />
      <form className="mb-4 flex flex-col sm:flex-row gap-2">
        <input
          name="q"
          defaultValue={sp.q}
          placeholder="ค้นหาเลข MC / JR / รหัสรถ"
          className="flex-1 rounded-lg border border-line bg-white px-3 py-2 text-sm"
        />
        <select name="status" defaultValue={sp.status || ""} className="rounded-lg border border-line bg-white px-3 py-2 text-sm">
          <option value="">ทุกสถานะ</option>
          {JOB_STATUSES.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
        <Button type="submit" variant="secondary">
          กรอง
        </Button>
      </form>
      {rows.length === 0 ? (
        <Empty
          title="ยังไม่มีใบงานซ่อม"
          description="เปิดใบงานจากใบแจ้งซ่อมที่รับแล้ว"
          action={<Button href="/job-requests">ไปที่ใบแจ้งซ่อม</Button>}
        />
      ) : (
        <div className="table-wrap rounded-xl border border-line bg-card">
          <table className="data">
            <thead>
              <tr>
                <th>เลขที่</th>
                <th>อ้างอิง JR</th>
                <th>รถ</th>
                <th>ประเภท</th>
                <th>ช่าง</th>
                <th>สถานะ</th>
                <th>เปิดเมื่อ</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.id}>
                  <td>
                    <Link href={`/work-orders/${r.id}`} className="font-medium hover:underline">
                      {r.number}
                    </Link>
                  </td>
                  <td>
                    <Link href={`/job-requests/${r.job_request_id}`} className="text-sm hover:underline">
                      {r.job_request_number}
                    </Link>
                  </td>
                  <td>
                    {r.asset_code}
                    <div className="text-xs text-muted">{r.asset_model}</div>
                  </td>
                  <td>
                    <Badge tone={jobTypeTone(r.job_type)}>{r.job_type}</Badge>
                  </td>
                  <td>{r.technician_name || "—"}</td>
                  <td>
                    <Badge tone={statusTone(r.status)}>{r.status}</Badge>
                  </td>
                  <td>{formatThaiDateTime(r.created_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
