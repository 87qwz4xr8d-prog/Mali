import { Badge, Button, Card, PageHeader, statusTone } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { dashboardStats, listJobRequests, listParts, listPmSchedules, listWorkOrders } from "@/lib/db/queries";
import { baht, daysUntil, formatThaiDate } from "@/lib/format";
import Link from "next/link";

export default async function DashboardPage() {
  const user = await requireUser();
  const stats = dashboardStats();
  const openJobs = listJobRequests({ openOnly: true }).slice(0, 6);
  const openWo = listWorkOrders({ openOnly: true }).slice(0, 5);
  const pmDue = listPmSchedules({ due: "overdue" }).slice(0, 5);
  const lowParts = listParts({ belowMin: true }).slice(0, 5);

  const tiles = [
    { label: "ใบแจ้งซ่อมที่ยังไม่ปิด", value: stats.openJr, href: "/job-requests" },
    { label: "ใบงานซ่อมระหว่างทำ", value: stats.openWo, href: "/work-orders" },
    { label: "งาน BD เปิดอยู่", value: stats.bd, href: "/job-requests?jobType=BD" },
    { label: "PM ครบกำหนด / เลยกำหนด", value: stats.pmDue, href: "/pm?due=overdue" },
    { label: "อะไหล่ต่ำกว่าขั้นต่ำ", value: stats.partsLow, href: "/parts?belowMin=1" },
    { label: "รถพร้อมใช้งาน", value: `${stats.ready}/${stats.totalAssets}`, href: "/assets" },
  ];

  return (
    <div>
      <PageHeader
        title={`สวัสดี คุณ${user.name_th.split(" ")[0]}`}
        description="ภาพรวมอู่รถกระเช้าไฟฟ้า — งานค้าง PM และอะไหล่ที่ต้องสั่ง"
        actions={
          <>
            <Button href="/job-requests/new">ใบแจ้งซ่อมใหม่</Button>
            <Button href="/assets/new" variant="secondary">
              รับรถเข้าเครื่องจักร
            </Button>
          </>
        }
      />
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 mb-6">
        {tiles.map((t) => (
          <Link key={t.label} href={t.href}>
            <Card className="p-4 hover:border-accent/40 transition">
              <p className="text-xs text-muted">{t.label}</p>
              <p className="mt-1 text-2xl font-semibold tabular-nums">{t.value}</p>
            </Card>
          </Link>
        ))}
      </div>
      <div className="grid gap-4 lg:grid-cols-2">
        <Card className="p-4">
          <h2 className="font-semibold mb-3">ใบแจ้งซ่อมเปิดอยู่</h2>
          {openJobs.length === 0 ? (
            <p className="text-sm text-muted">ไม่มีใบแจ้งซ่อมค้าง</p>
          ) : (
            <ul className="divide-y divide-line">
              {openJobs.map((j) => (
                <li key={j.id} className="py-2 flex items-start justify-between gap-3">
                  <div>
                    <Link href={`/job-requests/${j.id}`} className="font-medium hover:underline">
                      {j.number} · {j.asset_code}
                    </Link>
                    <p className="text-xs text-muted line-clamp-2">{j.symptom}</p>
                  </div>
                  <Badge tone={statusTone(j.status)}>{j.status}</Badge>
                </li>
              ))}
            </ul>
          )}
        </Card>
        <Card className="p-4">
          <h2 className="font-semibold mb-3">ใบงานซ่อม</h2>
          {openWo.length === 0 ? (
            <p className="text-sm text-muted">ไม่มีใบงานระหว่างทำ</p>
          ) : (
            <ul className="divide-y divide-line">
              {openWo.map((w) => (
                <li key={w.id} className="py-2 flex items-start justify-between gap-3">
                  <div>
                    <Link href={`/work-orders/${w.id}`} className="font-medium hover:underline">
                      {w.number} · {w.asset_code}
                    </Link>
                    <p className="text-xs text-muted">
                      {w.technician_name || "ยังไม่มอบหมายช่าง"} · {w.job_type}
                    </p>
                  </div>
                  <Badge tone={statusTone(w.status)}>{w.status}</Badge>
                </li>
              ))}
            </ul>
          )}
        </Card>
        <Card className="p-4">
          <h2 className="font-semibold mb-3">PM เลยกำหนด</h2>
          {pmDue.length === 0 ? (
            <p className="text-sm text-muted">ไม่มีแผนที่เลยกำหนด</p>
          ) : (
            <ul className="divide-y divide-line">
              {pmDue.map((p) => (
                <li key={p.id} className="py-2 flex justify-between gap-3">
                  <Link href={`/pm/${p.id}`} className="font-medium hover:underline">
                    {p.asset_code} · {p.title}
                  </Link>
                  <span className="text-xs text-danger">เลย {Math.abs(daysUntil(p.next_due_at))} วัน</span>
                </li>
              ))}
            </ul>
          )}
        </Card>
        <Card className="p-4">
          <h2 className="font-semibold mb-3">อะไหล่ต้องสั่ง</h2>
          {lowParts.length === 0 ? (
            <p className="text-sm text-muted">สต็อกอยู่ในเกณฑ์</p>
          ) : (
            <ul className="divide-y divide-line">
              {lowParts.map((p) => (
                <li key={p.id} className="py-2 flex justify-between gap-3">
                  <Link href={`/parts/${p.id}`} className="font-medium hover:underline">
                    {p.code} · {p.name}
                  </Link>
                  <span className="text-xs text-danger">
                    เหลือ {p.qty} / ขั้นต่ำ {p.min_qty} · {baht(p.unit_cost)}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </Card>
      </div>
      <p className="mt-6 text-xs text-muted">วันที่ในระบบแสดงเป็น วว/ดด/พ.ศ. เช่น วันนี้ {formatThaiDate(new Date().toISOString())}</p>
    </div>
  );
}
