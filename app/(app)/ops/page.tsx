import { Badge, Button, Card, PageHeader, statusTone } from "@/components/ui";
import { createRentalAction, updateAssetStatusAction } from "@/lib/actions";
import { requireUser } from "@/lib/auth";
import { BRANCHES, OPERATIONAL_STATUSES } from "@/lib/constants";
import { listAssets, listRentalJobs } from "@/lib/db/queries";
import { formatThaiDate } from "@/lib/format";
import Link from "next/link";

export default async function OpsPage() {
  await requireUser();
  const assets = listAssets();
  const rentals = listRentalJobs();
  const byStatus = OPERATIONAL_STATUSES.map((s) => ({
    status: s,
    rows: assets.filter((a) => a.operational_status === s),
  }));

  return (
    <div>
      <PageHeader
        title="สถานะรถประจำวัน"
        description="พร้อมใช้ / รอตรวจ / เบรคดาวน์ / อยู่หน้างาน — overlay จากระบบ fleet ไม่ใช่ใบงานซ่อม"
      />
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        {byStatus.map((g) => (
          <Card key={g.status} className="p-4">
            <p className="text-xs text-muted">{g.status}</p>
            <p className="text-2xl font-semibold">{g.rows.length}</p>
          </Card>
        ))}
      </div>
      <div className="table-wrap rounded-xl border border-line bg-card mb-6">
        <table className="data">
          <thead>
            <tr>
              <th>รหัส</th>
              <th>รุ่น</th>
              <th>หน้างาน</th>
              <th>สถานะ</th>
            </tr>
          </thead>
          <tbody>
            {assets.map((a) => (
              <tr key={a.id}>
                <td>
                  <Link href={`/assets/${a.id}`} className="font-medium hover:underline">
                    {a.code}
                  </Link>
                </td>
                <td>
                  {a.brand} {a.model}
                </td>
                <td>{a.site_name}</td>
                <td>
                  <Badge tone={statusTone(a.operational_status)}>{a.operational_status}</Badge>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="grid gap-4 lg:grid-cols-2">
        <Card className="p-4">
          <h2 className="font-semibold mb-3">บันทึกสถานะ</h2>
          <form action={updateAssetStatusAction} className="grid gap-2">
            <select name="asset_id" required className="rounded-lg border border-line px-3 py-2 text-sm bg-white">
              <option value="">เลือกรถ</option>
              {assets.map((a) => (
                <option key={a.id} value={a.id}>
                  {a.code}
                </option>
              ))}
            </select>
            <select name="status" className="rounded-lg border border-line px-3 py-2 text-sm bg-white">
              {OPERATIONAL_STATUSES.map((s) => (
                <option key={s}>{s}</option>
              ))}
            </select>
            <select name="branch" className="rounded-lg border border-line px-3 py-2 text-sm bg-white">
              {BRANCHES.map((b) => (
                <option key={b}>{b}</option>
              ))}
            </select>
            <input name="details" placeholder="รายละเอียด" className="rounded-lg border border-line px-3 py-2 text-sm" />
            <div className="grid grid-cols-2 gap-2">
              <input name="start_at" type="date" className="rounded-lg border border-line px-3 py-2 text-sm" />
              <input name="end_at" type="date" className="rounded-lg border border-line px-3 py-2 text-sm" />
            </div>
            <Button type="submit">บันทึกสถานะ</Button>
          </form>
        </Card>
        <Card className="p-4">
          <h2 className="font-semibold mb-3">งานเช่า / หน้างาน (SV)</h2>
          <ul className="text-sm divide-y divide-line mb-3">
            {rentals.map((r) => (
              <li key={r.id} className="py-2">
                {r.job_no} · {r.customer_name} · {r.job_site}
                <span className="block text-xs text-muted">
                  {formatThaiDate(r.start_date)} – {formatThaiDate(r.end_date)}
                </span>
              </li>
            ))}
          </ul>
          <form action={createRentalAction} className="grid gap-2">
            <select name="asset_id" required className="rounded-lg border border-line px-3 py-2 text-sm bg-white">
              <option value="">เลือกรถ</option>
              {assets.map((a) => (
                <option key={a.id} value={a.id}>
                  {a.code}
                </option>
              ))}
            </select>
            <input name="job_no" placeholder="เลขงาน" className="rounded-lg border border-line px-3 py-2 text-sm" />
            <input name="customer_name" required placeholder="ลูกค้า" className="rounded-lg border border-line px-3 py-2 text-sm" />
            <input name="job_site" placeholder="หน้างาน" className="rounded-lg border border-line px-3 py-2 text-sm" />
            <div className="grid grid-cols-2 gap-2">
              <input name="start_date" type="date" required className="rounded-lg border border-line px-3 py-2 text-sm" />
              <input name="end_date" type="date" required className="rounded-lg border border-line px-3 py-2 text-sm" />
            </div>
            <Button type="submit" variant="secondary">
              บันทึกงานเช่า
            </Button>
          </form>
        </Card>
      </div>
    </div>
  );
}
