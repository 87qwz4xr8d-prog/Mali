import { Button, PageHeader } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { listSites } from "@/lib/db/queries";
import { canManageAssets } from "@/lib/permissions";
import { redirect } from "next/navigation";
import { AssetForm } from "../asset-form";

export default async function NewAssetPage() {
  const user = await requireUser();
  if (!canManageAssets(user.role)) redirect("/assets");
  return (
    <div>
      <PageHeader
        title="รับรถเข้าเครื่องจักร"
        actions={
          <Button href="/assets" variant="secondary">
            ยกเลิก
          </Button>
        }
      />
      <AssetForm sites={listSites()} />
    </div>
  );
}
