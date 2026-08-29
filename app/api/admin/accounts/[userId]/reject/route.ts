import { getCurrentUser } from "@/lib/auth/current-user";
import { rejectAccount } from "@/lib/auth/approval";
import { requireSameOrigin } from "@/lib/security/csrf";

type RejectBody = {
  reason?: unknown;
};

type RouteContext = {
  params: Promise<{
    userId: string;
  }>;
};

export async function POST(
  request: Request,
  context: RouteContext
) {
  try {
    requireSameOrigin(request);
  } catch {
    return Response.json(
      { error: "Forbidden" },
      { status: 403 }
    );
  }

  const currentUser = await getCurrentUser();

  if (!currentUser) {
    return Response.json(
      { error: "Authentication required." },
      { status: 401 }
    );
  }

  let body: RejectBody = {};

  try {
    body = await request.json();
  } catch {
    // Empty request body is allowed; reason is optional.
  }

  const reason =
    typeof body.reason === "string"
      ? body.reason.trim() || undefined
      : undefined;

  const { userId } = await context.params;

  try {
    const result = await rejectAccount(
      userId,
      currentUser.id,
      reason
    );

    if (!result) {
      return Response.json(
        { error: "Account cannot be rejected." },
        { status: 400 }
      );
    }

    return Response.json({
      rejected: true,
      user: result.user,
    });
  } catch (error) {
    if (
      error instanceof Error &&
      error.message === "FORBIDDEN"
    ) {
      return Response.json(
        { error: "Forbidden" },
        { status: 403 }
      );
    }

    return Response.json(
      { error: "Unable to reject account." },
      { status: 500 }
    );
  }
}
