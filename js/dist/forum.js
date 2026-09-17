(function () {
var app = flarum.core.compat['forum/app'] || flarum.core.compat.app;
var extendMod = flarum.core.compat['common/extend'] || {};
var extend = extendMod.extend;
var Model = flarum.core.compat['common/Model'];
var User = flarum.core.compat['common/models/User'];
var UserPage = flarum.core.compat['forum/components/UserPage'];
var Page = flarum.core.compat['common/components/Page'];
var LinkButton = flarum.core.compat['common/components/LinkButton'];
var Button = flarum.core.compat['common/components/Button'];
var Select = flarum.core.compat['common/components/Select'];
var Link = flarum.core.compat['common/components/Link'];
var Modal = flarum.core.compat['common/components/Modal'];
var LoadingIndicator = flarum.core.compat['common/components/LoadingIndicator'];
var Checkbox = flarum.core.compat['common/components/Checkbox'];
var HeaderSecondary = flarum.core.compat['forum/components/HeaderSecondary'];
var UserControls = flarum.core.compat['forum/utils/UserControls'];
var PostControls = flarum.core.compat['forum/utils/PostControls'];
var DiscussionControls = flarum.core.compat['forum/utils/DiscussionControls'];
var NotificationGrid = flarum.core.compat['forum/components/NotificationGrid'];
var Notification = flarum.core.compat['forum/components/Notification'];
var UserCard = flarum.core.compat['forum/components/UserCard'];
var Stream = flarum.core.compat['common/utils/Stream'];
var username = flarum.core.compat['common/helpers/username'];
var extractText = flarum.core.compat['common/utils/extractText'];
var humanTime = flarum.core.compat['common/helpers/humanTime'];
var avatar = flarum.core.compat['common/helpers/avatar'];
var m = window.m;

function t(key, params) {
  return app.translator.trans('prm-moderation.forum.' + key, params || {});
}

function formatDate(value) {
  if (!value) {
    return '';
  }
  var date = value instanceof Date ? value : new Date(value);
  if (isNaN(date.getTime())) {
    return '';
  }
  var dd = String(date.getDate()).padStart(2, '0');
  var mm = String(date.getMonth() + 1).padStart(2, '0');
  return dd + '-' + mm + '-' + date.getFullYear();
}

function reasonLabel(reason) {
  return t('report.reason_' + (reason || 'other'));
}

class ModerationReport extends Model {}
ModerationReport.prototype.targetType = Model.attribute('targetType');
ModerationReport.prototype.reason = Model.attribute('reason');
ModerationReport.prototype.reasonDetail = Model.attribute('reasonDetail');
ModerationReport.prototype.status = Model.attribute('status');
ModerationReport.prototype.createdAt = Model.attribute('createdAt', Model.transformDate);
ModerationReport.prototype.handledAt = Model.attribute('handledAt', Model.transformDate);
ModerationReport.prototype.reporter = Model.hasOne('reporter');
ModerationReport.prototype.targetUser = Model.hasOne('targetUser');
ModerationReport.prototype.handledBy = Model.hasOne('handledBy');
ModerationReport.prototype.post = Model.hasOne('post');
ModerationReport.prototype.discussion = Model.hasOne('discussion');

class ModerationWarning extends Model {}
ModerationWarning.prototype.points = Model.attribute('points');
ModerationWarning.prototype.reason = Model.attribute('reason');
ModerationWarning.prototype.comment = Model.attribute('comment');
ModerationWarning.prototype.createdAt = Model.attribute('createdAt', Model.transformDate);
ModerationWarning.prototype.user = Model.hasOne('user');
ModerationWarning.prototype.actor = Model.hasOne('actor');
ModerationWarning.prototype.discussion = Model.hasOne('discussion');

class ModerationTicket extends Model {}
ModerationTicket.prototype.subject = Model.attribute('subject');
ModerationTicket.prototype.category = Model.attribute('category');
ModerationTicket.prototype.priority = Model.attribute('priority');
ModerationTicket.prototype.status = Model.attribute('status');
ModerationTicket.prototype.createdAt = Model.attribute('createdAt', Model.transformDate);
ModerationTicket.prototype.lastRepliedAt = Model.attribute('lastRepliedAt', Model.transformDate);
ModerationTicket.prototype.closedAt = Model.attribute('closedAt', Model.transformDate);
ModerationTicket.prototype.canReply = Model.attribute('canReply');
ModerationTicket.prototype.canClose = Model.attribute('canClose');
ModerationTicket.prototype.canReopen = Model.attribute('canReopen');
ModerationTicket.prototype.user = Model.hasOne('user');
ModerationTicket.prototype.assignedTo = Model.hasOne('assignedTo');
ModerationTicket.prototype.closedBy = Model.hasOne('closedBy');
ModerationTicket.prototype.replies = Model.hasMany('replies');

class ModerationTicketReply extends Model {}
ModerationTicketReply.prototype.content = Model.attribute('content');
ModerationTicketReply.prototype.isStaff = Model.attribute('isStaff');
ModerationTicketReply.prototype.createdAt = Model.attribute('createdAt', Model.transformDate);
ModerationTicketReply.prototype.user = Model.hasOne('user');
ModerationTicketReply.prototype.ticket = Model.hasOne('ticket');

function ticketStatusLabel(status) {
  return t('tickets.status_' + (status || 'open'));
}

function ticketCategoryLabel(category) {
  return t('tickets.category_' + (category || 'other'));
}

function statusBadge(status) {
  return m('span.TicketStatus.TicketStatus--' + (status || 'open'), ticketStatusLabel(status));
}

class TicketRepliedNotification extends Notification {
  icon() {
    return 'fas fa-life-ring';
  }

  href() {
    var subject = this.attrs.notification.subject && this.attrs.notification.subject();
    if (subject && subject.id) {
      return app.route('tickets.show', { id: subject.id() });
    }
    var data = this.attrs.notification.content() || {};
    return data.ticketId ? app.route('tickets.show', { id: data.ticketId }) : app.forum.attribute('baseUrl');
  }

  content() {
    var fromUser = this.attrs.notification.fromUser();
    var data = this.attrs.notification.content() || {};
    return t(data.isStaff ? 'notifications.ticket_replied' : 'notifications.ticket_replied_staff', {
      username: fromUser ? fromUser.displayName() : '',
    });
  }
}

class CreateTicketModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);
    this.subject = Stream('');
    this.category = Stream('');
    this.priority = Stream('normal');
    this.message = Stream('');
  }

  className() {
    return 'CreateTicketModal';
  }

  title() {
    return t('tickets.create');
  }

  content() {
    var self = this;
    return m('div.Modal-body', m('div.Form', [
      m('div.Form-group', [
        m('label', t('tickets.category')),
        m(Select, {
          value: this.category(),
          onchange: this.category,
          options: {
            '': t('tickets.category_placeholder'),
            account: t('tickets.category_account'),
            payment: t('tickets.category_payment'),
            technical: t('tickets.category_technical'),
            appeal: t('tickets.category_appeal'),
            other: t('tickets.category_other'),
          },
        }),
      ]),
      m('div.Form-group', [
        m('label', t('tickets.priority')),
        m(Select, {
          value: this.priority(),
          onchange: this.priority,
          options: {
            normal: t('tickets.priority_normal'),
            high: t('tickets.priority_high'),
          },
        }),
      ]),
      m('div.Form-group', [
        m('label', t('tickets.subject')),
        m('input.FormControl', {
          value: this.subject(),
          oninput: function (e) {
            self.subject(e.target.value);
          },
        }),
      ]),
      m('div.Form-group', [
        m('label', t('tickets.message')),
        m('textarea.FormControl', {
          value: this.message(),
          oninput: function (e) {
            self.message(e.target.value);
          },
        }),
      ]),
      m(
        'div.Form-group',
        m(Button, { className: 'Button Button--primary', type: 'submit', loading: this.loading }, t('tickets.submit'))
      ),
    ]));
  }

  onsubmit(e) {
    e.preventDefault();
    var self = this;
    this.loading = true;
    app.store
      .createRecord('moderation-tickets')
      .save({
        subject: this.subject(),
        category: this.category(),
        priority: this.priority(),
        content: this.message(),
      })
      .then(function (ticket) {
        app.alerts.show({ type: 'success' }, t('tickets.success'));
        self.hide();
        m.route.set(app.route('tickets.show', { id: ticket.id() }));
      })
      .catch(function () {
        self.loading = false;
        m.redraw();
      });
  }
}

class TicketsPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);
    app.setTitle(t('tickets.title'));
    this.filter = 'open';
    this.pageNumber = 1;
    this.perPage = 20;
    this.total = 0;
    this.loading = true;
    this.tickets = [];

    if (!app.session.user) {
      m.route.set('/');
      return;
    }

    this.refresh();
  }

  view() {
    var self = this;
    var pages = Math.max(1, Math.ceil(this.total / this.perPage));
    var pager = [];
    var i;
    for (i = 1; i <= pages; i++) {
      pager.push(this.pageButton(i));
    }

    return m('div.TicketsPage', m('div.container', [
      m('div.TicketsPage-head', [
        m('h2', t('tickets.title')),
        app.forum.attribute('canCreateTicket')
          ? m(
              Button,
              {
                className: 'Button Button--primary',
                icon: 'fas fa-plus',
                onclick: function () {
                  app.modal.show(CreateTicketModal);
                },
              },
              t('tickets.create')
            )
          : null,
      ]),
      m('div.ModerationPage-tabs', [
        this.filterButton('open', 'tickets.mine_open'),
        this.filterButton('closed', 'tickets.mine_closed'),
      ]),
      this.loading
        ? m(LoadingIndicator)
        : this.tickets.length
          ? [
              m(
                'div.TicketsPage-list',
                this.tickets.map(function (ticket) {
                  return self.ticketItem(ticket);
                })
              ),
              pages > 1 ? m('div.ModerationPage-pager', pager) : null,
            ]
          : m('p', t('tickets.empty')),
    ]));
  }

  filterButton(filter, key) {
    var self = this;
    return m(
      Button,
      {
        className: 'Button' + (this.filter === filter ? ' Button--primary' : ''),
        onclick: function () {
          self.filter = filter;
          self.pageNumber = 1;
          self.refresh();
        },
      },
      t(key)
    );
  }

  pageButton(page) {
    var self = this;
    return m(
      Button,
      {
        className: 'Button' + (page === this.pageNumber ? ' Button--primary' : ''),
        onclick: function () {
          self.pageNumber = page;
          self.refresh();
        },
      },
      String(page)
    );
  }

  ticketItem(ticket) {
    return m(
      Link,
      { className: 'TicketsPage-item', href: app.route('tickets.show', { id: ticket.id() }) },
      [
        m('div', [
          m('div.TicketsPage-itemTitle', ticket.subject()),
          m('div.TicketsPage-itemMeta', [
            ticketCategoryLabel(ticket.category()),
            ' · ',
            humanTime(ticket.lastRepliedAt() || ticket.createdAt()),
          ]),
        ]),
        m('div', [
          ticket.priority() === 'high' ? m('span.TicketStatus.TicketStatus--high', t('tickets.priority_high')) : null,
          statusBadge(ticket.status()),
        ]),
      ]
    );
  }

  refresh() {
    var self = this;
    this.loading = true;
    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/moderation-tickets',
        params: {
          filter: { status: this.filter },
          page: { offset: (this.pageNumber - 1) * this.perPage, limit: this.perPage },
          include: 'user,assignedTo',
        },
      })
      .then(function (payload) {
        self.total = (payload.meta && payload.meta.total) || 0;
        self.tickets = app.store.pushPayload(payload);
        self.loading = false;
        m.redraw();
      })
      .catch(function () {
        self.loading = false;
        m.redraw();
      });
  }
}

class TicketPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);
    this.ticket = null;
    this.loading = true;
    this.reply = Stream('');
    this.saving = false;
    this.load();
  }

  load() {
    var self = this;
    this.loading = true;
    app.store
      .find('moderation-tickets', m.route.param('id'), { include: 'user,assignedTo,closedBy,replies,replies.user' })
      .then(function (ticket) {
        self.ticket = ticket;
        self.loading = false;
        app.setTitle(ticket.subject());
        m.redraw();
      })
      .catch(function () {
        self.loading = false;
        m.redraw();
      });
  }

  view() {
    if (this.loading || !this.ticket) {
      return m('div.TicketPage', m('div.container', this.loading ? m(LoadingIndicator) : m('p', t('tickets.empty'))));
    }

    var ticket = this.ticket;
    var user = ticket.user && ticket.user();
    var assigned = ticket.assignedTo && ticket.assignedTo();
    var replies = (ticket.replies && ticket.replies()) || [];
    var self = this;
    var backHref = app.forum.attribute('canAccessModeration') ? app.route('moderation') : app.route('tickets');

    return m('div.TicketPage', m('div.container', [
      m(
        Link,
        { href: backHref, className: 'TicketsPage-back' },
        '← ' + extractText(app.forum.attribute('canAccessModeration') ? t('tickets.panel_title') : t('tickets.title'))
      ),
      m('div.TicketPage-head', [
        m('h2', ticket.subject()),
        m('div.TicketPage-meta', [
          statusBadge(ticket.status()),
          ticket.priority() === 'high' ? m('span.TicketStatus.TicketStatus--high', t('tickets.priority_high')) : null,
          ' · ',
          ticketCategoryLabel(ticket.category()),
          user ? [' · ', username(user)] : null,
          assigned ? [' · ', t('tickets.assigned', { username: assigned.displayName() })] : null,
        ]),
      ]),
      m(
        'div.TicketPage-thread',
        replies.map(function (reply) {
          var author = reply.user && reply.user();
          return m('div.TicketReply' + (reply.isStaff() ? '.TicketReply--staff' : ''), [
            m('div.TicketReply-meta', [
              author ? avatar(author) : null,
              author ? username(author) : null,
              reply.isStaff() ? m('span.TicketReply-staffBadge', t('tickets.staff_badge')) : null,
              m('span.TicketPage-meta', humanTime(reply.createdAt())),
            ]),
            m('div.TicketReply-body', reply.content()),
          ]);
        })
      ),
      ticket.canReply()
        ? m('div.TicketPage-composer', [
            m('label', t('tickets.reply')),
            m('textarea.FormControl', {
              value: this.reply(),
              oninput: function (e) {
                self.reply(e.target.value);
              },
            }),
            m('div.TicketPage-actions', [
              m(
                Button,
                {
                  className: 'Button Button--primary',
                  loading: this.saving,
                  onclick: this.sendReply.bind(this),
                },
                t('tickets.send_reply')
              ),
              ticket.canClose()
                ? m(
                    Button,
                    { className: 'Button', onclick: this.closeTicket.bind(this) },
                    t('tickets.close')
                  )
                : null,
            ]),
          ])
        : m('div.TicketPage-composer', [
            m('p', t('tickets.closed')),
            ticket.canReopen()
              ? m(
                  Button,
                  { className: 'Button Button--primary', onclick: this.reopenTicket.bind(this) },
                  t('tickets.reopen')
                )
              : null,
            ticket.canClose()
              ? m(Button, { className: 'Button', onclick: this.closeTicket.bind(this) }, t('tickets.close'))
              : null,
          ]),
    ]));
  }

  sendReply() {
    var self = this;
    if (!this.reply() || this.saving) {
      return;
    }
    this.saving = true;
    app.store
      .createRecord('moderation-ticket-replies')
      .save({
        content: this.reply(),
        relationships: { ticket: this.ticket },
      })
      .then(function () {
        self.reply('');
        self.saving = false;
        self.load();
      })
      .catch(function () {
        self.saving = false;
        m.redraw();
      });
  }

  closeTicket() {
    var self = this;
    this.ticket.save({ status: 'closed' }).then(function () {
      self.load();
    });
  }

  reopenTicket() {
    var self = this;
    this.ticket.save({ status: 'open' }).then(function () {
      self.load();
    });
  }
}

class WarningNotification extends Notification {
  icon() {
    return 'fas fa-exclamation-triangle';
  }

  href() {
    var user = app.session.user;
    return user ? app.route('user.warnings', { username: user.slug() }) : app.forum.attribute('baseUrl');
  }

  content() {
    var fromUser = this.attrs.notification.fromUser();
    var data = this.attrs.notification.content() || {};
    return t('notifications.warning_received', {
      username: fromUser ? fromUser.displayName() : '',
      points: data.points || 0,
    });
  }
}

class ReportModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);
    this.reason = Stream('');
    this.reasonDetail = Stream('');
  }

  className() {
    return 'ReportModal';
  }

  title() {
    if (this.attrs.post) {
      return t('report.title_post');
    }
    if (this.attrs.discussion) {
      return t('report.title_discussion');
    }
    return t('report.title_user', { username: this.attrs.user.displayName() });
  }

  content() {
    var self = this;
    return m('div.Modal-body', m('div.Form', [
      m('div.Form-group', [
        m('label', t('report.reason')),
        m(Select, {
          value: this.reason(),
          onchange: this.reason,
          options: {
            '': t('report.reason_placeholder'),
            spam: t('report.reason_spam'),
            abuse: t('report.reason_abuse'),
            illegal: t('report.reason_illegal'),
            other: t('report.reason_other'),
          },
        }),
      ]),
      m('div.Form-group', [
        m('label', t('report.details')),
        m('textarea.FormControl', {
          value: this.reasonDetail(),
          oninput: function (e) {
            self.reasonDetail(e.target.value);
          },
        }),
      ]),
      m(
        'div.Form-group',
        m(Button, { className: 'Button Button--primary', type: 'submit', loading: this.loading }, t('report.submit'))
      ),
    ]));
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading = true;

    var relationships = {};
    var targetType = 'user';
    if (this.attrs.post) {
      targetType = 'post';
      relationships.post = this.attrs.post;
    } else if (this.attrs.discussion) {
      targetType = 'discussion';
      relationships.discussion = this.attrs.discussion;
    } else {
      relationships.user = this.attrs.user;
    }

    var self = this;
    app.store
      .createRecord('moderation-reports')
      .save({
        targetType: targetType,
        reason: this.reason(),
        reasonDetail: this.reasonDetail(),
        relationships: relationships,
      })
      .then(function () {
        app.alerts.show({ type: 'success' }, t('report.success'));
        self.hide();
      })
      .catch(function () {
        self.loading = false;
        m.redraw();
      });
  }
}

class WarnModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);
    this.points = Stream('1');
    this.reason = Stream('');
    this.comment = Stream('');
  }

  className() {
    return 'WarnModal';
  }

  title() {
    return t('warn.title', { username: this.attrs.user.displayName() });
  }

  content() {
    var self = this;
    return m('div.Modal-body', m('div.Form', [
      m('div.Form-group', [
        m('label', t('warn.points')),
        m(Select, {
          value: this.points(),
          onchange: this.points,
          options: { '1': '1', '5': '5', '10': '10', '25': '25' },
        }),
      ]),
      m('div.Form-group', [
        m('label', t('warn.reason')),
        m('input.FormControl', {
          value: this.reason(),
          oninput: function (e) {
            self.reason(e.target.value);
          },
        }),
      ]),
      m('div.Form-group', [
        m('label', t('warn.comment')),
        m('textarea.FormControl', {
          value: this.comment(),
          oninput: function (e) {
            self.comment(e.target.value);
          },
        }),
      ]),
      m(
        'div.Form-group',
        m(Button, { className: 'Button Button--primary', type: 'submit', loading: this.loading }, t('warn.submit'))
      ),
    ]));
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading = true;

    var relationships = { user: this.attrs.user };
    if (this.attrs.post) {
      relationships.post = this.attrs.post;
    } else if (this.attrs.discussion) {
      relationships.discussion = this.attrs.discussion;
    }

    var self = this;
    app.store
      .createRecord('moderation-warnings')
      .save({
        points: parseInt(this.points(), 10),
        reason: this.reason(),
        comment: this.comment(),
        relationships: relationships,
      })
      .then(function () {
        app.alerts.show({ type: 'success' }, t('warn.success'));
        if (self.attrs.onsaved) {
          self.attrs.onsaved();
        }
        self.hide();
      })
      .catch(function () {
        self.loading = false;
        m.redraw();
      });
  }
}

class HandleReportModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);
    this.deleteContent = Stream(false);
    this.warn = Stream(false);
    this.ban = Stream(false);
    this.warningPoints = Stream('1');
    this.warningReason = Stream('');
    this.banDays = Stream('7');
    this.banReason = Stream('');
  }

  className() {
    return 'HandleReportModal Modal--large';
  }

  title() {
    return t('handle.title');
  }

  content() {
    var self = this;
    var report = this.attrs.report;
    var target = report.targetUser();
    var post = report.post && report.post();
    var discussion = report.discussion && report.discussion();

    return m('div.Modal-body', [
      m('div.HandleReportModal-summary', [
        m('div', [m('strong', t('panel.target') + ': '), target ? username(target) : '']),
        m('div', [m('strong', t('panel.type') + ': '), t('panel.type_' + report.targetType())]),
        m('div', [m('strong', t('panel.reason') + ': '), reasonLabel(report.reason())]),
        report.reasonDetail() ? m('p', report.reasonDetail()) : null,
        post && post.contentPlain
          ? m('p.ModerationPage-excerpt', (post.contentPlain() || '').slice(0, 240))
          : null,
        discussion ? m('div', discussion.title()) : null,
      ]),
      report.status() === 'pending'
        ? m('div.Form', [
            m('div.Form-group', m(Checkbox, { state: this.deleteContent(), onchange: this.deleteContent }, t('handle.delete_content'))),
            m('div.Form-group', m(Checkbox, { state: this.warn(), onchange: this.warn }, t('handle.warn'))),
            this.warn()
              ? [
                  m('div.Form-group', [
                    m('label', t('handle.warning_points')),
                    m(Select, {
                      value: this.warningPoints(),
                      onchange: this.warningPoints,
                      options: { '1': '1', '5': '5', '10': '10', '25': '25' },
                    }),
                  ]),
                  m('div.Form-group', [
                    m('label', t('handle.warning_reason')),
                    m('input.FormControl', {
                      value: this.warningReason(),
                      oninput: function (e) {
                        self.warningReason(e.target.value);
                      },
                    }),
                  ]),
                ]
              : null,
            m('div.Form-group', m(Checkbox, { state: this.ban(), onchange: this.ban }, t('handle.ban'))),
            this.ban()
              ? [
                  m('div.Form-group', [
                    m('label', t('handle.ban_days')),
                    m(Select, {
                      value: this.banDays(),
                      onchange: this.banDays,
                      options: {
                        '1': t('handle.ban_1'),
                        '7': t('handle.ban_7'),
                        '30': t('handle.ban_30'),
                        '0': t('handle.ban_0'),
                      },
                    }),
                  ]),
                  m('div.Form-group', [
                    m('label', t('handle.ban_reason')),
                    m('input.FormControl', {
                      value: this.banReason(),
                      oninput: function (e) {
                        self.banReason(e.target.value);
                      },
                    }),
                  ]),
                ]
              : null,
            m('div.Form-group', [
              m(
                Button,
                { className: 'Button Button--primary', type: 'submit', loading: this.loading },
                t('handle.resolve')
              ),
              ' ',
              m(
                Button,
                {
                  className: 'Button',
                  onclick: function () {
                    self.submitStatus('rejected');
                  },
                },
                t('handle.reject')
              ),
            ]),
          ])
        : m(Button, { className: 'Button', onclick: this.hide.bind(this) }, t('handle.close')),
    ]);
  }

  onsubmit(e) {
    e.preventDefault();
    this.submitStatus('resolved');
  }

  submitStatus(status) {
    var self = this;
    this.loading = true;
    this.attrs.report
      .save({
        status: status,
        deleteContent: this.deleteContent(),
        warn: this.warn(),
        warningPoints: parseInt(this.warningPoints(), 10),
        warningReason: this.warningReason(),
        ban: this.ban(),
        banDays: parseInt(this.banDays(), 10),
        banReason: this.banReason(),
      })
      .then(function () {
        if (self.attrs.onsaved) {
          self.attrs.onsaved();
        }
        self.hide();
      })
      .catch(function () {
        self.loading = false;
        m.redraw();
      });
  }
}

