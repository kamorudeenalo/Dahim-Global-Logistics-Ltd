import "server-only";

import { prisma } from "@/lib/db";
import {
  generateSecureToken,
  hashSecureToken,
  getEmailVerificationExpiry,
} from "@/lib/auth/tokens";
import { writeAuditLog } from "@/lib/auth/audit";
import { checkRateLimit } from "@/lib/security/rate-limit";

const EMAIL_VERIFICATION_LIMIT = 3;
const EMAIL_VERIFICATION_WINDOW_MS = 15 * 60 * 1000;

export async function createEmailVerificationToken(userId: string) {
  const rateLimit = await checkRateLimit(
    `email-verification:${userId}`,
    EMAIL_VERIFICATION_LIMIT,
    EMAIL_VERIFICATION_WINDOW_MS,
  );

  if (!rateLimit.allowed) {
    await writeAuditLog("EMAIL_VERIFICATION_REQUESTED", {
      userId,
      metadata: {
        reason: "RATE_LIMITED",
      },
    });

    return null;
  }

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

  await writeAuditLog("EMAIL_VERIFICATION_REQUESTED", {
    userId,
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

  const approval = await prisma.accountApproval.findFirst({
    where: {
      userId: record.userId,
      status: "APPROVED",
    },
    orderBy: {
      reviewedAt: "desc",
    },
  });

  const user = await prisma.user.update({
    where: {
      id: record.userId,
    },
    data: {
      emailVerifiedAt: new Date(),
      status: approval ? "ACTIVE" : "PENDING",
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

  await writeAuditLog("EMAIL_VERIFIED", {
    userId: user.id,
  });

  return user;
}
