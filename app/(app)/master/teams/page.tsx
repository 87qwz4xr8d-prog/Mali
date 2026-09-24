import { Button, PageHeader } from "@/components/ui";
import { upsertTeamAction } from "@/lib/actions";
import { requireUser } from "@/lib/auth";
import { listTeams } from "@/lib/db/queries";
import { canManageMaster } from "@/lib/permissions";
import { redirect } from "next/navigation";

export default async function TeamsPage() {
  const user = await requireUser();
  if (!canManageMaster(user.role)) redirect("/");
  const rows = listTeams();
  return (
    <div>
      <PageHeader title="ทีมซ่อม" />
      <div className="table-wrap rounded-xl border border-line bg-card mb-6">
        <table className="data">
          <thead>
            <tr>
              <th>ทีม</th>
              <th>กลุ่มงาน</th>
              <th>ขอบเขต</th>
              <th>อีเมล</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.id}>
                <td>{r.name}</td>
                <td>{r.repair_group}</td>
                <td>{r.scope}</td>
                <td>{r.email}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <form action={upsertTeamAction} className="grid gap-3 sm:grid-cols-2 rounded-xl border border-line bg-card p-4">
        <h2 className="sm:col-span-2 font-semibold">เพิ่มทีม</h2>
        <input name="name" required placeholder="รายชื่อทีม *" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="repair_group" placeholder="ทีมซ่อม/กลุ่มการซ่อม" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="scope" placeholder="ขอบเขตงาน" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="email" placeholder="อีเมล" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="notes" placeholder="หมายเหตุ" className="sm:col-span-2 rounded-lg border border-line px-3 py-2 text-sm" />
        <Button type="submit">บันทึก</Button>
      </form>
    </div>
  );
}
