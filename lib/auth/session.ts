import "server-only";

import { prisma } from "@/lib/db";
import {
  generateSessionToken,
  hashSessionToken,
} from "@/lib/auth/session-token";

const SESSION_DURATION_MS = 1000 * 60 * 60 * 24 * 7;

export async function createSession(
  userId: string,
  options?: {
    ipAddress?: string;
    userAgent?: string;
  }
) {
  const token = generateSessionToken();
  const tokenHash = hashSessionToken(token);
  const expiresAt = new Date(Date.now() + SESSION_DURATION_MS);

  const session = await prisma.session.create({
    data: {
      userId,
      tokenHash,
      expiresAt,
      ipAddress: options?.ipAddress,
      userAgent: options?.userAgent,
    },
  });

  return {
    token,
    session,
  };
}

export async function getSessionByToken(token: string) {
  if (!token) {
    return null;
  }

  const tokenHash = hashSessionToken(token);

  const session = await prisma.session.findUnique({
    where: {
      tokenHash,
    },
    include: {
      user: {
        select: {
          id: true,
          email: true,
          username: true,
          status: true,
          emailVerifiedAt: true,
          lastLoginAt: true,
          createdAt: true,
          updatedAt: true,
        },
      },
    },
  });

  if (!session) {
    return null;
  }

  if (session.revokedAt || session.expiresAt <= new Date()) {
    return null;
  }

  await prisma.session.update({
    where: {
      id: session.id,
    },
    data: {
      lastUsedAt: new Date(),
    },
  });

  return session;
}

export async function revokeSession(token: string) {
  if (!token) {
    return;
  }

  const tokenHash = hashSessionToken(token);

  await prisma.session.updateMany({
    where: {
      tokenHash,
      revokedAt: null,
    },
    data: {
      revokedAt: new Date(),
    },
  });
}

export async function revokeAllUserSessions(userId: string) {
  await prisma.session.updateMany({
    where: {
      userId,
      revokedAt: null,
    },
    data: {
      revokedAt: new Date(),
    },
  });
}
