import { ticketStatusLabel } from '../utils';

export function statusBadge(status) {
  return <span className={'TicketStatus TicketStatus--' + (status || 'open')}>{ticketStatusLabel(status)}</span>;
}

function sumArr(arr) {
  let total = 0;
  for (let i = 0; i < (arr || []).length; i++) {
    total += arr[i] || 0;
  }
  return total;
}

function niceMax(n) {
  if (n <= 0) {
    return 4;
  }
  const exp = Math.pow(10, Math.floor(Math.log10(n)));
  const m = n / exp;
  const nice = m <= 1 ? 1 : m <= 2 ? 2 : m <= 5 ? 5 : 10;
  return nice * exp;
}

function formatChartDay(iso) {
  if (!iso) {
    return '';
  }
  const parts = String(iso).split('-');
  if (parts.length !== 3) {
    return iso;
  }
  return parts[2] + '.' + parts[1];
}

export default class LineChart {
  view(vnode) {
    const title = vnode.attrs.title;
    const labels = vnode.attrs.labels || [];
    const series = vnode.attrs.series || [];
    const W = 440;
    const H = 220;
    const L = 36;
    const R = 12;
    const T = 16;
    const B = 28;
    const innerW = W - L - R;
    const innerH = H - T - B;

    let max = 0;
    for (let i = 0; i < series.length; i++) {
      const values = series[i].values || [];
      for (let j = 0; j < values.length; j++) {
        if (values[j] > max) {
          max = values[j];
        }
      }
    }
    max = niceMax(max);

    const n = labels.length || 1;
    const xAt = (idx) => (n <= 1 ? L + innerW / 2 : L + (idx / (n - 1)) * innerW);
    const yAt = (v) => T + innerH - ((v || 0) / max) * innerH;
    const points = (vals) => {
      const pts = [];
      for (let k = 0; k < vals.length; k++) {
        pts.push(xAt(k) + ',' + yAt(vals[k] || 0));
      }
      return pts.join(' ');
    };

    const grid = [];
    const ticks = 4;
    for (let i = 0; i <= ticks; i++) {
      const val = Math.round((max / ticks) * i);
      const y = yAt(val);
      grid.push(<line className="ModerationLineChart-grid" x1={L} x2={W - R} y1={y} y2={y} />);
      grid.push(
        <text className="ModerationLineChart-ylabel" x={L - 6} y={y + 3} text-anchor="end">
          {String(val)}
        </text>
      );
    }

    const xLabels = [];
    const showIdx = n >= 30 ? [0, 7, 14, 21, 29] : [0, Math.floor((n - 1) / 2), n - 1];
    const seen = {};
    for (let i = 0; i < showIdx.length; i++) {
      const j = showIdx[i];
      if (j < 0 || j >= n || seen[j]) {
        continue;
      }
      seen[j] = true;
      xLabels.push(
        <text
          className="ModerationLineChart-xlabel"
          x={xAt(j)}
          y={H - 8}
          text-anchor={j === 0 ? 'start' : j === n - 1 ? 'end' : 'middle'}
        >
          {formatChartDay(labels[j])}
        </text>
      );
    }

    const lines = [];
    const dots = [];
    for (let i = 0; i < series.length; i++) {
      const ser = series[i];
      const values = ser.values || [];
      lines.push(
        <polyline
          className="ModerationLineChart-line"
          points={points(values)}
          stroke={ser.color}
          stroke-width="2.25"
          stroke-linejoin="round"
          stroke-linecap="round"
        />
      );
      for (let j = 0; j < values.length; j++) {
        dots.push(
          <circle
            className="ModerationLineChart-dot"
            cx={xAt(j)}
            cy={yAt(values[j] || 0)}
            r={values[j] ? 3 : 2.25}
            fill={ser.color}
          >
            <title>
              {formatChartDay(labels[j])} — {ser.label}: {values[j] || 0}
            </title>
          </circle>
        );
      }
    }

    return (
      <div className="ModerationPage-chart">
        <div className="ModerationPage-chartHead">
          <h3>{title}</h3>
          <div className="ModerationPage-legend">
            {series.map((ser) => (
              <span className="ModerationPage-legendItem">
                <i className="ModerationPage-legendSwatch" style={{ background: ser.color }} />
                {ser.label + ' (' + sumArr(ser.values) + ')'}
              </span>
            ))}
          </div>
        </div>
        <svg className="ModerationLineChart" viewBox={'0 0 ' + W + ' ' + H} role="img" aria-label={title}>
          {grid}
          {xLabels}
          {lines}
          {dots}
        </svg>
      </div>
    );
  }
}