function sumArr(arr) {
  var total = 0;
  var i;
  for (i = 0; i < (arr || []).length; i++) {
    total += arr[i] || 0;
  }
  return total;
}

function niceMax(n) {
  if (n <= 0) {
    return 4;
  }
  var exp = Math.pow(10, Math.floor(Math.log10(n)));
  var m = n / exp;
  var nice = m <= 1 ? 1 : m <= 2 ? 2 : m <= 5 ? 5 : 10;
  return nice * exp;
}

function formatChartDay(iso) {
  if (!iso) {
    return '';
  }
  var parts = String(iso).split('-');
  if (parts.length !== 3) {
    return iso;
  }
  return parts[2] + '.' + parts[1];
}

class LineChart {
  view(vnode) {
    var title = vnode.attrs.title;
    var labels = vnode.attrs.labels || [];
    var series = vnode.attrs.series || [];
    var W = 440;
    var H = 220;
    var L = 36;
    var R = 12;
    var T = 16;
    var B = 28;
    var innerW = W - L - R;
    var innerH = H - T - B;
    var max = 0;
    var i;
    var j;
    var values;

    for (i = 0; i < series.length; i++) {
      values = series[i].values || [];
      for (j = 0; j < values.length; j++) {
        if (values[j] > max) {
          max = values[j];
        }
      }
    }
    max = niceMax(max);

    var n = labels.length || 1;
    function xAt(idx) {
      if (n <= 1) {
        return L + innerW / 2;
      }
      return L + (idx / (n - 1)) * innerW;
    }
    function yAt(v) {
      return T + innerH - ((v || 0) / max) * innerH;
    }
    function points(vals) {
      var pts = [];
      var k;
      for (k = 0; k < vals.length; k++) {
        pts.push(xAt(k) + ',' + yAt(vals[k] || 0));
      }
      return pts.join(' ');
    }

    var grid = [];
    var ticks = 4;
    for (i = 0; i <= ticks; i++) {
      var val = Math.round((max / ticks) * i);
      var y = yAt(val);
      grid.push(
        m('line.ModerationLineChart-grid', {
          x1: L,
          x2: W - R,
          y1: y,
          y2: y,
        })
      );
      grid.push(
        m('text.ModerationLineChart-ylabel', {
          x: L - 6,
          y: y + 3,
          'text-anchor': 'end',
        }, String(val))
      );
    }

    var xLabels = [];
    var showIdx = n >= 30 ? [0, 7, 14, 21, 29] : [0, Math.floor((n - 1) / 2), n - 1];
    var seen = {};
    for (i = 0; i < showIdx.length; i++) {
      j = showIdx[i];
      if (j < 0 || j >= n || seen[j]) {
        continue;
      }
      seen[j] = true;
      xLabels.push(
        m('text.ModerationLineChart-xlabel', {
          x: xAt(j),
          y: H - 8,
          'text-anchor': j === 0 ? 'start' : j === n - 1 ? 'end' : 'middle',
        }, formatChartDay(labels[j]))
      );
    }

    var lines = [];
    var dots = [];
    for (i = 0; i < series.length; i++) {
      var ser = series[i];
      values = ser.values || [];
      lines.push(
        m('polyline.ModerationLineChart-line', {
          points: points(values),
          stroke: ser.color,
          'stroke-width': 2.25,
          'stroke-linejoin': 'round',
          'stroke-linecap': 'round',
        })
      );
      for (j = 0; j < values.length; j++) {
        dots.push(
          m(
            'circle.ModerationLineChart-dot',
            {
              cx: xAt(j),
              cy: yAt(values[j] || 0),
              r: values[j] ? 3 : 2.25,
              fill: ser.color,
            },
            m('title', formatChartDay(labels[j]) + ' — ' + ser.label + ': ' + (values[j] || 0))
          )
        );
      }
    }

    return m('div.ModerationPage-chart', [
      m('div.ModerationPage-chartHead', [
        m('h3', title),
        m(
          'div.ModerationPage-legend',
          series.map(function (ser) {
            return m('span.ModerationPage-legendItem', [
              m('i.ModerationPage-legendSwatch', { style: { background: ser.color } }),
              ser.label + ' (' + sumArr(ser.values) + ')',
            ]);
          })
        ),
      ]),
      m(
        'svg.ModerationLineChart',
        { viewBox: '0 0 ' + W + ' ' + H, role: 'img', 'aria-label': title },
        [grid, xLabels, lines, dots]
      ),
    ]);
  }
}

class ModerationPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);
    app.setTitle(t('panel.title'));
    this.status = 'pending';
    this.pageNumber = 1;
    this.perPage = 20;
    this.total = 0;
    this.loading = true;
    this.reports = [];
    this.tickets = [];
    this.ticketTotal = 0;
    this.section = 'reports';
    this.ticketStatus = 'inbox';
    this.stats = null;
    this.statsLoading = true;

    if (!app.forum.attribute('canAccessModeration')) {
      m.route.set('/');
      return;
    }

    this.loadStats();
    this.refresh();
  }

  loadStats() {
    var self = this;
    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/moderation-stats',
      })
      .then(function (payload) {
        self.stats = payload;
        self.statsLoading = false;
        m.redraw();
      })
      .catch(function () {
        self.statsLoading = false;
        m.redraw();
      });
  }

  chartsView() {
    if (this.statsLoading) {
      return m('div.ModerationPage-charts', m(LoadingIndicator));
    }
    if (!this.stats || !this.stats.days) {
      return null;
    }

    return m('div.ModerationPage-charts', [
      m(LineChart, {
        title: extractText(t('panel.chart_activity')),
        labels: this.stats.days,
        series: [
          { label: extractText(t('panel.chart_discussions')), color: '#6cb2eb', values: this.stats.discussions || [] },
          { label: extractText(t('panel.chart_replies')), color: '#48c78e', values: this.stats.replies || [] },
        ],
      }),
      m(LineChart, {
        title: extractText(t('panel.chart_moderation')),
        labels: this.stats.days,
        series: [
          { label: extractText(t('panel.chart_reports')), color: '#f14668', values: this.stats.reports || [] },
          { label: extractText(t('panel.chart_warnings')), color: '#ff9f43', values: this.stats.warnings || [] },
        ],
      }),
    ]);
  }

  view() {
    return m('div.ModerationPage', m('div.container', [
      m('div.ModerationPage-head', m('h2', t('panel.title'))),
      this.chartsView(),
      m('div.ModerationPage-sections', [
        this.sectionButton('reports', t('tickets.section_reports')),
        this.sectionButton('tickets', t('tickets.section_tickets')),
      ]),
      this.section === 'tickets' ? this.ticketsView() : this.reportsView(),
    ]));
  }

  sectionButton(section, label) {
    var self = this;
    return m(
      Button,
      {
        className: 'Button' + (this.section === section ? ' Button--primary' : ''),
        onclick: function () {
          self.section = section;
          self.pageNumber = 1;
          if (section === 'tickets') {
            self.refreshTickets();
          } else {
            self.refresh();
          }
        },
      },
      label
    );
  }

  reportsView() {
    var self = this;
    var pages = Math.max(1, Math.ceil(this.total / this.perPage));
    var pageButtons = [];
    var i;
    for (i = 1; i <= pages; i++) {
      pageButtons.push(this.pageButton(i));
    }

    return [
      m('div.ModerationPage-tabs', [
        this.tabButton('pending'),
        this.tabButton('resolved'),
        this.tabButton('rejected'),
      ]),
      m('div.ModerationPage-panel', this.loading
        ? m(LoadingIndicator)
        : this.reports.length
          ? [
              m('div.ModerationPage-tableWrap', m('table.ModerationPage-table', [
                m('thead', m('tr', [
                  m('th', t('panel.target')),
                  m('th', t('panel.type')),
                  m('th', t('panel.reason')),
                  m('th', t('panel.reporter')),
                  m('th', t('panel.date')),
                  m('th', t('panel.actions')),
                ])),
                m('tbody', this.reports.map(function (report) {
                  return self.row(report);
                })),
              ])),
              pages > 1 ? m('div.ModerationPage-pager', pageButtons) : null,
            ]
          : m('p', t('panel.empty'))
      ),
    ];
  }

  ticketsView() {
    var self = this;
    var pages = Math.max(1, Math.ceil(this.ticketTotal / this.perPage));
    var pageButtons = [];
    var i;
    for (i = 1; i <= pages; i++) {
      pageButtons.push(this.ticketPageButton(i));
    }

    return [
      m('div.ModerationPage-tabs', [
        this.ticketTab('inbox'),
        this.ticketTab('answered'),
        this.ticketTab('closed'),
      ]),
      m('div.ModerationPage-panel', this.loading
        ? m(LoadingIndicator)
        : this.tickets.length
          ? [
              m('div.ModerationPage-tableWrap', m('table.ModerationPage-table', [
                m('thead', m('tr', [
                  m('th', t('tickets.user')),
                  m('th', t('tickets.subject')),
                  m('th', t('tickets.category')),
                  m('th', t('tickets.status')),
                  m('th', t('tickets.last_update')),
                  m('th', t('panel.actions')),
                ])),
                m('tbody', this.tickets.map(function (ticket) {
                  return self.ticketRow(ticket);
                })),
              ])),
              pages > 1 ? m('div.ModerationPage-pager', pageButtons) : null,
            ]
          : m('p', t('tickets.empty_inbox'))
      ),
    ];
  }

  ticketTab(status) {
    var self = this;
    return m(
      Button,
      {
        className: 'Button' + (this.ticketStatus === status ? ' Button--primary' : ''),
        onclick: function () {
          self.ticketStatus = status;
          self.pageNumber = 1;
          self.refreshTickets();
        },
      },
      t(status === 'inbox' ? 'tickets.inbox' : 'tickets.status_' + status)
    );
  }

  ticketPageButton(page) {
    var self = this;
    return m(
      Button,
      {
        className: 'Button' + (page === this.pageNumber ? ' Button--primary' : ''),
        onclick: function () {
          self.pageNumber = page;
          self.refreshTickets();
        },
      },
      String(page)
    );
  }

  ticketRow(ticket) {
    var user = ticket.user && ticket.user();
    return m('tr', [
      m('td', user ? m(Link, { href: app.route.user(user) }, username(user)) : null),
      m('td', [
        ticket.subject(),
        ticket.priority() === 'high' ? m('div.ModerationPage-excerpt', t('tickets.priority_high')) : null,
      ]),
      m('td', ticketCategoryLabel(ticket.category())),
      m('td', statusBadge(ticket.status())),
      m('td', formatDate(ticket.lastRepliedAt() || ticket.createdAt())),
      m(
        'td',
        m(
          Link,
          { className: 'Button Button--primary Button--sm', href: app.route('tickets.show', { id: ticket.id() }) },
          t('tickets.open')
        )
      ),
    ]);
  }

  refreshTickets() {
    var self = this;
    this.loading = true;
    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/moderation-tickets',
        params: {
          filter: { status: this.ticketStatus },
          page: { offset: (this.pageNumber - 1) * this.perPage, limit: this.perPage },
          include: 'user,assignedTo',
        },
      })
      .then(function (payload) {
        self.ticketTotal = (payload.meta && payload.meta.total) || 0;
        self.tickets = app.store.pushPayload(payload);
        self.loading = false;
        if (self.ticketStatus === 'inbox') {
          app.forum.pushAttributes({ openModerationTickets: self.ticketTotal });
        }
        m.redraw();
      })
      .catch(function () {
        self.loading = false;
        m.redraw();
      });
  }

  tabButton(status) {
    var self = this;
    return m(
      Button,
      {
        className: 'Button' + (this.status === status ? ' Button--primary' : ''),
        onclick: function () {
          self.status = status;
          self.pageNumber = 1;
          self.refresh();
        },
      },
      t('panel.' + status)
    );
  }

  pageButton(page) {
    var self = this;
    return m(
      Button,
      {
        className: 'Button' + (page === this.pageNumber ? ' Button--primary' : ''),
        onclick: function () {
          self.pageNumber = page;
          self.refresh();
        },
      },
      String(page)
    );
  }

  row(report) {
    var self = this;
    var target = report.targetUser();
    var reporter = report.reporter();
    var post = report.post && report.post();
    var discussion = report.discussion && report.discussion();
    var href = null;

    if (post && app.route.post) {
      try {
        href = app.route.post(post);
      } catch (e) {
        href = null;
      }
    }
    if (!href && discussion) {
      href = app.route.discussion(discussion);
    }
    if (!href && target) {
      href = app.route.user(target);
    }

    return m('tr', [
      m('td', target ? m(Link, { href: app.route.user(target) }, username(target)) : null),
      m('td', t('panel.type_' + report.targetType())),
      m('td', [
        reasonLabel(report.reason()),
        report.reasonDetail() ? m('div.ModerationPage-excerpt', report.reasonDetail()) : null,
      ]),
      m('td', reporter ? username(reporter) : null),
      m('td', formatDate(report.createdAt())),
      m('td', [
        m(
          Button,
          {
            className: 'Button Button--primary Button--sm',
            onclick: function () {
              app.modal.show(HandleReportModal, {
                report: report,
                onsaved: function () {
                  self.refresh();
                },
              });
            },
          },
          t('panel.open')
        ),
        href
          ? m(Link, { className: 'Button Button--sm', href: href }, t('panel.view_content'))
          : null,
      ]),
    ]);
  }

  refresh() {
    var self = this;
    this.loading = true;

    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/moderation-reports',
        params: {
          filter: { status: this.status },
          page: { offset: (this.pageNumber - 1) * this.perPage, limit: this.perPage },
          include: 'reporter,targetUser,handledBy,post,discussion',
        },
      })
      .then(function (payload) {
        self.total = (payload.meta && payload.meta.total) || 0;
        self.reports = app.store.pushPayload(payload);
        self.loading = false;
        if (self.status === 'pending') {
          app.forum.pushAttributes({ pendingModerationReports: self.total });
        }
        m.redraw();
      })
      .catch(function () {
        self.loading = false;
        m.redraw();
      });
  }
}

