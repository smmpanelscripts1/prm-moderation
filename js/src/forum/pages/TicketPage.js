import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Avatar from 'flarum/common/components/Avatar';
import Stream from 'flarum/common/utils/Stream';
import extractText from 'flarum/common/utils/extractText';
import username from 'flarum/common/helpers/username';
import humanTime from 'flarum/common/helpers/humanTime';
import { statusBadge } from '../components/LineChart';
import { t, ticketCategoryLabel } from '../utils';

export default class TicketPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);
    this.ticket = null;
    this.loading = true;
    this.reply = Stream('');
    this.saving = false;
    this.load();
  }

  load() {
    this.loading = true;
    app.store
      .find('moderation-tickets', m.route.param('id'), { include: 'user,assignedTo,closedBy,replies,replies.user' })
      .then((ticket) => {
        this.ticket = ticket;
        this.loading = false;
        app.setTitle(ticket.subject());
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }

  view() {
    if (this.loading || !this.ticket) {
      return (
        <div className="TicketPage">
          <div className="container">{this.loading ? <LoadingIndicator /> : <p>{t('tickets.empty')}</p>}</div>
        </div>
      );
    }

    const ticket = this.ticket;
    const user = ticket.user && ticket.user();
    const assigned = ticket.assignedTo && ticket.assignedTo();
    const replies = (ticket.replies && ticket.replies()) || [];
    const backHref = app.forum.attribute('canAccessModeration') ? app.route('moderation') : app.route('tickets');

    return (
      <div className="TicketPage">
        <div className="container">
          <Link href={backHref} className="TicketsPage-back">
            ←{' '}
            {extractText(
              app.forum.attribute('canAccessModeration') ? t('tickets.panel_title') : t('tickets.title')
            )}
          </Link>
          <div className="TicketPage-head">
            <h2>{ticket.subject()}</h2>
            <div className="TicketPage-meta">
              {statusBadge(ticket.status())}
              {ticket.priority() === 'high' ? (
                <span className="TicketStatus TicketStatus--high">{t('tickets.priority_high')}</span>
              ) : null}
              {' · '}
              {ticketCategoryLabel(ticket.category())}
              {user ? [' · ', username(user)] : null}
              {assigned ? [' · ', t('tickets.assigned', { username: assigned.displayName() })] : null}
            </div>
          </div>
          <div className="TicketPage-thread">
            {replies.map((reply) => {
              const author = reply.user && reply.user();
              return (
                <div className={'TicketReply' + (reply.isStaff() ? ' TicketReply--staff' : '')}>
                  <div className="TicketReply-meta">
                    {author ? <Avatar user={author} /> : null}
                    {author ? username(author) : null}
                    {reply.isStaff() ? <span className="TicketReply-staffBadge">{t('tickets.staff_badge')}</span> : null}
                    <span className="TicketPage-meta">{humanTime(reply.createdAt())}</span>
                  </div>
                  <div className="TicketReply-body">{reply.content()}</div>
                </div>
              );
            })}
          </div>
          {ticket.canReply() ? (
            <div className="TicketPage-composer">
              <label>{t('tickets.reply')}</label>
              <textarea className="FormControl" bidi={this.reply} />
              <div className="TicketPage-actions">
                <Button className="Button Button--primary" loading={this.saving} onclick={this.sendReply.bind(this)}>
                  {t('tickets.send_reply')}
                </Button>
                {ticket.canClose() ? (
                  <Button className="Button" onclick={this.closeTicket.bind(this)}>
                    {t('tickets.close')}
                  </Button>
                ) : null}
              </div>
            </div>
          ) : (
            <div className="TicketPage-composer">
              <p>{t('tickets.closed')}</p>
              {ticket.canReopen() ? (
                <Button className="Button Button--primary" onclick={this.reopenTicket.bind(this)}>
                  {t('tickets.reopen')}
                </Button>
              ) : null}
              {ticket.canClose() ? (
                <Button className="Button" onclick={this.closeTicket.bind(this)}>
                  {t('tickets.close')}
                </Button>
              ) : null}
            </div>
          )}
        </div>
      </div>
    );
  }

  sendReply() {
    if (!this.reply() || this.saving) {
      return;
    }
    this.saving = true;
    app.store
      .createRecord('moderation-ticket-replies')
      .save({
        content: this.reply(),
        relationships: { ticket: this.ticket },
      })
      .then(() => {
        this.reply('');
        this.saving = false;
        this.load();
      })
      .catch(() => {
        this.saving = false;
        m.redraw();
      });
  }

  closeTicket() {
    this.ticket.save({ status: 'closed' }).then(() => this.load());
  }

  reopenTicket() {
    this.ticket.save({ status: 'open' }).then(() => this.load());
  }
}
