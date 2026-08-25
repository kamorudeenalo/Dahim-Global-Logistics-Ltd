import "server-only";

import { prisma } from "@/lib/db";
import {
  generateSecureToken,
  hashSecureToken,
  getEmailVerificationExpiry,
} from "@/lib/auth/tokens";

export async function createEmailVerificationToken(userId: string) {
  const token = generateSecureToken();
  const tokenHash = hashSecureToken(token);
  const expiresAt = getEmailVerificationExpiry();

  await prisma.emailVerificationToken.deleteMany({
    where: {
      userId,
      usedAt: null,
    },
  });

  const record = await prisma.emailVerificationToken.create({
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

export async function verifyEmail(token: string) {
  if (!token) {
    return null;
  }

  const tokenHash = hashSecureToken(token);

  const record = await prisma.emailVerificationToken.findUnique({
    where: {
      tokenHash,
    },
  });

  if (!record || record.usedAt || record.expiresAt <= new Date()) {
    return null;
  }

  const user = await prisma.user.update({
    where: {
      id: record.userId,
    },
    data: {
      emailVerifiedAt: new Date(),
    },
    select: {
      id: true,
      email: true,
      username: true,
      status: true,
      emailVerifiedAt: true,
    },
  });

  await prisma.emailVerificationToken.update({
    where: {
      id: record.id,
    },
    data: {
      usedAt: new Date(),
    },
  });

  return user;
}
