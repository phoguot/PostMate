import { Component, input, output } from '@angular/core';
import { AppIcon } from '../icon/app-icon';
import { LucideX } from '@lucide/angular';

@Component({
  selector: 'app-detail-drawer',
  standalone: true,
  imports: [AppIcon],
  template: `
    @if (open()) {
      <aside class="flex h-full w-full flex-col overflow-y-auto border-l border-gray-200 bg-white">
        <header class="flex items-start justify-between border-b border-gray-100 px-4 py-3">
          <div class="min-w-0">
            <h2 class="truncate text-base font-semibold text-gray-900">{{ title() }}</h2>
            @if (subtitle()) {
              <p class="mt-0.5 truncate text-sm text-gray-500">{{ subtitle() }}</p>
            }
          </div>
          <button
            type="button"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100"
            (click)="closed.emit()"
          >
            <app-icon [icon]="closeIcon" [size]="18" />
          </button>
        </header>
        <div class="flex-1 overflow-y-auto">
          <ng-content />
        </div>
      </aside>
    }
  `
})
export class DetailDrawer {
  readonly open = input.required<boolean>();
  readonly title = input<string>('');
  readonly subtitle = input<string>('');

  readonly closed = output<void>();

  protected readonly closeIcon = LucideX;
}
