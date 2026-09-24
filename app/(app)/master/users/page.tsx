import { Badge, Button, PageHeader } from "@/components/ui";
import { upsertUserAction } from "@/lib/actions";
import { requireUser } from "@/lib/auth";
import { ROLE_LABEL, ROLES } from "@/lib/constants";
import { listSites, listTeams, listUsers } from "@/lib/db/queries";
import { canManageUsers } from "@/lib/permissions";
import { redirect } from "next/navigation";

export default async function UsersPage() {
  const user = await requireUser();
  if (!canManageUsers(user.role)) redirect("/");
  const rows = listUsers();
  const teams = listTeams();
  const sites = listSites();
  return (
    <div>
      <PageHeader title="ผู้ใช้และช่าง" description="Requestor · Technician · Engineer Review · Manager Approve · Admin · STORE" />
      <div className="table-wrap rounded-xl border border-line bg-card mb-6">
        <table className="data">
          <thead>
            <tr>
              <th>รหัส</th>
              <th>ชื่อ</th>
              <th>สิทธิ์</th>
              <th>ตำแหน่ง</th>
              <th>โทร</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.id}>
                <td>{r.employee_code || r.username}</td>
                <td>
                  {r.name_th}
                  <div className="text-xs text-muted">{r.name_en}</div>
                </td>
                <td>
                  <Badge>{ROLE_LABEL[r.role]}</Badge>
                </td>
                <td>{r.position}</td>
                <td>{r.phone}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <form action={upsertUserAction} className="grid gap-3 sm:grid-cols-2 rounded-xl border border-line bg-card p-4">
        <h2 className="sm:col-span-2 font-semibold">เพิ่มผู้ใช้</h2>
        <input name="username" required placeholder="ชื่อเข้าสู่ระบบ *" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="password" required type="password" placeholder="รหัสผ่าน *" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="name_th" required placeholder="ชื่อ - สกุล (ไทย) *" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="name_en" placeholder="English name" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <select name="role" className="rounded-lg border border-line px-3 py-2 text-sm bg-white">
          {ROLES.map((r) => (
            <option key={r} value={r}>
              {ROLE_LABEL[r]}
            </option>
          ))}
        </select>
        <input name="employee_code" placeholder="รหัสพนักงาน" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="position" placeholder="ตำแหน่ง" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="department" placeholder="หน่วยงาน" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="phone" placeholder="เบอร์โทร" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="email" placeholder="อีเมล" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <input name="started_at" type="date" className="rounded-lg border border-line px-3 py-2 text-sm" />
        <select name="team_id" className="rounded-lg border border-line px-3 py-2 text-sm bg-white">
          <option value="">ทีม (ถ้ามี)</option>
          {teams.map((t) => (
            <option key={t.id} value={t.id}>
              {t.name}
            </option>
          ))}
        </select>
        <select name="site_id" className="rounded-lg border border-line px-3 py-2 text-sm bg-white">
          <option value="">เห็นทุกหน้างาน</option>
          {sites.map((s) => (
            <option key={s.id} value={s.id}>
              {s.site_name}
            </option>
          ))}
        </select>
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" name="active" defaultChecked />
          ใช้งาน
        </label>
        <div className="sm:col-span-2">
          <Button type="submit">บันทึกผู้ใช้</Button>
        </div>
      </form>
    </div>
  );
}
