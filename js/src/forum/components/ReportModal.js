import app from 'flarum/forum/app';
import FormModal from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Select from 'flarum/common/components/Select';
import Stream from 'flarum/common/utils/Stream';
import { t } from '../utils';

export default class ReportModal extends FormModal {
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
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{t('report.reason')}</label>
            <Select
              value={this.reason()}
              onchange={this.reason}
              options={{
                '': t('report.reason_placeholder'),
                spam: t('report.reason_spam'),
                abuse: t('report.reason_abuse'),
                illegal: t('report.reason_illegal'),
                other: t('report.reason_other'),
              }}
            />
          </div>
          <div className="Form-group">
            <label>{t('report.details')}</label>
            <textarea className="FormControl" bidi={this.reasonDetail} />
          </div>
          <div className="Form-group">
            <Button className="Button Button--primary" type="submit" loading={this.loading}>
              {t('report.submit')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading = true;

    const relationships = {};
    let targetType = 'user';
    if (this.attrs.post) {
      targetType = 'post';
      relationships.post = this.attrs.post;
    } else if (this.attrs.discussion) {
      targetType = 'discussion';
      relationships.discussion = this.attrs.discussion;
    } else {
      relationships.user = this.attrs.user;
    }

    app.store
      .createRecord('moderation-reports')
      .save({
        targetType,
        reason: this.reason(),
        reasonDetail: this.reasonDetail(),
        relationships,
      })
      .then(() => {
        app.alerts.show({ type: 'success' }, t('report.success'));
        this.hide();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
