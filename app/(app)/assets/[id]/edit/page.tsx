import { Button, PageHeader } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { getAsset, listSites } from "@/lib/db/queries";
import { canManageAssets } from "@/lib/permissions";
import { notFound, redirect } from "next/navigation";
import { AssetForm } from "../../asset-form";

export default async function EditAssetPage({ params }: { params: Promise<{ id: string }> }) {
  const user = await requireUser();
  if (!canManageAssets(user.role)) redirect("/assets");
  const { id } = await params;
  const asset = getAsset(Number(id));
  if (!asset) notFound();
  return (
    <div>
      <PageHeader
        title={`แก้ไข ${asset.code}`}
        actions={
          <Button href={`/assets/${asset.id}`} variant="secondary">
            ยกเลิก
          </Button>
        }
      />
      <AssetForm asset={asset} sites={listSites()} />
    </div>
  );
}
