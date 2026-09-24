import { Badge, Button, Empty, PageHeader } from "@/components/ui";
import { generatePmJobAction } from "@/lib/actions";
import { requireUser } from "@/lib/auth";
import { listPmSchedules } from "@/lib/db/queries";
import { daysUntil, formatThaiDate } from "@/lib/format";
import Link from "next/link";

export default async function PmListPage({
  searchParams,
}: {
  searchParams: Promise<{ due?: string; q?: string }>;
}) {
  await requireUser();
  const sp = await searchParams;
  const due = sp.due === "overdue" || sp.due === "upcoming" ? sp.due : "all";
  const rows = listPmSchedules({ due, q: sp.q });

  return (
    <div>
      <PageHeader
        title="แผน PM"
        description="บำรุงรักษาเชิงป้องกันรายเดือนต่อคัน — สร้างใบแจ้งซ่อมประเภท PM เมื่อถึงกำหนด"
      />
      <form className="mb-4 flex flex-col sm:flex-row gap-2">
        <input name="q" defaultValue={sp.q} placeholder="รหัสรถ / ชื่อแผน" className="flex-1 rounded-lg border border-line bg-white px-3 py-2 text-sm" />
        <select name="due" defaultValue={due} className="rounded-lg border border-line bg-white px-3 py-2 text-sm">
          <option value="all">ทุกแผน</option>
          <option value="overdue">เลยกำหนด</option>
          <option value="upcoming">ยังไม่ถึง</option>
        </select>
        <Button type="submit" variant="secondary">
          กรอง
        </Button>
      </form>
      {rows.length === 0 ? (
        <Empty title="ไม่พบแผน PM" description="รับรถเข้าเครื่องจักรแล้วระบบจะสร้างแผนรายเดือนให้อัตโนมัติ" />
      ) : (
        <div className="table-wrap rounded-xl border border-line bg-card">
          <table className="data">
            <thead>
              <tr>
                <th>รถ</th>
                <th>แผน</th>
                <th>รอบ (วัน)</th>
                <th>ทำล่าสุด</th>
                <th>ครบกำหนด</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => {
                const d = daysUntil(r.next_due_at);
                return (
                  <tr key={r.id}>
                    <td>
                      <Link href={`/pm/${r.id}`} className="font-medium hover:underline">
                        {r.asset_code}
                      </Link>
                      <div className="text-xs text-muted">{r.asset_type}</div>
                    </td>
                    <td>{r.title}</td>
                    <td>{r.frequency_days}</td>
                    <td>{formatThaiDate(r.last_done_at)}</td>
                    <td>
                      {formatThaiDate(r.next_due_at)}{" "}
                      <Badge tone={d < 0 ? "danger" : d <= 7 ? "warn" : "ok"}>
                        {d < 0 ? `เลย ${-d} วัน` : `อีก ${d} วัน`}
                      </Badge>
                    </td>
                    <td>
                      <form action={generatePmJobAction}>
                        <input type="hidden" name="pm_id" value={r.id} />
                        <button className="text-sm text-accent hover:underline">สร้างงาน PM</button>
                      </form>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
