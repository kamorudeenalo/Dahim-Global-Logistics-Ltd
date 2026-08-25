import "server-only";

import { getSessionCookie, clearSessionCookie } from "@/lib/auth/session-cookie";
import { revokeSession } from "@/lib/auth/session";

export async function logout() {
  const token = await getSessionCookie();

  if (token) {
    await revokeSession(token);
  }

  await clearSessionCookie();
}
