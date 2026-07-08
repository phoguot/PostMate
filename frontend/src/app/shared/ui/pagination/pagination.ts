import { Component, computed, input, output } from '@angular/core';
import { AppIcon } from '../icon/app-icon';
import { LucideChevronLeft, LucideChevronRight } from '@lucide/angular';

@Component({
  selector: 'app-pagination',
  standalone: true,
  imports: [AppIcon],
  template: `
    <div class="flex items-center justify-between gap-4 border-t border-gray-100 px-4 py-3 text-sm text-gray-500">
      <p>Hiển thị {{ rangeStart() }} - {{ rangeEnd() }} trong {{ totalItems() }} kết quả</p>
      <div class="flex items-center gap-1">
        <button
          type="button"
          class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 disabled:opacity-40"
          [disabled]="page() <= 1"
          (click)="pageChange.emit(page() - 1)"
        >
          <app-icon [icon]="chevronLeft" [size]="16" />
        </button>
        @for (p of pageNumbers(); track p) {
          @if (p === -1) {
            <span class="px-1.5">…</span>
          } @else {
            <button
              type="button"
              class="flex h-8 w-8 items-center justify-center rounded-lg text-sm"
              [class]="p === page() ? 'bg-[var(--color-brand)] text-white' : 'hover:bg-gray-100'"
              (click)="pageChange.emit(p)"
            >
              {{ p }}
            </button>
          }
        }
        <button
          type="button"
          class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 disabled:opacity-40"
          [disabled]="page() >= totalPages()"
          (click)="pageChange.emit(page() + 1)"
        >
          <app-icon [icon]="chevronRight" [size]="16" />
        </button>
      </div>
    </div>
  `
})
export class Pagination {
  readonly page = input.required<number>();
  readonly pageSize = input.required<number>();
  readonly totalItems = input.required<number>();
  readonly totalPages = input.required<number>();

  readonly pageChange = output<number>();

  protected readonly chevronLeft = LucideChevronLeft;
  protected readonly chevronRight = LucideChevronRight;

  protected readonly rangeStart = computed(() => (this.totalItems() === 0 ? 0 : (this.page() - 1) * this.pageSize() + 1));
  protected readonly rangeEnd = computed(() => Math.min(this.page() * this.pageSize(), this.totalItems()));

  protected readonly pageNumbers = computed<number[]>(() => {
    const total = this.totalPages();
    const current = this.page();
    if (total <= 7) {
      return Array.from({ length: total }, (_, i) => i + 1);
    }
    const pages = new Set<number>([1, 2, total - 1, total, current - 1, current, current + 1]);
    const sorted = [...pages].filter((p) => p >= 1 && p <= total).sort((a, b) => a - b);
    const withGaps: number[] = [];
    sorted.forEach((p, i) => {
      if (i > 0 && p - sorted[i - 1] > 1) {
        withGaps.push(-1);
      }
      withGaps.push(p);
    });
    return withGaps;
  });
}
