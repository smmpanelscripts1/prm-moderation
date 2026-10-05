import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';
import UserPageResolver from 'flarum/forum/resolvers/UserPageResolver';
import ModerationReport from './models/ModerationReport';
import ModerationWarning from './models/ModerationWarning';
import ModerationTicket from './models/ModerationTicket';
import ModerationTicketReply from './models/ModerationTicketReply';
import ModerationPage from './pages/ModerationPage';
import TicketsPage from './pages/TicketsPage';
import TicketPage from './pages/TicketPage';
import UserWarningsPage from './pages/UserWarningsPage';
import WarningNotification from './components/WarningNotification';
import TicketRepliedNotification from './components/TicketRepliedNotification';

export default [
  new Extend.Store()
    .add('moderation-reports', ModerationReport)
    .add('moderation-warnings', ModerationWarning)
    .add('moderation-tickets', ModerationTicket)
    .add('moderation-ticket-replies', ModerationTicketReply),

  new Extend.Model(User)
    .attribute('warningPoints')
    .attribute('warningCount')
    .attribute('canWarn')
    .attribute('canReportUser'),

  new Extend.Routes()
    .add('moderation', '/moderation', ModerationPage)
    .add('tickets', '/tickets', TicketsPage)
    .add('tickets.show', '/tickets/:id', TicketPage)
    .add('user.warnings', '/u/:username/warnings', UserWarningsPage, UserPageResolver),

  new Extend.Notification()
    .add('moderationWarningReceived', WarningNotification)
    .add('moderationTicketReplied', TicketRepliedNotification),
];
