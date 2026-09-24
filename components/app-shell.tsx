"use client";

import { logoutAction } from "@/lib/actions";
import { APP_NAME, APP_NAME_TH, ROLE_LABEL } from "@/lib/constants";
import { cn } from "@/lib/format";
import { navForRole } from "@/lib/permissions";
import type { PublicUser } from "@/lib/types";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";

const ICONS: Record<string, string> = {
  layout: "▦",
  clipboard: "☰",
  wrench: "⚒",
  calendar: "◷",
  lift: "▲",
  box: "▣",
  database: "☰",
  chart: "▤",
  activity: "●",
};

export function AppShell({ user, children }: { user: PublicUser; children: React.ReactNode }) {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);
  const items = navForRole(user.role);

  return (
    <div className="min-h-screen lg:grid lg:grid-cols-[240px_1fr]">
      {open ? (
        <button
          className="fixed inset-0 z-30 bg-black/40 lg:hidden"
          aria-label="ปิดเมนู"
          onClick={() => setOpen(false)}
        />
      ) : null}
      <aside
        className={cn(
          "fixed inset-y-0 left-0 z-40 w-64 bg-sidebar text-white flex flex-col lg:static lg:w-auto lg:translate-x-0 transition-transform",
          open ? "translate-x-0" : "-translate-x-full lg:translate-x-0",
        )}
      >
        <div className="px-5 py-5 border-b border-white/10">
          <p className="text-[11px] uppercase tracking-[0.18em] text-white/50">{APP_NAME}</p>
          <p className="text-lg font-semibold">{APP_NAME_TH}</p>
          <p className="text-xs text-white/55 mt-1">รถกระเช้าไฟฟ้า · ยุธาภัคร์</p>
        </div>
        <nav className="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
          {items.map((item) => {
            const active =
              item.href === "/"
                ? pathname === "/"
                : pathname === item.href || pathname.startsWith(`${item.href}/`);
            return (
              <Link
                key={item.href}
                href={item.href}
                onClick={() => setOpen(false)}
                className={cn(
                  "flex items-center gap-2 rounded-lg px-3 py-2 text-sm",
                  active ? "bg-white/10 text-white" : "text-white/70 hover:bg-white/5 hover:text-white",
                )}
              >
                <span className="w-5 text-center opacity-70">{ICONS[item.icon] ?? "·"}</span>
                {item.label}
              </Link>
            );
          })}
        </nav>
        <div className="px-4 py-4 border-t border-white/10 text-sm">
          <p className="font-medium truncate">{user.name_th}</p>
          <p className="text-xs text-white/55">{ROLE_LABEL[user.role]}</p>
          <form action={logoutAction} className="mt-3">
            <button className="text-xs text-white/70 hover:text-white underline-offset-2 hover:underline">
              ออกจากระบบ
            </button>
          </form>
        </div>
      </aside>
      <div className="min-w-0">
        <header className="lg:hidden flex items-center gap-3 px-4 py-3 border-b border-line bg-card sticky top-0 z-20">
          <button
            type="button"
            className="rounded-md border border-line px-2 py-1 text-sm"
            onClick={() => setOpen(true)}
            aria-label="เปิดเมนู"
          >
            เมนู
          </button>
          <span className="font-semibold">{APP_NAME_TH}</span>
        </header>
        <main className="px-4 py-5 sm:px-6 lg:px-8 max-w-6xl">{children}</main>
      </div>
    </div>
  );
}
