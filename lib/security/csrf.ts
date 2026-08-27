import "server-only";

const SAFE_METHODS = new Set([
  "GET",
  "HEAD",
  "OPTIONS",
]);

function getConfiguredOrigin(): string {
  const configuredUrl =
    process.env.APP_URL?.trim();

  if (!configuredUrl) {
    throw new Error(
      "APP_URL must be configured for request-origin protection."
    );
  }

  return new URL(configuredUrl).origin;
}

export function isSafeMethod(method: string): boolean {
  return SAFE_METHODS.has(method.toUpperCase());
}

export function isSameOrigin(request: Request): boolean {
  if (isSafeMethod(request.method)) {
    return true;
  }

  const expectedOrigin = getConfiguredOrigin();

  const origin = request.headers.get("origin");

  if (origin) {
    try {
      return new URL(origin).origin === expectedOrigin;
    } catch {
      return false;
    }
  }

  const referer = request.headers.get("referer");

  if (referer) {
    try {
      return new URL(referer).origin === expectedOrigin;
    } catch {
      return false;
    }
  }

  return false;
}

export function requireSameOrigin(request: Request): void {
  if (!isSameOrigin(request)) {
    throw new Error("FORBIDDEN_ORIGIN");
  }
}
