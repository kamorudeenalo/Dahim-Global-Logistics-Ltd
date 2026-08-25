import "server-only";

import crypto from "node:crypto";

const EMAIL_VERIFICATION_DURATION_MS = 1000 * 60 * 60 * 24;
const PASSWORD_RESET_DURATION_MS = 1000 * 60 * 60;

export function generateSecureToken(): string {
  return crypto.randomBytes(32).toString("base64url");
}

export function hashSecureToken(token: string): string {
  return crypto.createHash("sha256").update(token, "utf8").digest("hex");
}

export function getEmailVerificationExpiry(): Date {
  return new Date(Date.now() + EMAIL_VERIFICATION_DURATION_MS);
}

export function getPasswordResetExpiry(): Date {
  return new Date(Date.now() + PASSWORD_RESET_DURATION_MS);
}
