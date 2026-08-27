import { getCurrentUser } from "@/lib/auth/current-user";

export async function GET() {
  const user = await getCurrentUser();

  if (!user) {
    return Response.json(
      {
        authenticated: false,
      },
      {
        status: 401,
      }
    );
  }

  return Response.json({
    authenticated: true,
    user: {
      id: user.id,
      email: user.email,
      username: user.username,
      status: user.status,
      emailVerifiedAt: user.emailVerifiedAt,
      lastLoginAt: user.lastLoginAt,
    },
  });
}
