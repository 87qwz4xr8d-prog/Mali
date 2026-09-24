import { createHmac, timingSafeEqual } from "node:crypto";
import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { SESSION_COOKIE, SESSION_DAYS } from "./constants";
import { findUserById } from "./db/queries";
import { sessionSecret } from "./runtime-config";
import type { PublicUser } from "./types";

function secret() {
  return sessionSecret();
}

function sign(payload: string) {
  return createHmac("sha256", secret()).update(payload).digest("base64url");
}

export function createSessionToken(userId: number) {
  const exp = Date.now() + SESSION_DAYS * 86400000;
  const payload = Buffer.from(JSON.stringify({ uid: userId, exp })).toString("base64url");
  return `${payload}.${sign(payload)}`;
}

export function readSessionToken(token: string): { uid: number; exp: number } | null {
  const [payload, sig] = token.split(".");
  if (!payload || !sig) return null;
  const expected = sign(payload);
  const a = Buffer.from(sig);
  const b = Buffer.from(expected);
  if (a.length !== b.length || !timingSafeEqual(a, b)) return null;
  try {
    const data = JSON.parse(Buffer.from(payload, "base64url").toString("utf8")) as {
      uid: number;
      exp: number;
    };
    if (Date.now() > data.exp) return null;
    return data;
  } catch {
    return null;
  }
}

export async function getSessionUser(): Promise<PublicUser | null> {
  const jar = await cookies();
  const token = jar.get(SESSION_COOKIE)?.value;
  if (!token) return null;
  const data = readSessionToken(token);
  if (!data) return null;
  return findUserById(data.uid) ?? null;
}

export async function requireUser() {
  const user = await getSessionUser();
  if (!user) redirect("/login");
  return user;
}

export async function setSessionCookie(userId: number) {
  const jar = await cookies();
  jar.set(SESSION_COOKIE, createSessionToken(userId), {
    httpOnly: true,
    sameSite: "lax",
    path: "/",
    maxAge: SESSION_DAYS * 86400,
    secure: process.env.MALI_HTTPS === "1",
  });
}

export async function clearSessionCookie() {
  const jar = await cookies();
  jar.delete(SESSION_COOKIE);
}
