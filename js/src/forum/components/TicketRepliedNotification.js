import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';
import { t } from '../utils';

export default class TicketRepliedNotification extends Notification {
  icon() {
    return 'fas fa-life-ring';
  }

  href() {
    const subject = this.attrs.notification.subject && this.attrs.notification.subject();
    if (subject && subject.id) {
      return app.route('tickets.show', { id: subject.id() });
    }
    const data = this.attrs.notification.content() || {};
    return data.ticketId ? app.route('tickets.show', { id: data.ticketId }) : app.forum.attribute('baseUrl');
  }

  content() {
    const fromUser = this.attrs.notification.fromUser();
    const data = this.attrs.notification.content() || {};
    return t(data.isStaff ? 'notifications.ticket_replied' : 'notifications.ticket_replied_staff', {
      username: fromUser ? fromUser.displayName() : '',
    });
  }
}
