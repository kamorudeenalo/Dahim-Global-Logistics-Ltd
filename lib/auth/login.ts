import "server-only";

import { authenticateUser } from "@/lib/auth/authenticate";
import { createSession } from "@/lib/auth/session";
import { setSessionCookie } from "@/lib/auth/session-cookie";

export async function login(
  identifier: string,
  password: string,
  options?: {
    ipAddress?: string;
    userAgent?: string;
  }
) {
  const user = await authenticateUser(identifier, password);

  if (!user) {
    return null;
  }

  const { token } = await createSession(user.id, options);

  await setSessionCookie(token);

  return user;
}
