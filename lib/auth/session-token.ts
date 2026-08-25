import "server-only";
import crypto from "node:crypto";

const SESSION_TOKEN_BYTES = 32;

export function generateSessionToken(): string {
  return crypto.randomBytes(SESSION_TOKEN_BYTES).toString("base64url");
}

export function hashSessionToken(token: string): string {
  return crypto.createHash("sha256").update(token, "utf8").digest("hex");
}
