import { Injectable, signal } from '@angular/core';

export type ToastTone = 'success' | 'error' | 'info';

export interface Toast {
  id: number;
  message: string;
  tone: ToastTone;
}

let nextId = 1;

@Injectable({ providedIn: 'root' })
export class NotifyService {
  private readonly _toasts = signal<Toast[]>([]);
  readonly toasts = this._toasts.asReadonly();

  success(message: string): void {
    this.push(message, 'success');
  }

  error(message: string): void {
    this.push(message, 'error');
  }

  info(message: string): void {
    this.push(message, 'info');
  }

  dismiss(id: number): void {
    this._toasts.update((list) => list.filter((t) => t.id !== id));
  }

  private push(message: string, tone: ToastTone): void {
    const toast: Toast = { id: nextId++, message, tone };
    this._toasts.update((list) => [...list, toast]);
    setTimeout(() => this.dismiss(toast.id), 4000);
  }
}
