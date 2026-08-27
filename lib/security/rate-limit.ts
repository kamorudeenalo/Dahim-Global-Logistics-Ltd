import "server-only";

import { prisma } from "@/lib/db";

type RateLimitResult = {
  allowed: boolean;
  remaining: number;
  retryAfterSeconds: number;
};

export async function checkRateLimit(
  key: string,
  limit: number,
  windowMs: number
): Promise<RateLimitResult> {
  if (!key || limit < 1 || windowMs < 1) {
    throw new Error("Invalid rate limit configuration.");
  }

  const now = new Date();
  const expiresAt = new Date(now.getTime() + windowMs);

  return prisma.$transaction(async (tx) => {
    const existing = await tx.rateLimit.findUnique({
      where: { key },
    });

    if (!existing || existing.expiresAt <= now) {
      await tx.rateLimit.upsert({
        where: { key },
        create: {
          key,
          count: 1,
          windowStart: now,
          expiresAt,
        },
        update: {
          count: 1,
          windowStart: now,
          expiresAt,
        },
      });

      return {
        allowed: true,
        remaining: Math.max(limit - 1, 0),
        retryAfterSeconds: Math.ceil(windowMs / 1000),
      };
    }

    if (existing.count >= limit) {
      return {
        allowed: false,
        remaining: 0,
        retryAfterSeconds: Math.max(
          Math.ceil(
            (existing.expiresAt.getTime() - now.getTime()) / 1000
          ),
          1
        ),
      };
    }

    const updated = await tx.rateLimit.update({
      where: { key },
      data: {
        count: {
          increment: 1,
        },
      },
    });

    return {
      allowed: true,
      remaining: Math.max(limit - updated.count, 0),
      retryAfterSeconds: Math.max(
        Math.ceil(
          (updated.expiresAt.getTime() - now.getTime()) / 1000
        ),
        1
      ),
    };
  });
}

export async function resetRateLimit(key: string) {
  if (!key) {
    return;
  }

  await prisma.rateLimit.deleteMany({
    where: { key },
  });
}
