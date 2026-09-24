import { Badge, Button, Card, PageHeader, statusTone } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { LIFT_TYPE_TH } from "@/lib/constants";
import { assetHistory, getAsset } from "@/lib/db/queries";
import { baht, formatThaiDate } from "@/lib/format";
import { canManageAssets } from "@/lib/permissions";
import Link from "next/link";
import { notFound } from "next/navigation";

export default async function AssetDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const user = await requireUser();
  const { id } = await params;
  const asset = getAsset(Number(id));
  if (!asset) notFound();
  const history = assetHistory(asset.id);

  return (
    <div>
      <PageHeader
        title={asset.code}
        description={`${asset.brand ?? ""} ${asset.model ?? ""} · ${asset.serial ?? ""}`}
        actions={
          <>
            {canManageAssets(user.role) ? (
              <Button href={`/assets/${asset.id}/edit`} variant="secondary">
                แก้ไข
              </Button>
            ) : null}
            <Button href={`/job-requests/new`} variant="primary">
              แจ้งซ่อมคันนี้
            </Button>
          </>
        }
      />
      <div className="flex flex-wrap gap-2 mb-4">
        <Badge>{LIFT_TYPE_TH[asset.type as keyof typeof LIFT_TYPE_TH] ?? asset.type}</Badge>
        <Badge tone={statusTone(asset.operational_status)}>{asset.operational_status}</Badge>
      </div>
      <Card className="p-4 mb-4">
        <dl className="grid sm:grid-cols-3 gap-3 text-sm">
          <div>
            <dt className="text-muted">บริษัท</dt>
            <dd>{asset.company_name}</dd>
          </div>
          <div>
            <dt className="text-muted">หน้างาน</dt>
            <dd>{asset.site_name}</dd>
          </div>
          <div>
            <dt className="text-muted">ลูกค้า</dt>
            <dd>{asset.customer_name || "—"}</dd>
          </div>
          <div>
            <dt className="text-muted">เลขทรัพย์สิน</dt>
            <dd>{asset.asset_number || "—"}</dd>
          </div>
          <div>
            <dt className="text-muted">รับเข้า</dt>
            <dd>{formatThaiDate(asset.received_at)}</dd>
          </div>
          <div>
            <dt className="text-muted">หมดประกัน</dt>
            <dd>{formatThaiDate(asset.warranty_until)}</dd>
          </div>
          <div>
            <dt className="text-muted">ราคา</dt>
            <dd>{baht(asset.price)}</dd>
          </div>
          <div>
            <dt className="text-muted">ชั่วโมงเครื่อง</dt>
            <dd>{asset.hour_meter}</dd>
          </div>
          <div>
            <dt className="text-muted">ผู้ติดต่อ</dt>
            <dd>
              {asset.contact_name || "—"} {asset.contact_phone || ""}
            </dd>
          </div>
          <div className="sm:col-span-3">
            <dt className="text-muted">รายละเอียด</dt>
            <dd>{asset.description || "—"}</dd>
          </div>
        </dl>
      </Card>
      <div className="grid gap-4 lg:grid-cols-2">
        <Card className="p-4">
          <h2 className="font-semibold mb-2">ประวัติใบแจ้งซ่อม / ใบงาน</h2>
          {history.requests.length === 0 ? (
            <p className="text-sm text-muted">ยังไม่มีประวัติซ่อม</p>
          ) : (
            <ul className="text-sm divide-y divide-line">
              {history.requests.map((r) => (
                <li key={r.id} className="py-2">
                  <Link href={`/job-requests/${r.id}`} className="font-medium hover:underline">
                    {r.number}
                  </Link>{" "}
                  {r.job_type} · {r.status}
                  {r.work_order_number ? ` · ${r.work_order_number}` : ""}
                </li>
              ))}
            </ul>
          )}
        </Card>
        <Card className="p-4">
          <h2 className="font-semibold mb-2">แผน PM และเช่า</h2>
          {history.pm.map((p) => (
            <p key={p.id} className="text-sm">
              <Link href={`/pm/${p.id}`} className="hover:underline">
                {p.title}
              </Link>{" "}
              ครบ {formatThaiDate(p.next_due_at)}
            </p>
          ))}
          {history.rentals.map((r) => (
            <p key={r.id} className="text-sm mt-2">
              งานเช่า {r.job_no} · {r.customer_name} ({formatThaiDate(r.start_date)}–{formatThaiDate(r.end_date)})
            </p>
          ))}
        </Card>
      </div>
    </div>
  );
}
