import app from 'flarum/forum/app';
import UserPage from 'flarum/forum/components/UserPage';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import extractText from 'flarum/common/utils/extractText';
import { t, formatDate, metaTotal } from '../utils';

export default class UserWarningsPage extends UserPage {
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
    if (!this.user) {
      return <LoadingIndicator />;
    }

    return (
      <div className="WarningsPage">
        <p>{t('warnings.points', { points: this.user.warningPoints() || 0 })}</p>
        {this.loadingList ? (
          <LoadingIndicator />
        ) : this.warnings.length ? (
          this.warnings.map((item) => this.item(item))
        ) : (
          <p>{t('warnings.empty')}</p>
        )}
      </div>
    );
  }

  item(item) {
    const actor = item.actor();
    return (
      <div className="WarningsPage-item">
        <strong>{item.reason()}</strong>
        <div>
          {extractText(t('warnings.points', { points: item.points() })) +
            ' · ' +
            formatDate(item.createdAt()) +
            ' · ' +
            extractText(t('warnings.by', { username: actor ? actor.displayName() : '' }))}
        </div>
        {item.comment() ? <p>{item.comment()}</p> : null}
      </div>
    );
  }

  refresh() {
    if (!this.user) {
      return;
    }
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
      .then((payload) => {
        this.total = metaTotal(payload);
        this.warnings = app.store.pushPayload(payload);
        this.loadingList = false;
        m.redraw();
      })
      .catch(() => {
        this.loadingList = false;
        m.redraw();
      });
  }
}
