export interface RefName {
  id: number;
  name: string;
}

export interface FacebookAccount {
  id: number;
  displayName: string | null;
  email: string | null;
  avatarUrl: string | null;
  oauthConnected: boolean;
  browserProfile: RefName | null;
  server: string | null;
  proxyIp: string | null;
  status: number;
  accountRole: string | null;
  isPrimary: boolean;
  expiresAt: string | null;
  lastLoginAt: string | null;
  lastLoginIp: string | null;
  device: string | null;
  userAgent: string | null;
  capabilities: {
    canPost: boolean;
    canUpload: boolean;
    canComment: boolean;
    canReply: boolean;
    canInbox: boolean;
  };
  fanpageCount: number;
  cookieStatus: number | null;
  cookieExpiresAt: string | null;
  modifiedAt: number | null;
  createdAt: number | null;
}

export interface Fanpage {
  id: number;
  fbPageId: string | null;
  name: string | null;
  category: string | null;
  url: string | null;
  facebookAccount: RefName | null;
  browserProfile: RefName | null;
  likesCount: number;
  followersCount: number;
  status: number;
  canPost: boolean;
  canPostReason: string | null;
  capabilities: { canUpload: boolean; canComment: boolean; canReply: boolean; canInbox: boolean };
  lastPostAt: string | null;
  apiEnabled: boolean;
  tokenExpiresAt: string | null;
  channel: number;
  modifiedAt: number | null;
  createdAt: number | null;
}

export interface Cookie {
  id: number;
  code: string | null;
  facebookAccount: RefName | null;
  browserProfile: RefName | null;
  fanpages: string[];
  sizeKb: number | null;
  status: number;
  expiresAt: string | null;
  daysLeft: number | null;
  lastLoginAt: string | null;
  lastLoginIp: string | null;
  device: string | null;
  userAgent: string | null;
  modifiedAt: number | null;
  createdAt: number | null;
}
