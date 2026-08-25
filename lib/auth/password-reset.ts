import "server-only";

import { prisma } from "@/lib/db";
import {
  generateSecureToken,
  hashSecureToken,
  getPasswordResetExpiry,
} from "@/lib/auth/tokens";
import { hashPassword } from "@/lib/auth/password";
import { revokeAllUserSessions } from "@/lib/auth/session";

export async function createPasswordResetToken(userId: string) {
  const token = generateSecureToken();
  const tokenHash = hashSecureToken(token);
  const expiresAt = getPasswordResetExpiry();

  await prisma.passwordResetToken.deleteMany({
    where: {
      userId,
      usedAt: null,
    },
  });

  const record = await prisma.passwordResetToken.create({
    data: {
      userId,
      tokenHash,
      expiresAt,
    },
  });

  return {
    token,
    record,
  };
}

export async function resetPassword(
  token: string,
  newPassword: string
) {
  if (!token) {
    return null;
  }

  const tokenHash = hashSecureToken(token);

  const record = await prisma.passwordResetToken.findUnique({
    where: {
      tokenHash,
    },
  });

  if (!record || record.usedAt || record.expiresAt <= new Date()) {
    return null;
  }

  const passwordHash = await hashPassword(newPassword);

  const user = await prisma.user.update({
    where: {
      id: record.userId,
    },
    data: {
      passwordHash,
    },
    select: {
      id: true,
      email: true,
      username: true,
      status: true,
      emailVerifiedAt: true,
    },
  });

  await prisma.passwordResetToken.update({
    where: {
      id: record.id,
    },
    data: {
      usedAt: new Date(),
    },
  });

  await revokeAllUserSessions(user.id);

  return user;
}
