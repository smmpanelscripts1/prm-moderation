import Model from 'flarum/common/Model';

export default class ModerationWarning extends Model {}

Object.assign(ModerationWarning.prototype, {
  points: Model.attribute('points'),
  reason: Model.attribute('reason'),
  comment: Model.attribute('comment'),
  createdAt: Model.attribute('createdAt', Model.transformDate),
  user: Model.hasOne('user'),
  actor: Model.hasOne('actor'),
  discussion: Model.hasOne('discussion'),
});
