import { Alert, Button, PageHeader } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { getTemplate, listAssets, listTemplateItems } from "@/lib/db/queries";
import { canCreateJobRequest } from "@/lib/permissions";
import { redirect } from "next/navigation";
import { JobRequestForm } from "../form";

export default async function NewJobRequestPage() {
  const user = await requireUser();
  if (!canCreateJobRequest(user.role)) redirect("/job-requests");
  const assets = listAssets();
  const template = getTemplate("request");
  const items = template ? listTemplateItems(template.id) : [];

  return (
    <div>
      <PageHeader
        title="สร้างใบแจ้งซ่อม"
        description="แจ้งอาการ ตรวจเช็คเบื้องต้น แล้วส่งให้วิศวกรมอบหมายทีม"
        actions={
          <Button href="/job-requests" variant="secondary">
            กลับรายการ
          </Button>
        }
      />
      {assets.length === 0 ? (
        <Alert>ยังไม่มีเครื่องจักรในระบบ — รับรถเข้าเครื่องจักก่อน</Alert>
      ) : (
        <JobRequestForm
          assets={assets.map((a) => ({
            id: a.id,
            label: `${a.code} · ${a.brand ?? ""} ${a.model ?? ""} · ${a.site_name ?? ""}`,
          }))}
          items={items}
          defaultPhone={user.phone ?? ""}
        />
      )}
    </div>
  );
}
