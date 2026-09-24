import { requireUser } from "@/lib/auth";
import { AppShell } from "@/components/app-shell";
import type { PublicUser } from "@/lib/types";

export const dynamic = "force-dynamic";
export const runtime = "nodejs";

export default async function ShellLayout({ children }: { children: React.ReactNode }) {
  const user = await requireUser();
  const safe: PublicUser = JSON.parse(JSON.stringify(user));
  return <AppShell user={safe}>{children}</AppShell>;
}
