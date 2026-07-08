import { Component, computed, input } from '@angular/core';
import { BarChartGroup } from './bar-chart.model';

const CHART_HEIGHT = 180;

@Component({
  selector: 'app-bar-chart',
  standalone: true,
  template: `
    <div class="flex items-end gap-4" [style.height.px]="CHART_HEIGHT + 24">
      @for (group of groups(); track group.label) {
        <div class="flex flex-1 flex-col items-center gap-2">
          <div class="flex w-full max-w-10 flex-col-reverse overflow-hidden rounded-t-md" [style.height.px]="CHART_HEIGHT">
            @for (segment of group.segments; track segment.colorVar) {
              @if (segment.value > 0) {
                <div [style.height.%]="segmentHeightPct(segment.value)" [style.backgroundColor]="segment.colorVar"></div>
              }
            }
          </div>
          <span class="text-xs text-gray-400">{{ group.label }}</span>
        </div>
      }
    </div>
  `
})
export class BarChart {
  readonly groups = input.required<BarChartGroup[]>();

  protected readonly CHART_HEIGHT = CHART_HEIGHT;

  private readonly maxTotal = computed(() =>
    Math.max(
      1,
      ...this.groups().map((g) => g.segments.reduce((sum, s) => sum + s.value, 0))
    )
  );

  protected segmentHeightPct(value: number): number {
    return (value / this.maxTotal()) * 100;
  }
}
