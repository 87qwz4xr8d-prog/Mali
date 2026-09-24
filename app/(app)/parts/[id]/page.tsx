import { Badge, Button, Card, PageHeader } from "@/components/ui";
import { receivePartAction } from "@/lib/actions";
import { requireUser } from "@/lib/auth";
import { getPart } from "@/lib/db/queries";
import { baht } from "@/lib/format";
import { canManageParts } from "@/lib/permissions";
import { notFound } from "next/navigation";

export default async function PartDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const user = await requireUser();
  const { id } = await params;
  const part = getPart(Number(id));
  if (!part) notFound();
  const low = part.qty <= part.min_qty;

  return (
    <div>
      <PageHeader
        title={part.code}
        description={part.name}
        actions={
          canManageParts(user.role) ? (
            <Button href={`/parts/${part.id}/edit`} variant="secondary">
              แก้ไข
            </Button>
          ) : null
        }
      />
      {low ? <Badge tone="danger">ต่ำกว่าขั้นต่ำ — ควรสั่งซื้อ {part.reorder_qty} {part.unit}</Badge> : null}
      <Card className="p-4 mt-4">
        <dl className="grid sm:grid-cols-3 gap-3 text-sm">
          <div>
            <dt className="text-muted">พาสโค้ด</dt>
            <dd>{part.oem_code || "—"}</dd>
          </div>
          <div>
            <dt className="text-muted">คงเหลือ</dt>
            <dd>
              {part.qty} {part.unit}
            </dd>
          </div>
          <div>
            <dt className="text-muted">ต้นทุน</dt>
            <dd>{baht(part.unit_cost)}</dd>
          </div>
          <div>
            <dt className="text-muted">ขั้นต่ำ / สั่ง / สูงสุด</dt>
            <dd>
              {part.min_qty} / {part.reorder_qty} / {part.max_qty}
            </dd>
          </div>
          <div>
            <dt className="text-muted">ที่เก็บ</dt>
            <dd>{part.location || "—"}</dd>
          </div>
          <div>
            <dt className="text-muted">Lead time</dt>
            <dd>{part.lead_time || "—"}</dd>
          </div>
          <div className="sm:col-span-3">
            <dt className="text-muted">ใช้กับ</dt>
            <dd>{part.used_on || "—"}</dd>
          </div>
        </dl>
      </Card>
      {canManageParts(user.role) ? (
        <Card className="p-4 mt-4">
          <h2 className="font-semibold mb-2">รับเข้าคลัง</h2>
          <form action={receivePartAction} className="flex flex-col sm:flex-row gap-2">
            <input type="hidden" name="part_id" value={part.id} />
            <input name="qty" type="number" min={1} defaultValue={part.reorder_qty || 1} className="rounded-lg border border-line px-3 py-2 text-sm" />
            <input name="note" placeholder="ใบรับ / ผู้ขาย" className="flex-1 rounded-lg border border-line px-3 py-2 text-sm" />
            <Button type="submit" variant="secondary">
              รับเข้า
            </Button>
          </form>
        </Card>
      ) : null}
    </div>
  );
}
