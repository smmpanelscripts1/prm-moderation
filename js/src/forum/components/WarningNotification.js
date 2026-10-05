import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';
import { t } from '../utils';

export default class WarningNotification extends Notification {
  icon() {
    return 'fas fa-exclamation-triangle';
  }

  href() {
    const user = app.session.user;
    return user ? app.route('user.warnings', { username: user.slug() }) : app.forum.attribute('baseUrl');
  }

  content() {
    const fromUser = this.attrs.notification.fromUser();
    const data = this.attrs.notification.content() || {};
    return t('notifications.warning_received', {
      username: fromUser ? fromUser.displayName() : '',
      points: data.points || 0,
    });
  }
}
