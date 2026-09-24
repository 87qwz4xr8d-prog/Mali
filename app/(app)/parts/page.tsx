import { Badge, Button, Empty, PageHeader } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { PART_CATEGORIES } from "@/lib/constants";
import { listParts } from "@/lib/db/queries";
import { baht } from "@/lib/format";
import { canManageParts } from "@/lib/permissions";
import Link from "next/link";

export default async function PartsPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; belowMin?: string; category?: string }>;
}) {
  const user = await requireUser();
  const sp = await searchParams;
  const rows = listParts({
    q: sp.q,
    belowMin: sp.belowMin === "1",
    category: sp.category,
  });

  return (
    <div>
      <PageHeader
        title="อะไหล่"
        description="รหัส YTP · พาสโค้ด OEM · ขั้นต่ำ/ต้องสั่ง/สูงสุด"
        actions={canManageParts(user.role) ? <Button href="/parts/new">เพิ่มอะไหล่</Button> : null}
      />
      <form className="mb-4 flex flex-col sm:flex-row gap-2">
        <input name="q" defaultValue={sp.q} placeholder="รหัส / ชื่อ / พาสโค้ด" className="flex-1 rounded-lg border border-line bg-white px-3 py-2 text-sm" />
        <select name="category" defaultValue={sp.category || ""} className="rounded-lg border border-line bg-white px-3 py-2 text-sm">
          <option value="">ทุกหมวด</option>
          {PART_CATEGORIES.map((c) => (
            <option key={c.code} value={c.code}>
              {c.label}
            </option>
          ))}
        </select>
        <label className="flex items-center gap-2 text-sm px-2">
          <input type="checkbox" name="belowMin" value="1" defaultChecked={sp.belowMin === "1"} />
          ต่ำกว่าขั้นต่ำ
        </label>
        <Button type="submit" variant="secondary">
          กรอง
        </Button>
      </form>
      {rows.length === 0 ? (
        <Empty title="ไม่พบอะไหล่" />
      ) : (
        <div className="table-wrap rounded-xl border border-line bg-card">
          <table className="data">
            <thead>
              <tr>
                <th>รหัส</th>
                <th>รายการ</th>
                <th>พาสโค้ด</th>
                <th>คงเหลือ</th>
                <th>ขั้นต่ำ</th>
                <th>ที่เก็บ</th>
                <th>ต้นทุน</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((p) => (
                <tr key={p.id}>
                  <td>
                    <Link href={`/parts/${p.id}`} className="font-medium hover:underline">
                      {p.code}
                    </Link>
                  </td>
                  <td>
                    {p.name}
                    <div className="text-xs text-muted">{p.used_on}</div>
                  </td>
                  <td className="text-xs">{p.oem_code}</td>
                  <td>
                    {p.qty} {p.unit}{" "}
                    {p.qty <= p.min_qty ? <Badge tone="danger">ต้องสั่ง</Badge> : null}
                  </td>
                  <td>
                    {p.min_qty}/{p.reorder_qty}/{p.max_qty}
                  </td>
                  <td>{p.location}</td>
                  <td>{baht(p.unit_cost)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
