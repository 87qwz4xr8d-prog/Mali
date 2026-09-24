"use client";

import { loginAction } from "@/lib/actions";
import { Alert, inputClass } from "@/components/ui";
import { useActionState } from "react";

export function LoginForm({ showSeedHint = false }: { showSeedHint?: boolean }) {
  const [state, action, pending] = useActionState(loginAction, {});
  return (
    <form action={action} className="space-y-4">
      {state.error ? <Alert>{state.error}</Alert> : null}
      <label className="block">
        <span className="text-sm font-medium">ชื่อผู้ใช้</span>
        <input className={inputClass("mt-1")} name="username" autoComplete="username" required />
      </label>
      <label className="block">
        <span className="text-sm font-medium">รหัสผ่าน</span>
        <input
          className={inputClass("mt-1")}
          type="password"
          name="password"
          autoComplete="current-password"
          required
        />
      </label>
      <button
        type="submit"
        disabled={pending}
        className="w-full rounded-lg bg-accent text-white py-2.5 text-sm font-medium hover:bg-[#b56a10] disabled:opacity-60"
      >
        {pending ? "กำลังเข้าสู่ระบบ…" : "เข้าสู่ระบบ"}
      </button>
      {showSeedHint ? (
        <p className="text-xs text-muted leading-5">
          บัญชีทดลองติดตั้งครั้งแรก: <code>admin</code> / <code>Mali@2569</code>
          <br />
          ช่าง: <code>tech</code> · วิศวกร: <code>engineer</code> · หัวหน้า: <code>manager</code>
        </p>
      ) : null}
    </form>
  );
}
