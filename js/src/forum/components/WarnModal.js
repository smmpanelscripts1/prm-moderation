import app from 'flarum/forum/app';
import FormModal from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Select from 'flarum/common/components/Select';
import Stream from 'flarum/common/utils/Stream';
import { t } from '../utils';

export default class WarnModal extends FormModal {
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
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{t('warn.points')}</label>
            <Select
              value={this.points()}
              onchange={this.points}
              options={{ 1: '1', 5: '5', 10: '10', 25: '25' }}
            />
          </div>
          <div className="Form-group">
            <label>{t('warn.reason')}</label>
            <input className="FormControl" bidi={this.reason} />
          </div>
          <div className="Form-group">
            <label>{t('warn.comment')}</label>
            <textarea className="FormControl" bidi={this.comment} />
          </div>
          <div className="Form-group">
            <Button className="Button Button--primary" type="submit" loading={this.loading}>
              {t('warn.submit')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading = true;

    const relationships = { user: this.attrs.user };
    if (this.attrs.post) {
      relationships.post = this.attrs.post;
    } else if (this.attrs.discussion) {
      relationships.discussion = this.attrs.discussion;
    }

    app.store
      .createRecord('moderation-warnings')
      .save({
        points: parseInt(this.points(), 10),
        reason: this.reason(),
        comment: this.comment(),
        relationships,
      })
      .then(() => {
        app.alerts.show({ type: 'success' }, t('warn.success'));
        if (this.attrs.onsaved) {
          this.attrs.onsaved();
        }
        this.hide();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
