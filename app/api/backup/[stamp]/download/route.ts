import { existsSync, readFileSync, statSync } from "node:fs";
import path from "node:path";
import { NextRequest, NextResponse } from "next/server";
import { getSessionUser } from "@/lib/auth";
import { getBackupDir } from "@/lib/backup";
import { canManageBackup } from "@/lib/permissions";

export async function GET(
  _request: NextRequest,
  context: { params: Promise<{ stamp: string }> },
) {
  const user = await getSessionUser();
  if (!user || !canManageBackup(user.role)) {
    return NextResponse.json({ error: "ไม่มีสิทธิ์" }, { status: 403 });
  }

  const { stamp } = await context.params;
  let dir: string;
  try {
    dir = getBackupDir(stamp);
  } catch {
    return NextResponse.json({ error: "รหัสชุดไม่ถูกต้อง" }, { status: 400 });
  }

  const file = path.join(dir, "mali.db");
  if (!existsSync(file)) {
    return NextResponse.json({ error: "ไม่พบไฟล์สำรอง" }, { status: 404 });
  }

  const buf = readFileSync(file);
  const size = statSync(file).size;
  return new NextResponse(buf, {
    status: 200,
    headers: {
      "Content-Type": "application/octet-stream",
      "Content-Length": String(size),
      "Content-Disposition": `attachment; filename="mali-${stamp}.db"`,
      "Cache-Control": "no-store",
    },
  });
}
