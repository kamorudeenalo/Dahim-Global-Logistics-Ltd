import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Dahim Global Logistics",
  description: "Standalone Dahim Global Logistics platform.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
