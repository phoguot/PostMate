import { Component, input } from '@angular/core';
import { LucideDynamicIcon, LucideIconInput } from '@lucide/angular';

@Component({
  selector: 'app-icon',
  standalone: true,
  imports: [LucideDynamicIcon],
  template: `<svg [lucideIcon]="icon()" [size]="size()" [strokeWidth]="strokeWidth()"></svg>`
})
export class AppIcon {
  readonly icon = input.required<LucideIconInput>();
  readonly size = input<number>(18);
  readonly strokeWidth = input<number>(1.75);
}
