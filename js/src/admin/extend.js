import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

function t(key) {
  return app.translator.trans('prm-moderation.admin.settings.' + key);
}

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
    )
    .setting(() => ({
      setting: 'prm-moderation.spam.enabled',
      type: 'boolean',
      label: t('enabled_label'),
      help: t('enabled_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.monitor_all_users',
      type: 'boolean',
      label: t('monitor_all_users_label'),
      help: t('monitor_all_users_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.monitor_post_count',
      type: 'number',
      label: t('monitor_post_count_label'),
      help: t('monitor_post_count_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.monitor_hours_old',
      type: 'number',
      label: t('monitor_hours_old_label'),
      help: t('monitor_hours_old_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.detect_urls',
      type: 'boolean',
      label: t('detect_urls_label'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.detect_emails',
      type: 'boolean',
      label: t('detect_emails_label'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.detect_phones',
      type: 'boolean',
      label: t('detect_phones_label'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.detect_blocked_words',
      type: 'boolean',
      label: t('detect_blocked_words_label'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.scan_usernames',
      type: 'boolean',
      label: t('scan_usernames_label'),
      help: t('scan_usernames_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.scan_nicknames',
      type: 'boolean',
      label: t('scan_nicknames_label'),
      help: t('scan_nicknames_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.scan_bios',
      type: 'boolean',
      label: t('scan_bios_label'),
      help: t('scan_bios_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.allowed_domains',
      type: 'textarea',
      label: t('allowed_domains_label'),
      help: t('allowed_domains_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.blocked_words',
      type: 'textarea',
      label: t('blocked_words_label'),
      help: t('blocked_words_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.flag_threshold',
      type: 'number',
      label: t('flag_threshold_label'),
      help: t('flag_threshold_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.spam_threshold',
      type: 'number',
      label: t('spam_threshold_label'),
      help: t('spam_threshold_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.auto_flag',
      type: 'boolean',
      label: t('auto_flag_label'),
      help: t('auto_flag_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.auto_unapprove',
      type: 'boolean',
      label: t('auto_unapprove_label'),
      help: t('auto_unapprove_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.auto_report',
      type: 'boolean',
      label: t('auto_report_label'),
      help: t('auto_report_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.system_user_id',
      type: 'number',
      label: t('system_user_id_label'),
      help: t('system_user_id_help'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.hide_posts_on_mark',
      type: 'boolean',
      label: t('hide_posts_on_mark_label'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.hide_discussions_on_mark',
      type: 'boolean',
      label: t('hide_discussions_on_mark_label'),
    }))
    .setting(() => ({
      setting: 'prm-moderation.spam.suspend_on_mark',
      type: 'boolean',
      label: t('suspend_on_mark_label'),
    })),
];
