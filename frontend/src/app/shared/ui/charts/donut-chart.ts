import { Component, computed, input } from '@angular/core';
import { DonutSlice } from './donut-chart.model';

interface DonutArc extends DonutSlice {
  dashArray: string;
  dashOffset: number;
}

const RADIUS = 60;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

@Component({
  selector: 'app-donut-chart',
  standalone: true,
  template: `
    <div class="relative flex items-center justify-center">
      <svg width="180" height="180" viewBox="0 0 160 160">
        <g transform="translate(80,80) rotate(-90)">
          @for (arc of arcs(); track arc.label) {
            <circle
              r="60"
              fill="none"
              [attr.stroke]="arc.colorVar"
              stroke-width="18"
              [attr.stroke-dasharray]="arc.dashArray"
              [attr.stroke-dashoffset]="arc.dashOffset"
            />
          }
        </g>
      </svg>
      <div class="absolute flex flex-col items-center">
        <span class="text-xs text-gray-400">Tổng</span>
        <span class="text-xl font-semibold text-gray-900">{{ total() }}</span>
      </div>
    </div>
  `
})
export class DonutChart {
  readonly slices = input.required<DonutSlice[]>();

  protected readonly total = computed(() => this.slices().reduce((sum, s) => sum + s.value, 0));

  protected readonly arcs = computed<DonutArc[]>(() => {
    const total = this.total() || 1;
    let cumulative = 0;
    return this.slices().map((slice) => {
      const length = (slice.value / total) * CIRCUMFERENCE;
      const arc: DonutArc = {
        ...slice,
        dashArray: `${length} ${CIRCUMFERENCE - length}`,
        dashOffset: -cumulative
      };
      cumulative += length;
      return arc;
    });
  });
}
