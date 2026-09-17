import app from 'flarum/admin/app';

app.initializers.add('prm-moderation', () => {
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
    );
});