class UserWarningsPage extends UserPage {
  oninit(vnode) {
    super.oninit(vnode);
    this.pageNumber = 1;
    this.perPage = 20;
    this.total = 0;
    this.loadingList = true;
    this.warnings = [];
    this.loadUser(m.route.param('username'));
  }

  show(user) {
    super.show(user);
    this.refresh();
  }

  content() {
    var self = this;
    if (!this.user) {
      return m(LoadingIndicator);
    }

    return m('div.WarningsPage', [
      m('p', t('warnings.points', { points: this.user.warningPoints() || 0 })),
      this.loadingList
        ? m(LoadingIndicator)
        : this.warnings.length
          ? this.warnings.map(function (item) {
              return self.item(item);
            })
          : m('p', t('warnings.empty')),
    ]);
  }

  item(item) {
    var actor = item.actor();
    return m('div.WarningsPage-item', [
      m('strong', item.reason()),
      m('div', extractText(t('warnings.points', { points: item.points() })) + ' · ' + formatDate(item.createdAt()) + ' · ' + extractText(t('warnings.by', { username: actor ? actor.displayName() : '' }))),
      item.comment() ? m('p', item.comment()) : null,
    ]);
  }

  refresh() {
    if (!this.user) {
      return;
    }
    var self = this;
    this.loadingList = true;
    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/moderation-warnings',
        params: {
          filter: { user: this.user.id() },
          page: { offset: (this.pageNumber - 1) * this.perPage, limit: this.perPage },
          include: 'user,actor,discussion',
        },
      })
      .then(function (payload) {
        self.total = (payload.meta && payload.meta.total) || 0;
        self.warnings = app.store.pushPayload(payload);
        self.loadingList = false;
        m.redraw();
      })
      .catch(function () {
        self.loadingList = false;
        m.redraw();
      });
  }
}

function canSeeWarnings(user) {
  if (!user || !app.session.user) {
    return false;
  }
  return app.forum.attribute('canAccessModeration') || app.session.user.id() === user.id();
}

function canReportTarget(user) {
  return !!(user && app.forum.attribute('canReport') && (!app.session.user || app.session.user.id() !== user.id()));
}

