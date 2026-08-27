import "server-only";

import { prisma } from "@/lib/db";
import { authenticateUser } from "@/lib/auth/authenticate";
import { createSession } from "@/lib/auth/session";
import { setSessionCookie } from "@/lib/auth/session-cookie";
import { writeAuditLog } from "@/lib/auth/audit";
import { checkRateLimit } from "@/lib/security/rate-limit";

const LOGIN_LIMIT = 5;
const LOGIN_WINDOW_MS = 15 * 60 * 1000;

export async function login(
  identifier: string,
  password: string,
  options?: {
    ipAddress?: string;
    userAgent?: string;
  }
) {
  const normalizedIdentifier = identifier.trim().toLowerCase();

  const rateLimit = await checkRateLimit(
    `login:${normalizedIdentifier}`,
    LOGIN_LIMIT,
    LOGIN_WINDOW_MS
  );

  if (!rateLimit.allowed) {
    const existingUser = await prisma.user.findFirst({
      where: {
        OR: [
          { email: normalizedIdentifier },
          { username: normalizedIdentifier },
        ],
      },
      select: {
        id: true,
      },
    });

    await writeAuditLog("LOGIN_FAILED", {
      userId: existingUser?.id,
      ipAddress: options?.ipAddress,
      userAgent: options?.userAgent,
      metadata: {
        reason: "RATE_LIMITED",
      },
    });

    return null;
  }

  const user = await authenticateUser(
    normalizedIdentifier,
    password
  );

  if (!user) {
    const existingUser = await prisma.user.findFirst({
      where: {
        OR: [
          { email: normalizedIdentifier },
          { username: normalizedIdentifier },
        ],
      },
      select: {
        id: true,
      },
    });

    await writeAuditLog("LOGIN_FAILED", {
      userId: existingUser?.id,
      ipAddress: options?.ipAddress,
      userAgent: options?.userAgent,
    });

    return null;
  }

  const { token } = await createSession(
    user.id,
    options
  );

  await setSessionCookie(token);

  await prisma.user.update({
    where: {
      id: user.id,
    },
    data: {
      lastLoginAt: new Date(),
    },
  });

  await writeAuditLog("LOGIN_SUCCESS", {
    userId: user.id,
    ipAddress: options?.ipAddress,
    userAgent: options?.userAgent,
  });

  return user;
}
