import app from 'flarum/forum/app';
import FormModal from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Select from 'flarum/common/components/Select';
import Checkbox from 'flarum/common/components/Checkbox';
import Stream from 'flarum/common/utils/Stream';
import username from 'flarum/common/helpers/username';
import { t, reasonLabel } from '../utils';

export default class HandleReportModal extends FormModal {
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
    const report = this.attrs.report;
    const target = report.targetUser();
    const post = report.post && report.post();
    const discussion = report.discussion && report.discussion();

    return (
      <div className="Modal-body">
        <div className="HandleReportModal-summary">
          <div>
            <strong>{t('panel.target')}: </strong>
            {target ? username(target) : ''}
          </div>
          <div>
            <strong>{t('panel.type')}: </strong>
            {t('panel.type_' + report.targetType())}
          </div>
          <div>
            <strong>{t('panel.reason')}: </strong>
            {reasonLabel(report.reason())}
          </div>
          {report.reasonDetail() ? <p>{report.reasonDetail()}</p> : null}
          {post && post.contentPlain ? (
            <p className="ModerationPage-excerpt">{(post.contentPlain() || '').slice(0, 240)}</p>
          ) : null}
          {discussion ? <div>{discussion.title()}</div> : null}
        </div>

        {report.status() === 'pending' ? (
          <div className="Form">
            <div className="Form-group">
              <Checkbox state={this.deleteContent()} onchange={this.deleteContent}>
                {t('handle.delete_content')}
              </Checkbox>
            </div>
            <div className="Form-group">
              <Checkbox state={this.warn()} onchange={this.warn}>
                {t('handle.warn')}
              </Checkbox>
            </div>
            {this.warn() ? (
              <div>
                <div className="Form-group">
                  <label>{t('handle.warning_points')}</label>
                  <Select
                    value={this.warningPoints()}
                    onchange={this.warningPoints}
                    options={{ 1: '1', 5: '5', 10: '10', 25: '25' }}
                  />
                </div>
                <div className="Form-group">
                  <label>{t('handle.warning_reason')}</label>
                  <input className="FormControl" bidi={this.warningReason} />
                </div>
              </div>
            ) : null}
            <div className="Form-group">
              <Checkbox state={this.ban()} onchange={this.ban}>
                {t('handle.ban')}
              </Checkbox>
            </div>
            {this.ban() ? (
              <div>
                <div className="Form-group">
                  <label>{t('handle.ban_days')}</label>
                  <Select
                    value={this.banDays()}
                    onchange={this.banDays}
                    options={{
                      1: t('handle.ban_1'),
                      7: t('handle.ban_7'),
                      30: t('handle.ban_30'),
                      0: t('handle.ban_0'),
                    }}
                  />
                </div>
                <div className="Form-group">
                  <label>{t('handle.ban_reason')}</label>
                  <input className="FormControl" bidi={this.banReason} />
                </div>
              </div>
            ) : null}
            <div className="Form-group">
              <Button className="Button Button--primary" type="submit" loading={this.loading}>
                {t('handle.resolve')}
              </Button>{' '}
              <Button className="Button" onclick={() => this.submitStatus('rejected')}>
                {t('handle.reject')}
              </Button>
            </div>
          </div>
        ) : (
          <Button className="Button" onclick={this.hide.bind(this)}>
            {t('handle.close')}
          </Button>
        )}
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();
    this.submitStatus('resolved');
  }

  submitStatus(status) {
    this.loading = true;
    this.attrs.report
      .save({
        status,
        deleteContent: this.deleteContent(),
        warn: this.warn(),
        warningPoints: parseInt(this.warningPoints(), 10),
        warningReason: this.warningReason(),
        ban: this.ban(),
        banDays: parseInt(this.banDays(), 10),
        banReason: this.banReason(),
      })
      .then(() => {
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
