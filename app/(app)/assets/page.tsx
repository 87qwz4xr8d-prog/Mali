import { Badge, Button, Empty, PageHeader, statusTone } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { LIFT_TYPES, LIFT_TYPE_TH, OPERATIONAL_STATUSES } from "@/lib/constants";
import { listAssets, listSites } from "@/lib/db/queries";
import { canManageAssets } from "@/lib/permissions";
import Link from "next/link";

export default async function AssetsPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; type?: string; status?: string; siteId?: string }>;
}) {
  const user = await requireUser();
  const sp = await searchParams;
  const sites = listSites();
  const rows = listAssets({
    q: sp.q,
    type: sp.type,
    status: sp.status,
    siteId: sp.siteId ? Number(sp.siteId) : undefined,
  });

  return (
    <div>
      <PageHeader
        title="เครื่องจักร"
        description="ทะเบียนรถกระเช้า — รหัส YB / YS / YP ตามหน้างาน"
        actions={canManageAssets(user.role) ? <Button href="/assets/new">รับรถเข้าเครื่องจักร</Button> : null}
      />
      <form className="mb-4 grid gap-2 sm:grid-cols-4">
        <input name="q" defaultValue={sp.q} placeholder="รหัส / รุ่น / ซีเรียล" className="rounded-lg border border-line bg-white px-3 py-2 text-sm sm:col-span-2" />
        <select name="type" defaultValue={sp.type || ""} className="rounded-lg border border-line bg-white px-3 py-2 text-sm">
          <option value="">ทุกประเภท</option>
          {LIFT_TYPES.map((t) => (
            <option key={t} value={t}>
              {LIFT_TYPE_TH[t]}
            </option>
          ))}
        </select>
        <select name="status" defaultValue={sp.status || ""} className="rounded-lg border border-line bg-white px-3 py-2 text-sm">
          <option value="">ทุกสถานะรถ</option>
          {OPERATIONAL_STATUSES.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
        <select name="siteId" defaultValue={sp.siteId || ""} className="rounded-lg border border-line bg-white px-3 py-2 text-sm sm:col-span-3">
          <option value="">ทุกหน่วยงาน</option>
          {sites.map((s) => (
            <option key={s.id} value={s.id}>
              {s.company_name} · {s.site_name}
            </option>
          ))}
        </select>
        <Button type="submit" variant="secondary">
          กรอง
        </Button>
      </form>
      {rows.length === 0 ? (
        <Empty title="ไม่พบเครื่องจักร" description="ลองล้างตัวกรอง หรือรับรถเข้าใหม่" />
      ) : (
        <div className="table-wrap rounded-xl border border-line bg-card">
          <table className="data">
            <thead>
              <tr>
                <th>รหัส</th>
                <th>ประเภท</th>
                <th>ยี่ห้อ / รุ่น</th>
                <th>ซีเรียล</th>
                <th>หน้างาน</th>
                <th>ชม.</th>
                <th>สถานะ</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.id}>
                  <td>
                    <Link href={`/assets/${r.id}`} className="font-medium hover:underline">
                      {r.code}
                    </Link>
                  </td>
                  <td>{LIFT_TYPE_TH[r.type as keyof typeof LIFT_TYPE_TH] ?? r.type}</td>
                  <td>
                    {r.brand} {r.model}
                  </td>
                  <td className="text-xs">{r.serial}</td>
                  <td>
                    {r.site_name}
                    <div className="text-xs text-muted">{r.customer_name}</div>
                  </td>
                  <td>{r.hour_meter}</td>
                  <td>
                    <Badge tone={statusTone(r.operational_status)}>{r.operational_status}</Badge>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
