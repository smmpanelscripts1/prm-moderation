import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

export default [
  new Extend.Admin()
    .permission(
      () => ({
        icon: 'fas fa-shield-alt',
        label: app.translator.trans('prm-moderation.admin.permissions.access_label'),
        permission: 'moderation.access',
      }),
      'moderate'
    )
    .permission(
      () => ({
        icon: 'fas fa-flag',
        label: app.translator.trans('prm-moderation.admin.permissions.report_label'),
        permission: 'moderation.report',
      }),
      'start'
    )
    .permission(
      () => ({
        icon: 'fas fa-life-ring',
        label: app.translator.trans('prm-moderation.admin.permissions.ticket_label'),
        permission: 'moderation.ticket',
      }),
      'start'
    ),
];
