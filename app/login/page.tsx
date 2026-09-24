import { APP_NAME, APP_NAME_TH } from "@/lib/constants";
import { getSessionUser } from "@/lib/auth";
import { getDb } from "@/lib/db/connection";
import { redirect } from "next/navigation";
import { LoginForm } from "./login-form";

export default async function LoginPage() {
  getDb();
  const user = await getSessionUser();
  const user = await getSessionUser();
  if (user) redirect("/");

  return (
    <div className="min-h-screen grid lg:grid-cols-2">
      <section className="hidden lg:flex flex-col justify-between bg-sidebar text-white p-12">
        <div>
          <p className="text-xs uppercase tracking-[0.2em] text-white/50">{APP_NAME}</p>
          <h1 className="mt-4 text-4xl font-semibold leading-tight">
            ระบบซ่อมบำรุง
            <br />
            รถกระเช้าไฟฟ้า
          </h1>
          <p className="mt-4 max-w-md text-white/70">
            ใบแจ้งซ่อม → ใบงานซ่อม → PM → อะไหล่ สำหรับอู่ยุธาภัคร์ หนองใหญ่
          </p>
        </div>
        <p className="text-sm text-white/50">บจก.ยุธาภัคร์ · ชลบุรี</p>
      </section>
      <section className="flex items-center justify-center p-6">
        <div className="w-full max-w-sm">
          <p className="lg:hidden text-xs uppercase tracking-[0.2em] text-muted">{APP_NAME}</p>
          <h2 className="text-2xl font-semibold">{APP_NAME_TH}</h2>
          <p className="text-sm text-muted mt-1 mb-6">เข้าสู่ระบบด้วยบัญชีช่างหรือผู้ดูแล</p>
          <LoginForm showSeedHint={process.env.MALI_SHOW_SEED_LOGINS === "1"} />
        </div>
      </section>
    </div>
  );
}
