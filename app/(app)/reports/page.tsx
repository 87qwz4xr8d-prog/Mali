import { Badge, Card, PageHeader, statusTone } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { reportOpenBreakdown, reportPartsUsed, reportPmCompliance } from "@/lib/db/queries";
import { baht, formatThaiDate } from "@/lib/format";
import { canViewReports } from "@/lib/permissions";
import Link from "next/link";
import { redirect } from "next/navigation";

export default async function ReportsPage() {
  const user = await requireUser();
  if (!canViewReports(user.role)) redirect("/");
  const bd = reportOpenBreakdown();
  const pm = reportPmCompliance();
  const parts = reportPartsUsed();
  const rate = pm.total ? Math.round((pm.ok / pm.total) * 100) : 0;

  return (
    <div>
      <PageHeader title="รายงาน" description="งาน BD เปิดอยู่ · ความครบกำหนด PM · อะไหล่ที่ใช้ในใบงาน" />
      <div className="grid gap-3 sm:grid-cols-3 mb-6">
        <Card className="p-4">
          <p className="text-xs text-muted">Breakdown เปิดอยู่</p>
          <p className="text-2xl font-semibold">{bd.length}</p>
        </Card>
        <Card className="p-4">
          <p className="text-xs text-muted">แผน PM ที่ยังไม่เลยกำหนด</p>
          <p className="text-2xl font-semibold">{rate}%</p>
          <p className="text-xs text-muted">
            ครบ {pm.ok} / เลย {pm.overdue} จาก {pm.total} แผน
          </p>
        </Card>
        <Card className="p-4">
          <p className="text-xs text-muted">รายการอะไหล่ที่เบิกแล้ว</p>
          <p className="text-2xl font-semibold">{parts.length}</p>
        </Card>
      </div>
      <Card className="p-4 mb-4">
        <h2 className="font-semibold mb-3">งาน BD ที่ยังไม่ปิด</h2>
        {bd.length === 0 ? (
          <p className="text-sm text-muted">ไม่มีงานเบรคดาวน์ค้าง</p>
        ) : (
          <ul className="text-sm divide-y divide-line">
            {bd.map((j) => (
              <li key={j.id} className="py-2 flex justify-between gap-3">
                <Link href={`/job-requests/${j.id}`} className="hover:underline">
                  {j.number} · {j.asset_code} · {j.symptom}
                </Link>
                <Badge tone={statusTone(j.status)}>{j.status}</Badge>
              </li>
            ))}
          </ul>
        )}
      </Card>
      <Card className="p-4 mb-4">
        <h2 className="font-semibold mb-3">ความครบกำหนด PM</h2>
        <div className="table-wrap">
          <table className="data">
            <thead>
              <tr>
                <th>รถ</th>
                <th>แผน</th>
                <th>ครบกำหนด</th>
                <th>สถานะ</th>
              </tr>
            </thead>
            <tbody>
              {pm.rows.map((r) => (
                <tr key={r.id}>
                  <td>{r.asset_code}</td>
                  <td>{r.title}</td>
                  <td>{formatThaiDate(r.next_due_at)}</td>
                  <td>
                    <Badge tone={r.next_due_at < new Date().toISOString().slice(0, 10) ? "danger" : "ok"}>
                      {r.next_due_at < new Date().toISOString().slice(0, 10) ? "เลยกำหนด" : "ตามแผน"}
                    </Badge>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
      <Card className="p-4">
        <h2 className="font-semibold mb-3">อะไหล่ที่ใช้ในใบงาน</h2>
        {parts.length === 0 ? (
          <p className="text-sm text-muted">ยังไม่มีการเบิก</p>
        ) : (
          <div className="table-wrap">
            <table className="data">
              <thead>
                <tr>
                  <th>อะไหล่</th>
                  <th>ใบงาน</th>
                  <th>รถ</th>
                  <th>จำนวน</th>
                  <th>มูลค่า</th>
                </tr>
              </thead>
              <tbody>
                {parts.map((p, i) => (
                  <tr key={`${p.wo_number}-${p.part_code}-${i}`}>
                    <td>
                      {p.part_code} {p.part_name}
                    </td>
                    <td>{p.wo_number}</td>
                    <td>{p.asset_code}</td>
                    <td>{p.qty}</td>
                    <td>{baht(p.total_price)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>
    </div>
  );
}
