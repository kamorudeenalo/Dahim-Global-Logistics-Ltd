import { logout } from "@/lib/auth/logout";
import { requireSameOrigin } from "@/lib/security/csrf";

export async function POST(request: Request) {
  try {
    requireSameOrigin(request);
  } catch {
    return Response.json(
      { error: "Forbidden" },
      { status: 403 }
    );
  }

  await logout();

  return Response.json({
    authenticated: false,
  });
}
