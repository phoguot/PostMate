import { Component, inject } from '@angular/core';
import { NotifyService, ToastTone } from '../../../core/notify/notify.service';

const TONE_CLASSES: Record<ToastTone, string> = {
  success: 'bg-[var(--color-status-success)]',
  error: 'bg-[var(--color-status-danger)]',
  info: 'bg-gray-900'
};

@Component({
  selector: 'app-toast-host',
  standalone: true,
  template: `
    <div class="pointer-events-none fixed bottom-4 right-4 z-50 flex flex-col gap-2">
      @for (toast of notify.toasts(); track toast.id) {
        <div
          class="pointer-events-auto min-w-[240px] rounded-lg px-4 py-3 text-sm text-white shadow-lg"
          [class]="toneClass(toast.tone)"
        >
          {{ toast.message }}
        </div>
      }
    </div>
  `
})
export class ToastHost {
  protected readonly notify = inject(NotifyService);

  protected toneClass(tone: ToastTone): string {
    return TONE_CLASSES[tone];
  }
}
