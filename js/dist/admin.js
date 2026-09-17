(function () {
var app = flarum.core.compat['admin/app'] || flarum.core.compat.app;

app.initializers.add('prm-moderation', function () {
  app.extensionData
    .for('prm-moderation')
    .registerPermission(
      {
        icon: 'fas fa-shield-alt',
        label: app.translator.trans('prm-moderation.admin.permissions.access_label'),
        permission: 'moderation.access',
      },
      'moderate'
    )
    .registerPermission(
      {
        icon: 'fas fa-flag',
        label: app.translator.trans('prm-moderation.admin.permissions.report_label'),
        permission: 'moderation.report',
      },
      'start'
    )
    .registerPermission(
      {
        icon: 'fas fa-life-ring',
        label: app.translator.trans('prm-moderation.admin.permissions.ticket_label'),
        permission: 'moderation.ticket',
      },
      'start'
    );
});

module.exports = {};
})();
