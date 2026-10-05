import Model from 'flarum/common/Model';

export default class ModerationReport extends Model {}

Object.assign(ModerationReport.prototype, {
  targetType: Model.attribute('targetType'),
  reason: Model.attribute('reason'),
  reasonDetail: Model.attribute('reasonDetail'),
  status: Model.attribute('status'),
  createdAt: Model.attribute('createdAt', Model.transformDate),
  handledAt: Model.attribute('handledAt', Model.transformDate),
  reporter: Model.hasOne('reporter'),
  targetUser: Model.hasOne('targetUser'),
  handledBy: Model.hasOne('handledBy'),
  post: Model.hasOne('post'),
  discussion: Model.hasOne('discussion'),
});
