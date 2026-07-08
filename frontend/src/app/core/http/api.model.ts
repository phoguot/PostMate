export interface Paginator {
  page: number;
  pageSize: number;
  totalItems: number;
  totalPages: number;
}

export interface PaginatedResult<T> {
  result: T[];
  paginator: Paginator;
}

export interface ApiEnvelope<T> {
  code: number;
  errorCode: string | null;
  messages: string[];
  data: T;
}

export const API_CODE = {
  FAILED: 0,
  SUCCESS: 1,
  SERVER_ERROR: 10
} as const;

export class ApiError extends Error {
  constructor(message: string, public readonly code: number, public readonly errorCode: string | null = null) {
    super(message);
  }
}
