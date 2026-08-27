import "server-only";

import { getSessionCookie, clearSessionCookie } from "@/lib/auth/session-cookie";
import { getSessionByToken, revokeSession } from "@/lib/auth/session";
import { writeAuditLog } from "@/lib/auth/audit";

export async function logout() {
  const token = await getSessionCookie();

  if (token) {
    const session = await getSessionByToken(token);

    await revokeSession(token);

    if (session?.user?.id) {
      await writeAuditLog("LOGOUT", {
        userId: session.user.id,
      });
    }
  }

  await clearSessionCookie();
}
