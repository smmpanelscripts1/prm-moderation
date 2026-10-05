import app from 'flarum/forum/app';

export function t(key, params) {
  return app.translator.trans('prm-moderation.forum.' + key, params || {});
}

export function formatDate(value) {
  if (!value) {
    return '';
  }
  const date = value instanceof Date ? value : new Date(value);
  if (isNaN(date.getTime())) {
    return '';
  }
  const dd = String(date.getDate()).padStart(2, '0');
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  return dd + '-' + mm + '-' + date.getFullYear();
}

export function reasonLabel(reason) {
  return t('report.reason_' + (reason || 'other'));
}

export function ticketStatusLabel(status) {
  return t('tickets.status_' + (status || 'open'));
}

export function ticketCategoryLabel(category) {
  return t('tickets.category_' + (category || 'other'));
}

export function metaTotal(payload) {
  return (payload.meta && payload.meta.page && payload.meta.page.total) || 0;
}

export function canSeeWarnings(user) {
  if (!user || !app.session.user) {
    return false;
  }
  return app.forum.attribute('canAccessModeration') || app.session.user.id() === user.id();
}

export function canReportTarget(user) {
  return !!(user && app.forum.attribute('canReport') && (!app.session.user || app.session.user.id() !== user.id()));
}
