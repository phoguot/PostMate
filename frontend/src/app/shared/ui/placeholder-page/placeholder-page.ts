import { Component, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { toSignal } from '@angular/core/rxjs-interop';
import { map } from 'rxjs/operators';
import { PageHeader } from '../page-header/page-header';

@Component({
  selector: 'app-placeholder-page',
  standalone: true,
  imports: [PageHeader],
  template: `
    <app-page-header [title]="title()" [subtitle]="subtitle()" />
    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white py-20 text-center">
      <p class="text-sm text-gray-500">{{ message() }}</p>
    </div>
  `
})
export class PlaceholderPage {
  private readonly route = inject(ActivatedRoute);

  private readonly data = toSignal(this.route.data, { initialValue: this.route.snapshot.data });

  protected readonly title = () => (this.data()['title'] as string) ?? '';
  protected readonly subtitle = () => (this.data()['subtitle'] as string) ?? '';
  protected readonly message = () =>
    (this.data()['message'] as string) ?? 'Tính năng này chưa có API backend tương ứng, sẽ được bổ sung sau.';
}
