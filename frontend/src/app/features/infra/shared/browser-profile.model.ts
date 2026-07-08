export interface BrowserProfile {
  id: number;
  code: string | null;
  profileName: string | null;
  server: { id: number; name: string; ip: string } | null;
  proxyIp: string | null;
  facebookAccount: { id: number; email: string } | null;
  status: number;
  mode: number | null;
  chromeVersion: string | null;
  os: string | null;
  userAgent: string | null;
  fingerprint: unknown;
  cpuPercent: number | null;
  ramMb: number | null;
  startedAt: string | null;
  lastActiveAt: string | null;
  uptimeMinutes: number | null;
  modifiedAt: number | null;
  createdAt: number | null;
}
