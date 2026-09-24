import { Button, PageHeader } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { canManageParts } from "@/lib/permissions";
import { redirect } from "next/navigation";
import { PartForm } from "../part-form";

export default async function NewPartPage() {
  const user = await requireUser();
  if (!canManageParts(user.role)) redirect("/parts");
  return (
    <div>
      <PageHeader title="เพิ่มอะไหล่" actions={<Button href="/parts" variant="secondary">ยกเลิก</Button>} />
      <PartForm />
    </div>
  );
}
