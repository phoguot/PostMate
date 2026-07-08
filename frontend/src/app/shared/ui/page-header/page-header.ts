import { Component, input } from '@angular/core';

@Component({
  selector: 'app-page-header',
  standalone: true,
  template: `
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">{{ title() }}</h1>
        @if (subtitle()) {
          <p class="mt-1 text-sm text-gray-500">{{ subtitle() }}</p>
        }
      </div>
      <div class="flex items-center gap-2">
        <ng-content />
      </div>
    </div>
  `
})
export class PageHeader {
  readonly title = input.required<string>();
  readonly subtitle = input<string>('');
}
