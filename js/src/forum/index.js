import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import LinkButton from 'flarum/common/components/LinkButton';
import Button from 'flarum/common/components/Button';
import HeaderSecondary from 'flarum/forum/components/HeaderSecondary';
import UserControls from 'flarum/forum/utils/UserControls';
import PostControls from 'flarum/forum/utils/PostControls';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import UserCard from 'flarum/forum/components/UserCard';
import ReportModal from './components/ReportModal';
import WarnModal from './components/WarnModal';
import { t, canSeeWarnings, canReportTarget } from './utils';

export { default as extend } from './extend';

app.initializers.add('prm-moderation', () => {
  extend(HeaderSecondary.prototype, 'items', function (items) {
    if (app.forum.attribute('canAccessModeration')) {
      const reportCount = app.forum.attribute('pendingModerationReports') || 0;
      const ticketCount = app.forum.attribute('openModerationTickets') || 0;
      const count = reportCount + ticketCount;
      items.add(
        'moderation',
        <LinkButton href={app.route('moderation')} icon="fas fa-shield-alt" className="Button Button--link">
          {t('header')}
          {count ? <span className="ModerationBadge">{String(count)}</span> : null}
        </LinkButton>,
        12
      );
    }
    if (app.session.user && app.forum.attribute('canCreateTicket')) {
      const waiting = app.forum.attribute('waitingSupportTickets') || 0;
      items.add(
        'tickets',
        <LinkButton href={app.route('tickets')} icon="fas fa-life-ring" className="Button Button--link">
          {t('header_tickets')}
          {waiting ? <span className="ModerationBadge">{String(waiting)}</span> : null}
        </LinkButton>,
        11
      );
    }
  });

  extend('flarum/forum/components/UserPage', 'navItems', function (items) {
    const user = this.user;
    if (!canSeeWarnings(user)) {
      return;
    }
    items.add(
      'warnings',
      <LinkButton href={app.route('user.warnings', { username: user.slug() })} icon="fas fa-exclamation-triangle">
        {t('nav_warnings')}
      </LinkButton>,
      80
    );
  });

  extend(UserCard.prototype, 'infoItems', function (items) {
    const user = this.attrs.user;
    if (!canSeeWarnings(user) || !user.warningPoints()) {
      return;
    }
    items.add('warning-points', <span className="UserCard-warningPoints">{t('warnings.points', { points: user.warningPoints() })}</span>, 80);
  });

  extend(UserControls, 'userControls', function (items, user) {
    if (user && user.canReportUser && user.canReportUser()) {
      items.add(
        'moderation-report',
        <Button icon="fas fa-flag" onclick={() => app.modal.show(ReportModal, { user })}>
          {t('user_controls.report')}
        </Button>,
        70
      );
    }
  });

  extend(UserControls, 'moderationControls', function (items, user) {
    if (user && user.canWarn && user.canWarn()) {
      items.add(
        'moderation-warn',
        <Button icon="fas fa-exclamation-triangle" onclick={() => app.modal.show(WarnModal, { user })}>
          {t('user_controls.warn')}
        </Button>,
        90
      );
    }
  });

  extend(PostControls, 'userControls', function (items, post) {
    const user = post.user && post.user();
    if (!canReportTarget(user)) {
      return;
    }
    items.add(
      'moderation-report',
      <Button icon="fas fa-flag" onclick={() => app.modal.show(ReportModal, { post, user })}>
        {t('post_controls.report')}
      </Button>
    );
  });

  extend(PostControls, 'moderationControls', function (items, post) {
    const user = post.user && post.user();
    if (!app.forum.attribute('canAccessModeration') || !user || (app.session.user && app.session.user.id() === user.id())) {
      return;
    }
    items.add(
      'moderation-warn',
      <Button icon="fas fa-exclamation-triangle" onclick={() => app.modal.show(WarnModal, { user, post })}>
        {t('post_controls.warn')}
      </Button>
    );
  });

  extend(DiscussionControls, 'userControls', function (items, discussion) {
    const user = discussion.user && discussion.user();
    if (!canReportTarget(user)) {
      return;
    }
    items.add(
      'moderation-report',
      <Button icon="fas fa-flag" onclick={() => app.modal.show(ReportModal, { discussion, user })}>
        {t('discussion_controls.report')}
      </Button>
    );
  });

  extend(DiscussionControls, 'moderationControls', function (items, discussion) {
    const user = discussion.user && discussion.user();
    if (!app.forum.attribute('canAccessModeration') || !user || (app.session.user && app.session.user.id() === user.id())) {
      return;
    }
    items.add(
      'moderation-warn',
      <Button icon="fas fa-exclamation-triangle" onclick={() => app.modal.show(WarnModal, { user, discussion })}>
        {t('discussion_controls.warn')}
      </Button>
    );
  });

  extend('flarum/forum/components/NotificationGrid', 'notificationTypes', function (items) {
    items.add('moderationWarningReceived', {
      name: 'moderationWarningReceived',
      icon: 'fas fa-exclamation-triangle',
      label: t('notifications.notify_warning_label'),
    });
    items.add('moderationTicketReplied', {
      name: 'moderationTicketReplied',
      icon: 'fas fa-life-ring',
      label: t('notifications.notify_ticket_label'),
    });
  });
});
