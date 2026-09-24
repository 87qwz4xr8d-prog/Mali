import { Card, PageHeader } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { JOB_TYPES } from "@/lib/constants";
import { canManageMaster } from "@/lib/permissions";
import { redirect } from "next/navigation";

export default async function JobTypesPage() {
  const user = await requireUser();
  if (!canManageMaster(user.role)) redirect("/");
  return (
    <div>
      <PageHeader title="ประเภทงานซ่อม" description="ชุดรหัสงานของ Visual Maintenance — ไม่แก้ในหน้านี้" />
      <div className="grid gap-3">
        {JOB_TYPES.map((t) => (
          <Card key={t.code} className="p-4">
            <p className="font-semibold">{t.label}</p>
            <p className="text-sm text-muted">{t.hint}</p>
          </Card>
        ))}
      </div>
    </div>
  );
}
