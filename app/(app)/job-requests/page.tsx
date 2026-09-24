import { Badge, Button, Empty, PageHeader, jobTypeTone, statusTone } from "@/components/ui";
import { JOB_STATUSES, JOB_TYPES } from "@/lib/constants";
import { listJobRequests } from "@/lib/db/queries";
import { formatThaiDateTime } from "@/lib/format";
import { canCreateJobRequest } from "@/lib/permissions";
import { requireUser } from "@/lib/auth";
import Link from "next/link";

export default async function JobRequestListPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; status?: string; jobType?: string }>;
}) {
  const user = await requireUser();
  const sp = await searchParams;
  const rows = listJobRequests({
    q: sp.q,
    status: sp.status,
    jobType: sp.jobType,
  });

  return (
    <div>
      <PageHeader
        title="ใบแจ้งซ่อม"
        description="สร้างและติดตามใบแจ้งซ่อมก่อนเปิดใบงานซ่อม"
        actions={
          canCreateJobRequest(user.role) ? <Button href="/job-requests/new">สร้างใบแจ้งซ่อม</Button> : null
        }
      />
      <form className="mb-4 flex flex-col sm:flex-row gap-2">
        <input
          name="q"
          defaultValue={sp.q}
          placeholder="ค้นหาเลขที่ / รหัสรถ / อาการ"
          className="flex-1 rounded-lg border border-line bg-white px-3 py-2 text-sm"
        />
        <select name="jobType" defaultValue={sp.jobType || ""} className="rounded-lg border border-line bg-white px-3 py-2 text-sm">
          <option value="">ทุกประเภทงาน</option>
          {JOB_TYPES.map((t) => (
            <option key={t.code} value={t.code}>
              {t.code}
            </option>
          ))}
        </select>
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
          title="ยังไม่มีใบแจ้งซ่อมตามเงื่อนไขนี้"
          description="ลองล้างตัวกรอง หรือสร้างใบใหม่จากรถที่เสีย"
          action={canCreateJobRequest(user.role) ? <Button href="/job-requests/new">สร้างใบแจ้งซ่อม</Button> : null}
        />
      ) : (
        <div className="table-wrap rounded-xl border border-line bg-card">
          <table className="data">
            <thead>
              <tr>
                <th>เลขที่</th>
                <th>รถ</th>
                <th>ประเภท</th>
                <th>อาการ</th>
                <th>สถานะ</th>
                <th>ผู้แจ้ง</th>
                <th>วันที่</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.id}>
                  <td>
                    <Link href={`/job-requests/${r.id}`} className="font-medium hover:underline">
                      {r.number}
                    </Link>
                    {r.work_order_number ? (
                      <div className="text-xs text-muted">WO {r.work_order_number}</div>
                    ) : null}
                  </td>
                  <td>
                    {r.asset_code}
                    <div className="text-xs text-muted">{r.asset_model}</div>
                  </td>
                  <td>
                    <Badge tone={jobTypeTone(r.job_type)}>{r.job_type}</Badge>
                  </td>
                  <td className="max-w-xs">
                    <span className="line-clamp-2">{r.symptom}</span>
                  </td>
                  <td>
                    <Badge tone={statusTone(r.status)}>{r.status}</Badge>
                  </td>
                  <td>{r.requester_name}</td>
                  <td className="whitespace-nowrap">{formatThaiDateTime(r.created_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
