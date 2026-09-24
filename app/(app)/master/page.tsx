import { Card, PageHeader } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { canManageMaster } from "@/lib/permissions";
import Link from "next/link";
import { redirect } from "next/navigation";

const LINKS = [
  { href: "/master/sites", title: "หน่วยงาน", desc: "บริษัท ลูกค้า หน้างาน" },
  { href: "/master/teams", title: "ทีมซ่อม", desc: "Team A Service / Team B Maintenance" },
  { href: "/master/users", title: "ผู้ใช้และช่าง", desc: "สิทธิ์ 6 ระดับ + รหัสพนักงาน" },
  { href: "/master/vendors", title: "ร้านค้า", desc: "ผู้ขายอะไหล่ OEM / ท้องถิ่น" },
  { href: "/master/job-types", title: "ประเภทงานซ่อม", desc: "BD PM CM SV Drive" },
];

export default async function MasterPage() {
  const user = await requireUser();
  if (!canManageMaster(user.role)) redirect("/");
  return (
    <div>
      <PageHeader title="ข้อมูลหลัก" description="มาสเตอร์ที่ใบแจ้งซ่อมและใบงานอ้างอิง — ไม่ใช่หน้าคลัง FIFO" />
      <div className="grid gap-3 sm:grid-cols-2">
        {LINKS.map((l) => (
          <Link key={l.href} href={l.href}>
            <Card className="p-4 hover:border-accent/40 transition">
              <p className="font-semibold">{l.title}</p>
              <p className="text-sm text-muted">{l.desc}</p>
            </Card>
          </Link>
        ))}
      </div>
    </div>
  );
}