app.initializers.add('prm-moderation', function () {
  app.store.models['moderation-reports'] = ModerationReport;
  app.store.models['moderation-warnings'] = ModerationWarning;
  app.store.models['moderation-tickets'] = ModerationTicket;
  app.store.models['moderation-ticket-replies'] = ModerationTicketReply;
  app.routes.moderation = { path: '/moderation', component: ModerationPage };
  app.routes.tickets = { path: '/tickets', component: TicketsPage };
  app.routes['tickets.show'] = { path: '/tickets/:id', component: TicketPage };
  app.routes['user.warnings'] = { path: '/u/:username/warnings', component: UserWarningsPage };
  app.notificationComponents.moderationWarningReceived = WarningNotification;
  app.notificationComponents.moderationTicketReplied = TicketRepliedNotification;

  User.prototype.warningPoints = Model.attribute('warningPoints');
  User.prototype.warningCount = Model.attribute('warningCount');
  User.prototype.canWarn = Model.attribute('canWarn');
  User.prototype.canReportUser = Model.attribute('canReportUser');

  extend(HeaderSecondary.prototype, 'items', function (items) {
    if (app.forum.attribute('canAccessModeration')) {
      var reportCount = app.forum.attribute('pendingModerationReports') || 0;
      var ticketCount = app.forum.attribute('openModerationTickets') || 0;
      var count = reportCount + ticketCount;
      items.add(
        'moderation',
        m(
          LinkButton,
          { href: app.route('moderation'), icon: 'fas fa-shield-alt', className: 'Button Button--link' },
          [t('header'), count ? m('span.ModerationBadge', String(count)) : null]
        ),
        12
      );
    }
    if (app.session.user && app.forum.attribute('canCreateTicket')) {
      var waiting = app.forum.attribute('waitingSupportTickets') || 0;
      items.add(
        'tickets',
        m(
          LinkButton,
          { href: app.route('tickets'), icon: 'fas fa-life-ring', className: 'Button Button--link' },
          [t('header_tickets'), waiting ? m('span.ModerationBadge', String(waiting)) : null]
        ),
        11
      );
    }
  });

  extend(UserPage.prototype, 'navItems', function (items) {
    var user = this.user;
    if (!canSeeWarnings(user)) {
      return;
    }
    items.add(
      'warnings',
      m(
        LinkButton,
        { href: app.route('user.warnings', { username: user.slug() }), icon: 'fas fa-exclamation-triangle' },
        t('nav_warnings')
      ),
      80
    );
  });

  extend(UserCard.prototype, 'infoItems', function (items) {
    var user = this.attrs.user;
    if (!canSeeWarnings(user) || !user.warningPoints()) {
      return;
    }
    items.add('warning-points', m('span.UserCard-warningPoints', t('warnings.points', { points: user.warningPoints() })), 80);
  });

  extend(UserControls, 'userControls', function (items, user) {
    if (user && user.canReportUser && user.canReportUser()) {
      items.add(
        'moderation-report',
        m(
          Button,
          {
            icon: 'fas fa-flag',
            onclick: function () {
              app.modal.show(ReportModal, { user: user });
            },
          },
          t('user_controls.report')
        ),
        70
      );
    }
  });

  extend(UserControls, 'moderationControls', function (items, user) {
    if (user && user.canWarn && user.canWarn()) {
      items.add(
        'moderation-warn',
        m(
          Button,
          {
            icon: 'fas fa-exclamation-triangle',
            onclick: function () {
              app.modal.show(WarnModal, { user: user });
            },
          },
          t('user_controls.warn')
        ),
        90
      );
    }
  });

  extend(PostControls, 'userControls', function (items, post) {
    var user = post.user && post.user();
    if (!canReportTarget(user)) {
      return;
    }
    items.add(
      'moderation-report',
      m(
        Button,
        {
          icon: 'fas fa-flag',
          onclick: function () {
            app.modal.show(ReportModal, { post: post, user: user });
          },
        },
        t('post_controls.report')
      )
    );
  });

  extend(PostControls, 'moderationControls', function (items, post) {
    var user = post.user && post.user();
    if (!app.forum.attribute('canAccessModeration') || !user || (app.session.user && app.session.user.id() === user.id())) {
      return;
    }
    items.add(
      'moderation-warn',
      m(
        Button,
        {
          icon: 'fas fa-exclamation-triangle',
          onclick: function () {
            app.modal.show(WarnModal, { user: user, post: post });
          },
        },
        t('post_controls.warn')
      )
    );
  });

  extend(DiscussionControls, 'userControls', function (items, discussion) {
    var user = discussion.user && discussion.user();
    if (!canReportTarget(user)) {
      return;
    }
    items.add(
      'moderation-report',
      m(
        Button,
        {
          icon: 'fas fa-flag',
          onclick: function () {
            app.modal.show(ReportModal, { discussion: discussion, user: user });
          },
        },
        t('discussion_controls.report')
      )
    );
  });

  extend(DiscussionControls, 'moderationControls', function (items, discussion) {
    var user = discussion.user && discussion.user();
    if (!app.forum.attribute('canAccessModeration') || !user || (app.session.user && app.session.user.id() === user.id())) {
      return;
    }
    items.add(
      'moderation-warn',
      m(
        Button,
        {
          icon: 'fas fa-exclamation-triangle',
          onclick: function () {
            app.modal.show(WarnModal, { user: user, discussion: discussion });
          },
        },
        t('discussion_controls.warn')
      )
    );
  });

  extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
    items.add('moderationWarningReceived', {
      name: 'moderationWarningReceived',
      icon: 'fas fa-exclamation-triangle',
      label: t('notifications.notify_warning_label'),
    });
    items.add('moderationTicketReplied', {
      name: 'moderationTicketReplied',
      icon: 'fas fa-life-ring',
      label: t('notifications.notify_ticket_label'),
    });
  });
});

module.exports = {};
})();
