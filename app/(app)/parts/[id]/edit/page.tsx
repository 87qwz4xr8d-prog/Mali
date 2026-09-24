import { Button, PageHeader } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { getPart } from "@/lib/db/queries";
import { canManageParts } from "@/lib/permissions";
import { notFound, redirect } from "next/navigation";
import { PartForm } from "../../part-form";

export default async function EditPartPage({ params }: { params: Promise<{ id: string }> }) {
  const user = await requireUser();
  if (!canManageParts(user.role)) redirect("/parts");
  const { id } = await params;
  const part = getPart(Number(id));
  if (!part) notFound();
  return (
    <div>
      <PageHeader title={`แก้ไข ${part.code}`} actions={<Button href={`/parts/${part.id}`} variant="secondary">ยกเลิก</Button>} />
      <PartForm part={part} />
    </div>
  );
}
