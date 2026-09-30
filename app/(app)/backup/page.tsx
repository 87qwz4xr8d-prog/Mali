import { Badge, Button, Card, PageHeader } from "@/components/ui";
import {
  createBackupAction,
  deleteBackupAction,
  pruneBackupsAction,
} from "@/lib/actions";
import { requireUser } from "@/lib/auth";
import { formatBytes, listBackups, readBackupStatus } from "@/lib/backup";
import { getBackupKeep, getBackupRoot, getDataDir } from "@/lib/db/paths";
import { formatThaiDateTime } from "@/lib/format";
import { canManageBackup } from "@/lib/permissions";
import { redirect } from "next/navigation";

export default async function BackupPage({
  searchParams,
}: {
  searchParams?: Promise<{ msg?: string; err?: string }>;
}) {
  const user = await requireUser();
  if (!canManageBackup(user.role)) redirect("/");
  const sp = (await searchParams) || {};
  const status = readBackupStatus();
  const rows = listBackups();
  const keep = getBackupKeep();

  return (
    <div>
      <PageHeader
        title="สำรองข้อมูลอัตโนมัติ"
        description="สำรอง SQLite และไฟล์อัปโหลดบน PC เซิร์ฟเวอร์ — รันมือจากหน้านี้ หรือตั้ง Auto ตามเวลาด้วย systemd / Task Scheduler"
      />

      {sp.msg ? (
        <p className="mb-4 rounded-lg border border-line bg-card px-3 py-2 text-sm text-ok">{sp.msg}</p>
      ) : null}
      {sp.err ? (
        <p className="mb-4 rounded-lg border border-danger/30 bg-card px-3 py-2 text-sm text-danger">
          {sp.err}
        </p>
      ) : null}

      <Card className="p-4 mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="font-semibold">สำรองมือตอนนี้</p>
          <p className="text-sm text-muted">สร้างชุดสำรอง SQLite + uploads ทันทีโดยไม่ต้องหยุดบริการ</p>
        </div>
        <form action={createBackupAction}>
          <Button type="submit">สำรองตอนนี้</Button>
        </form>
      </Card>

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <Card className="p-4">
          <p className="text-xs text-muted">สำรองล่าสุด</p>
          <p className="text-lg font-semibold mt-1">
            {status.last_ok_at ? formatThaiDateTime(status.last_ok_at) : "—"}
          </p>
          <p className="text-xs text-muted mt-1">
            {status.last_mode === "auto" ? "Auto" : status.last_mode === "manual" ? "มือ" : ""}
            {status.last_stamp ? ` · ${status.last_stamp}` : ""}
          </p>
        </Card>
        <Card className="p-4">
          <p className="text-xs text-muted">จำนวนชุดที่เก็บ</p>
          <p className="text-lg font-semibold mt-1">
            {rows.length} / {keep}
          </p>
          <p className="text-xs text-muted mt-1">เก็บล่าสุด {keep} ชุด (MALI_BACKUP_KEEP)</p>
        </Card>
        <Card className="p-4">
          <p className="text-xs text-muted">โฟลเดอร์ข้อมูล</p>
          <p className="text-sm font-medium mt-1 break-all">{getDataDir()}</p>
        </Card>
        <Card className="p-4">
          <p className="text-xs text-muted">โฟลเดอร์สำรอง</p>
          <p className="text-sm font-medium mt-1 break-all">{getBackupRoot()}</p>
        </Card>
      </div>

      {status.last_error ? (
        <Card className="p-4 mb-6 border-danger/40">
          <p className="text-sm font-medium text-danger">ข้อผิดพลาดล่าสุด</p>
          <p className="text-sm text-muted mt-1">{status.last_error}</p>
        </Card>
      ) : null}

      <Card className="p-4 mb-6">
        <h2 className="font-semibold mb-2">ตั้ง Auto บน PC เซิร์ฟเวอร์</h2>
        <p className="text-sm text-muted mb-3">
          สคริปต์จะสำรองทุกวันเวลา 02:00 และตัดชุดเก่าให้อัตโนมัติ คัดลอกชุดสำรองไป USB/NAS
          เพิ่มได้ด้วย <code className="text-xs">MALI_BACKUP_MIRROR</code>
        </p>
        <div className="grid gap-3 lg:grid-cols-2 text-sm">
          <div className="rounded-lg border border-line bg-paper/60 p-3">
            <p className="font-medium mb-1">Linux (systemd timer)</p>
            <pre className="text-xs overflow-x-auto whitespace-pre-wrap text-muted">{`sudo ./deploy/install-auto-backup.sh`}</pre>
          </div>
          <div className="rounded-lg border border-line bg-paper/60 p-3">
            <p className="font-medium mb-1">Windows (Task Scheduler)</p>
            <pre className="text-xs overflow-x-auto whitespace-pre-wrap text-muted">{`powershell -ExecutionPolicy Bypass -File .\\deploy\\install-auto-backup.ps1`}</pre>
          </div>
        </div>
        <p className="text-xs text-muted mt-3">
          กู้คืน: <code>sudo ./deploy/restore.sh &lt;stamp&gt;</code> — หยุดบริการมาลีก่อนกู้คืนเสมอ
        </p>
      </Card>

      <div className="flex flex-wrap items-center justify-between gap-2 mb-3">
        <h2 className="font-semibold">ชุดสำรอง</h2>
        <form action={pruneBackupsAction}>
          <Button type="submit" variant="secondary">
            ตัดชุดเกิน {keep}
          </Button>
        </form>
      </div>

      <div className="table-wrap rounded-xl border border-line bg-card">
        <table className="data">
          <thead>
            <tr>
              <th>รหัสชุด</th>
              <th>เวลา</th>
              <th>โหมด</th>
              <th>ขนาด DB</th>
              <th>uploads</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 ? (
              <tr>
                <td colSpan={6} className="text-muted text-sm">
                  ยังไม่มีชุดสำรอง — กด «สำรองตอนนี้» หรือติดตั้ง Auto บนเซิร์ฟเวอร์
                </td>
              </tr>
            ) : (
              rows.map((r) => (
                <tr key={r.stamp}>
                  <td className="font-medium">{r.stamp}</td>
                  <td>{formatThaiDateTime(r.created_at)}</td>
                  <td>
                    <Badge tone={r.mode === "auto" ? "ok" : "warn"}>
                      {r.mode === "auto" ? "Auto" : "มือ"}
                    </Badge>
                  </td>
                  <td>{formatBytes(r.db_bytes)}</td>
                  <td>{r.has_uploads ? "มี" : "—"}</td>
                  <td>
                    <div className="flex flex-wrap gap-2 justify-end">
                      <Button href={`/api/backup/${r.stamp}/download`} variant="secondary">
                        ดาวน์โหลด DB
                      </Button>
                      <form action={deleteBackupAction}>
                        <input type="hidden" name="stamp" value={r.stamp} />
                        <Button type="submit" variant="danger">
                          ลบ
                        </Button>
                      </form>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
