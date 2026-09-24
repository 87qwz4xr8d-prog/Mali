import { Button, PageHeader } from "@/components/ui";
import { upsertVendorAction } from "@/lib/actions";
import { requireUser } from "@/lib/auth";
import { listVendors } from "@/lib/db/queries";
import { canManageMaster } from "@/lib/permissions";
import { redirect } from "next/navigation";

export default async function VendorsPage() {
  const user = await requireUser();
  if (!canManageMaster(user.role)) redirect("/");
  const rows = listVendors();
  return (
    <div>
      <PageHeader title="ร้านค้า" />
      <div className="table-wrap rounded-xl border border-line bg-card mb-6">
        <table className="data">
          <thead>
            <tr>
              <th>รหัส</th>
              <th>บริษัท</th>
              <th>ผู้ติดต่อ</th>
              <th>โทร</th>
              <th>อีเมล</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.id}>
                <td>{r.code}</td>
                <td>
                  {r.company_name}
                  <div className="text-xs text-muted">{r.description}</div>
                </td>
                <td>{r.contact}</td>
                <td>{r.phone}</td>
                <td>{r.email}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <form action={upsertVendorAction} className="grid gap-3 sm:grid-cols-2 rounded-xl border border-line bg-card p-4">
        <h2 className="sm:col-span-2 font-semibold">เพิ่มร้านค้า</h2>
        <input name="code" required placeholder="รหัส *" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="company_name" required placeholder="ชื่อบริษัท *" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="description" placeholder="รายละเอียด" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="contact" placeholder="ผู้ติดต่อ" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="phone" placeholder="เบอร์โทรศัพท์" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="email" placeholder="อีเมล" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="address" placeholder="ที่อยู่" className="sm:col-span-2 rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="notes" placeholder="หมายเหตุ" className="sm:col-span-2 rounded-lg border border-line px-3 py-2 text-sm" />
        <Button type="submit">บันทึก</Button>
      </form>
    </div>
  );
}
