import { getCurrentUser } from "@/lib/auth/current-user";
import { approveAccount } from "@/lib/auth/approval";
import { requireSameOrigin } from "@/lib/security/csrf";

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

  const { userId } = await context.params;

  try {
    const result = await approveAccount(
      userId,
      currentUser.id
    );

    if (!result) {
      return Response.json(
        { error: "Account cannot be approved." },
        { status: 400 }
      );
    }

    return Response.json({
      approved: true,
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
      { error: "Unable to approve account." },
      { status: 500 }
    );
  }
}
