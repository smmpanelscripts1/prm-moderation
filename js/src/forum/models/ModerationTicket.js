import Model from 'flarum/common/Model';

export default class ModerationTicket extends Model {}

Object.assign(ModerationTicket.prototype, {
  subject: Model.attribute('subject'),
  category: Model.attribute('category'),
  priority: Model.attribute('priority'),
  status: Model.attribute('status'),
  createdAt: Model.attribute('createdAt', Model.transformDate),
  lastRepliedAt: Model.attribute('lastRepliedAt', Model.transformDate),
  closedAt: Model.attribute('closedAt', Model.transformDate),
  canReply: Model.attribute('canReply'),
  canClose: Model.attribute('canClose'),
  canReopen: Model.attribute('canReopen'),
  user: Model.hasOne('user'),
  assignedTo: Model.hasOne('assignedTo'),
  closedBy: Model.hasOne('closedBy'),
  replies: Model.hasMany('replies'),
});
