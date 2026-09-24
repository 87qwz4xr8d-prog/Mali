import { Button, PageHeader } from "@/components/ui";
import { updateJobRequestAction } from "@/lib/actions";
import { requireUser } from "@/lib/auth";
import { PRIORITIES } from "@/lib/constants";
import { getJobRequest } from "@/lib/db/queries";
import { canEditJobRequest } from "@/lib/permissions";
import { notFound, redirect } from "next/navigation";
import { EditJobRequestForm } from "./edit-form";

export default async function EditJobRequestPage({ params }: { params: Promise<{ id: string }> }) {
  const user = await requireUser();
  const { id } = await params;
  const jr = getJobRequest(Number(id));
  if (!jr) notFound();
  if (!canEditJobRequest(user.role, jr.status === "ปิดจบงาน")) redirect(`/job-requests/${jr.id}`);

  return (
    <div>
      <PageHeader
        title={`แก้ไข ${jr.number}`}
        actions={
          <Button href={`/job-requests/${jr.id}`} variant="secondary">
            ยกเลิก
          </Button>
        }
      />
      <EditJobRequestForm jr={jr} />
    </div>
  );
}
