import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",
  serverExternalPackages: ["bcryptjs"],
  allowedDevOrigins: ["127.0.0.1", "localhost"],
  agentRules: false,
};

export default nextConfig;
