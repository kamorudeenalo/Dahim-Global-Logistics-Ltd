import "server-only";

import { getSessionByToken } from "@/lib/auth/session";
import { getSessionCookie } from "@/lib/auth/session-cookie";

export async function getCurrentUser() {
  const token = await getSessionCookie();

  if (!token) {
    return null;
  }

  const session = await getSessionByToken(token);

  if (!session) {
    return null;
  }

  return session.user;
}
