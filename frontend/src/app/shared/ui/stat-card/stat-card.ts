import { Component, computed, input } from '@angular/core';
import { LucideIconInput } from '@lucide/angular';
import { AppIcon } from '../icon/app-icon';
import { StatusTone } from '../../../core/models/status.model';

const ICON_TONE_CLASSES: Record<StatusTone, string> = {
  success: 'bg-[var(--color-status-success-bg)] text-[var(--color-status-success)]',
  warning: 'bg-[var(--color-status-warning-bg)] text-[var(--color-status-warning)]',
  danger: 'bg-[var(--color-status-danger-bg)] text-[var(--color-status-danger)]',
  info: 'bg-[var(--color-status-info-bg)] text-[var(--color-status-info)]',
  neutral: 'bg-[var(--color-status-neutral-bg)] text-[var(--color-status-neutral)]'
};

@Component({
  selector: 'app-stat-card',
  standalone: true,
  imports: [AppIcon],
  template: `
    <div class="flex flex-1 min-w-[180px] items-start gap-3 rounded-xl border border-gray-200 bg-white p-4">
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" [class]="iconClass()">
        <app-icon [icon]="icon()" [size]="20" />
      </div>
      <div class="min-w-0">
        <p class="text-sm text-gray-500">{{ label() }}</p>
        <div class="flex items-baseline gap-2">
          <p class="text-2xl font-semibold text-gray-900">{{ value() }}</p>
          @if (trend()) {
            <span class="text-xs font-medium text-[var(--color-status-success)]">{{ trend() }}</span>
          }
        </div>
        @if (subtitle()) {
          <p class="mt-0.5 truncate text-xs text-gray-400">{{ subtitle() }}</p>
        }
      </div>
    </div>
  `
})
export class StatCard {
  readonly icon = input.required<LucideIconInput>();
  readonly label = input.required<string>();
  readonly value = input.required<string | number>();
  readonly subtitle = input<string>('');
  readonly trend = input<string>('');
  readonly tone = input<StatusTone>('info');

  protected readonly iconClass = computed(() => ICON_TONE_CLASSES[this.tone()]);
}
