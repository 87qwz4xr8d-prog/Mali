import { Button, PageHeader } from "@/components/ui";
import { upsertSiteAction } from "@/lib/actions";
import { requireUser } from "@/lib/auth";
import { listSites } from "@/lib/db/queries";
import { canManageMaster } from "@/lib/permissions";
import { redirect } from "next/navigation";

export default async function SitesPage() {
  const user = await requireUser();
  if (!canManageMaster(user.role)) redirect("/");
  const rows = listSites();
  return (
    <div>
      <PageHeader title="หน่วยงาน" description="ชื่อบริษัท · ลูกค้า · หน้างาน" />
      <div className="table-wrap rounded-xl border border-line bg-card mb-6">
        <table className="data">
          <thead>
            <tr>
              <th>บริษัท</th>
              <th>ลูกค้า</th>
              <th>หน้างาน</th>
              <th>หมายเหตุ</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.id}>
                <td>{r.company_name}</td>
                <td>{r.customer_name}</td>
                <td>{r.site_name}</td>
                <td className="text-sm text-muted">{r.notes}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <form action={upsertSiteAction} className="grid gap-3 sm:grid-cols-2 rounded-xl border border-line bg-card p-4">
        <h2 className="sm:col-span-2 font-semibold">เพิ่มหน่วยงาน</h2>
        <input name="company_name" required placeholder="ชื่อบริษัท *" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="customer_name" placeholder="ชื่อบริษัทลูกค้า" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="site_name" placeholder="หน้างาน" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="notes" placeholder="หมายเหตุ" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <div className="sm:col-span-2">
          <Button type="submit">บันทึก</Button>
        </div>
      </form>
    </div>
  );
}
