import Model from 'flarum/common/Model';

export default class ModerationTicketReply extends Model {}

Object.assign(ModerationTicketReply.prototype, {
  content: Model.attribute('content'),
  isStaff: Model.attribute('isStaff'),
  createdAt: Model.attribute('createdAt', Model.transformDate),
  user: Model.hasOne('user'),
  ticket: Model.hasOne('ticket'),
});
