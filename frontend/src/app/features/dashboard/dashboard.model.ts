export interface DashboardOverview {
  total: number;
  published: number;
  publishedRate: number;
  scheduled: number;
  failed: number;
  processing: number;
  deltas: {
    total: number | null;
    published: number | null;
    scheduled: number | null;
    failed: number | null;
    processing: number | null;
  };
}

export interface DashboardPerformancePoint {
  date: string;
  published: number;
  pending: number;
  failed: number;
}

export interface DashboardDistributionItem {
  status: number;
  name: string;
  count: number;
  percent: number;
}

export interface DashboardDistribution {
  total: number;
  distribution: DashboardDistributionItem[];
}

export interface DashboardHealth {
  queue: { pending: number };
  aiAgents: null;
  browsers: { total: number; serverCount: number; running: number; stopped: number; offline: number };
  cookies: { total: number; valid: number; expiring: number; invalid: number };
  overall: 'good' | 'warning';
}
