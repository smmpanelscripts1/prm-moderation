import app from 'flarum/forum/app';
import FormModal from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Select from 'flarum/common/components/Select';
import Stream from 'flarum/common/utils/Stream';
import { t } from '../utils';

export default class CreateTicketModal extends FormModal {
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
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{t('tickets.category')}</label>
            <Select
              value={this.category()}
              onchange={this.category}
              options={{
                '': t('tickets.category_placeholder'),
                account: t('tickets.category_account'),
                payment: t('tickets.category_payment'),
                technical: t('tickets.category_technical'),
                appeal: t('tickets.category_appeal'),
                other: t('tickets.category_other'),
              }}
            />
          </div>
          <div className="Form-group">
            <label>{t('tickets.priority')}</label>
            <Select
              value={this.priority()}
              onchange={this.priority}
              options={{
                normal: t('tickets.priority_normal'),
                high: t('tickets.priority_high'),
              }}
            />
          </div>
          <div className="Form-group">
            <label>{t('tickets.subject')}</label>
            <input className="FormControl" bidi={this.subject} />
          </div>
          <div className="Form-group">
            <label>{t('tickets.message')}</label>
            <textarea className="FormControl" bidi={this.message} />
          </div>
          <div className="Form-group">
            <Button className="Button Button--primary" type="submit" loading={this.loading}>
              {t('tickets.submit')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading = true;

    app.store
      .createRecord('moderation-tickets')
      .save({
        subject: this.subject(),
        category: this.category(),
        priority: this.priority(),
        content: this.message(),
      })
      .then((ticket) => {
        app.alerts.show({ type: 'success' }, t('tickets.success'));
        this.hide();
        m.route.set(app.route('tickets.show', { id: ticket.id() }));
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
