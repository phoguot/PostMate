export interface BarChartSegment {
  value: number;
  colorVar: string;
}

export interface BarChartGroup {
  label: string;
  segments: BarChartSegment[];
}
