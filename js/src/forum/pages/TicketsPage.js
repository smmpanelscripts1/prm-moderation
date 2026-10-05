import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import humanTime from 'flarum/common/helpers/humanTime';
import CreateTicketModal from '../components/CreateTicketModal';
import { statusBadge } from '../components/LineChart';
import { t, ticketCategoryLabel, metaTotal } from '../utils';

export default class TicketsPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);
    app.setTitle(t('tickets.title'));
    this.filter = 'open';
    this.pageNumber = 1;
    this.perPage = 20;
    this.total = 0;
    this.loading = true;
    this.tickets = [];

    if (!app.session.user) {
      m.route.set('/');
      return;
    }

    this.refresh();
  }

  view() {
    const pages = Math.max(1, Math.ceil(this.total / this.perPage));
    const pager = [];
    for (let i = 1; i <= pages; i++) {
      pager.push(this.pageButton(i));
    }

    return (
      <div className="TicketsPage">
        <div className="container">
          <div className="TicketsPage-head">
            <h2>{t('tickets.title')}</h2>
            {app.forum.attribute('canCreateTicket') ? (
              <Button
                className="Button Button--primary"
                icon="fas fa-plus"
                onclick={() => app.modal.show(CreateTicketModal)}
              >
                {t('tickets.create')}
              </Button>
            ) : null}
          </div>
          <div className="ModerationPage-tabs">
            {this.filterButton('open', 'tickets.mine_open')}
            {this.filterButton('closed', 'tickets.mine_closed')}
          </div>
          {this.loading ? (
            <LoadingIndicator />
          ) : this.tickets.length ? (
            <div>
              <div className="TicketsPage-list">{this.tickets.map((ticket) => this.ticketItem(ticket))}</div>
              {pages > 1 ? <div className="ModerationPage-pager">{pager}</div> : null}
            </div>
          ) : (
            <p>{t('tickets.empty')}</p>
          )}
        </div>
      </div>
    );
  }

  filterButton(filter, key) {
    return (
      <Button
        className={'Button' + (this.filter === filter ? ' Button--primary' : '')}
        onclick={() => {
          this.filter = filter;
          this.pageNumber = 1;
          this.refresh();
        }}
      >
        {t(key)}
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

  ticketItem(ticket) {
    return (
      <Link className="TicketsPage-item" href={app.route('tickets.show', { id: ticket.id() })}>
        <div>
          <div className="TicketsPage-itemTitle">{ticket.subject()}</div>
          <div className="TicketsPage-itemMeta">
            {ticketCategoryLabel(ticket.category())}
            {' · '}
            {humanTime(ticket.lastRepliedAt() || ticket.createdAt())}
          </div>
        </div>
        <div>
          {ticket.priority() === 'high' ? (
            <span className="TicketStatus TicketStatus--high">{t('tickets.priority_high')}</span>
          ) : null}
          {statusBadge(ticket.status())}
        </div>
      </Link>
    );
  }

  refresh() {
    this.loading = true;
    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/moderation-tickets',
        params: {
          filter: { status: this.filter },
          page: { offset: (this.pageNumber - 1) * this.perPage, limit: this.perPage },
          include: 'user,assignedTo',
        },
      })
      .then((payload) => {
        this.total = metaTotal(payload);
        this.tickets = app.store.pushPayload(payload);
        this.loading = false;
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
