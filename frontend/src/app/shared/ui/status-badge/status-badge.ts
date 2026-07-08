import { Component, computed, input } from '@angular/core';
import { StatusTone } from '../../../core/models/status.model';

const TONE_CLASSES: Record<StatusTone, string> = {
  success: 'bg-[var(--color-status-success-bg)] text-[var(--color-status-success)]',
  warning: 'bg-[var(--color-status-warning-bg)] text-[var(--color-status-warning)]',
  danger: 'bg-[var(--color-status-danger-bg)] text-[var(--color-status-danger)]',
  info: 'bg-[var(--color-status-info-bg)] text-[var(--color-status-info)]',
  neutral: 'bg-[var(--color-status-neutral-bg)] text-[var(--color-status-neutral)]'
};

@Component({
  selector: 'app-status-badge',
  standalone: true,
  template: `
    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" [class]="toneClass()">
      <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
      {{ label() }}
    </span>
  `
})
export class StatusBadge {
  readonly label = input.required<string>();
  readonly tone = input<StatusTone>('neutral');

  protected readonly toneClass = computed(() => TONE_CLASSES[this.tone()]);
}
