import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import extractText from 'flarum/common/utils/extractText';
import username from 'flarum/common/helpers/username';
import HandleReportModal from '../components/HandleReportModal';
import LineChart, { statusBadge } from '../components/LineChart';
import { t, formatDate, reasonLabel, ticketCategoryLabel, metaTotal } from '../utils';

export default class ModerationPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);
    app.setTitle(t('panel.title'));
    this.status = 'pending';
    this.pageNumber = 1;
    this.perPage = 20;
    this.total = 0;
    this.loading = true;
    this.reports = [];
    this.tickets = [];
    this.ticketTotal = 0;
    this.section = 'reports';
    this.ticketStatus = 'inbox';
    this.stats = null;
    this.statsLoading = true;

    if (!app.forum.attribute('canAccessModeration')) {
      m.route.set('/');
      return;
    }

    this.loadStats();
    this.refresh();
  }

  loadStats() {
    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/moderation-stats',
      })
      .then((payload) => {
        this.stats = payload;
        this.statsLoading = false;
        m.redraw();
      })
      .catch(() => {
        this.statsLoading = false;
        m.redraw();
      });
  }

  chartsView() {
    if (this.statsLoading) {
      return (
        <div className="ModerationPage-charts">
          <LoadingIndicator />
        </div>
      );
    }
    if (!this.stats || !this.stats.days) {
      return null;
    }

    return (
      <div className="ModerationPage-charts">
        <LineChart
          title={extractText(t('panel.chart_activity'))}
          labels={this.stats.days}
          series={[
            { label: extractText(t('panel.chart_discussions')), color: '#6cb2eb', values: this.stats.discussions || [] },
            { label: extractText(t('panel.chart_replies')), color: '#48c78e', values: this.stats.replies || [] },
          ]}
        />
        <LineChart
          title={extractText(t('panel.chart_moderation'))}
          labels={this.stats.days}
          series={[
            { label: extractText(t('panel.chart_reports')), color: '#f14668', values: this.stats.reports || [] },
            { label: extractText(t('panel.chart_warnings')), color: '#ff9f43', values: this.stats.warnings || [] },
          ]}
        />
      </div>
    );
  }

  view() {
    return (
      <div className="ModerationPage">
        <div className="container">
          <div className="ModerationPage-head">
            <h2>{t('panel.title')}</h2>
          </div>
          {this.chartsView()}
          <div className="ModerationPage-sections">
            {this.sectionButton('reports', t('tickets.section_reports'))}
            {this.sectionButton('tickets', t('tickets.section_tickets'))}
          </div>
          {this.section === 'tickets' ? this.ticketsView() : this.reportsView()}
        </div>
      </div>
    );
  }

  sectionButton(section, label) {
    return (
      <Button
        className={'Button' + (this.section === section ? ' Button--primary' : '')}
        onclick={() => {
          this.section = section;
          this.pageNumber = 1;
          if (section === 'tickets') {
            this.refreshTickets();
          } else {
            this.refresh();
          }
        }}
      >
        {label}
      </Button>
    );
  }

  reportsView() {
    const pages = Math.max(1, Math.ceil(this.total / this.perPage));
    const pageButtons = [];
    for (let i = 1; i <= pages; i++) {
      pageButtons.push(this.pageButton(i));
    }

    return [
      <div className="ModerationPage-tabs">
        {this.tabButton('pending')}
        {this.tabButton('resolved')}
        {this.tabButton('rejected')}
      </div>,
      <div className="ModerationPage-panel">
        {this.loading ? (
          <LoadingIndicator />
        ) : this.reports.length ? (
          <div>
            <div className="ModerationPage-tableWrap">
              <table className="ModerationPage-table">
                <thead>
                  <tr>
                    <th>{t('panel.target')}</th>
                    <th>{t('panel.type')}</th>
                    <th>{t('panel.reason')}</th>
                    <th>{t('panel.reporter')}</th>
                    <th>{t('panel.date')}</th>
                    <th>{t('panel.actions')}</th>
                  </tr>
                </thead>
                <tbody>{this.reports.map((report) => this.row(report))}</tbody>
              </table>
            </div>
            {pages > 1 ? <div className="ModerationPage-pager">{pageButtons}</div> : null}
          </div>
        ) : (
          <p>{t('panel.empty')}</p>
        )}
      </div>,
    ];
  }

  ticketsView() {
    const pages = Math.max(1, Math.ceil(this.ticketTotal / this.perPage));
    const pageButtons = [];
    for (let i = 1; i <= pages; i++) {
      pageButtons.push(this.ticketPageButton(i));
    }

    return [
      <div className="ModerationPage-tabs">
        {this.ticketTab('inbox')}
        {this.ticketTab('answered')}
        {this.ticketTab('closed')}
      </div>,
      <div className="ModerationPage-panel">
        {this.loading ? (
          <LoadingIndicator />
        ) : this.tickets.length ? (
          <div>
            <div className="ModerationPage-tableWrap">
              <table className="ModerationPage-table">
                <thead>
                  <tr>
                    <th>{t('tickets.user')}</th>
                    <th>{t('tickets.subject')}</th>
                    <th>{t('tickets.category')}</th>
                    <th>{t('tickets.status')}</th>
                    <th>{t('tickets.last_update')}</th>
                    <th>{t('panel.actions')}</th>
                  </tr>
                </thead>
                <tbody>{this.tickets.map((ticket) => this.ticketRow(ticket))}</tbody>
              </table>
            </div>
            {pages > 1 ? <div className="ModerationPage-pager">{pageButtons}</div> : null}
          </div>
        ) : (
          <p>{t('tickets.empty_inbox')}</p>
        )}
      </div>,
    ];
  }

  ticketTab(status) {
    return (
      <Button
        className={'Button' + (this.ticketStatus === status ? ' Button--primary' : '')}
        onclick={() => {
          this.ticketStatus = status;
          this.pageNumber = 1;
          this.refreshTickets();
        }}
      >
        {t(status === 'inbox' ? 'tickets.inbox' : 'tickets.status_' + status)}
      </Button>
    );
  }

  ticketPageButton(page) {
    return (
      <Button
        className={'Button' + (page === this.pageNumber ? ' Button--primary' : '')}
        onclick={() => {
          this.pageNumber = page;
          this.refreshTickets();
        }}
      >
        {String(page)}
      </Button>
    );
  }

  ticketRow(ticket) {
    const user = ticket.user && ticket.user();
    return (
      <tr>
        <td>{user ? <Link href={app.route.user(user)}>{username(user)}</Link> : null}</td>
        <td>
          {ticket.subject()}
          {ticket.priority() === 'high' ? <div className="ModerationPage-excerpt">{t('tickets.priority_high')}</div> : null}
        </td>
        <td>{ticketCategoryLabel(ticket.category())}</td>
        <td>{statusBadge(ticket.status())}</td>
        <td>{formatDate(ticket.lastRepliedAt() || ticket.createdAt())}</td>
        <td>
          <Link className="Button Button--primary Button--sm" href={app.route('tickets.show', { id: ticket.id() })}>
            {t('tickets.open')}
          </Link>
        </td>
      </tr>
    );
  }

  refreshTickets() {
    this.loading = true;
    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/moderation-tickets',
        params: {
          filter: { status: this.ticketStatus },
          page: { offset: (this.pageNumber - 1) * this.perPage, limit: this.perPage },
          include: 'user,assignedTo',
        },
      })
      .then((payload) => {
        this.ticketTotal = metaTotal(payload);
        this.tickets = app.store.pushPayload(payload);
        this.loading = false;
        if (this.ticketStatus === 'inbox') {
          app.forum.pushAttributes({ openModerationTickets: this.ticketTotal });
        }
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }

  tabButton(status) {
    return (
      <Button
        className={'Button' + (this.status === status ? ' Button--primary' : '')}
        onclick={() => {
          this.status = status;
          this.pageNumber = 1;
          this.refresh();
        }}
      >
        {t('panel.' + status)}
      </Button>
    );
  }

  pageButton(page) {
    return (
      <Button
        className={'Button' + (page === this.pageNumber ? ' Button--primary' : '')}
        onclick={() => {
          this.pageNumber = page;
          this.refresh();
        }}
      >
        {String(page)}
      </Button>
    );
  }

  row(report) {
    const target = report.targetUser();
    const reporter = report.reporter();
    const post = report.post && report.post();
    const discussion = report.discussion && report.discussion();
    let href = null;

    if (post && app.route.post) {
      try {
        href = app.route.post(post);
      } catch (e) {
        href = null;
      }
    }
    if (!href && discussion) {
      href = app.route.discussion(discussion);
    }
    if (!href && target) {
      href = app.route.user(target);
    }

    return (
      <tr>
        <td>{target ? <Link href={app.route.user(target)}>{username(target)}</Link> : null}</td>
        <td>{t('panel.type_' + report.targetType())}</td>
        <td>
          {reasonLabel(report.reason())}
          {report.reasonDetail() ? <div className="ModerationPage-excerpt">{report.reasonDetail()}</div> : null}
        </td>
        <td>{reporter ? username(reporter) : null}</td>
        <td>{formatDate(report.createdAt())}</td>
        <td>
          <Button
            className="Button Button--primary Button--sm"
            onclick={() =>
              app.modal.show(HandleReportModal, {
                report,
                onsaved: () => this.refresh(),
              })
            }
          >
            {t('panel.open')}
          </Button>
          {href ? (
            <Link className="Button Button--sm" href={href}>
              {t('panel.view_content')}
            </Link>
          ) : null}
        </td>
      </tr>
    );
  }

  refresh() {
    this.loading = true;

    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/moderation-reports',
        params: {
          filter: { status: this.status },
          page: { offset: (this.pageNumber - 1) * this.perPage, limit: this.perPage },
          include: 'reporter,targetUser,handledBy,post,discussion',
        },
      })
      .then((payload) => {
        this.total = metaTotal(payload);
        this.reports = app.store.pushPayload(payload);
        this.loading = false;
        if (this.status === 'pending') {
          app.forum.pushAttributes({ pendingModerationReports: this.total });
        }
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
